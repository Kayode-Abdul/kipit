<?php

namespace App\Filament\Tenant\Pages;

use App\Filament\Tenant\Pages\Traits\HasPageNavigation;
use App\Mail\SupportTicketCreatedMail;
use App\Mail\SupportTicketReplyMail;
use App\Models\Admin;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Traits\HasTranslatableResource;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class SupportTickets extends Page implements HasForms
{
    use HasPageNavigation;
    use HasTranslatableResource;
    use InteractsWithForms;

    public static ?string $label = 'Support';

    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';

    protected static ?string $navigationLabel = 'Support';

    protected static string $view = 'filament.tenant.pages.support-tickets';

    protected static ?int $navigationSort = 98;

    public ?array $data = [];

    public ?int $selectedTicketId = null;

    public string $replyBody = '';

    public static function canAccess(): bool
    {
        return Auth::guard('web')->check();
    }

    public function mount(): void
    {
        $this->form->fill([
            'category' => 'general',
            'priority' => 'normal',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('category')
                    ->options(SupportTicket::categories())
                    ->required(),
                Forms\Components\Select::make('priority')
                    ->options([
                        'low' => 'Low',
                        'normal' => 'Normal',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('subject')
                    ->required()
                    ->maxLength(180),
                Forms\Components\Textarea::make('body')
                    ->label('Describe the issue')
                    ->required()
                    ->rows(5)
                    ->minLength(10),
            ])
            ->statePath('data');
    }

    public function getTicketsProperty()
    {
        $user = Auth::guard('web')->user();

        return SupportTicket::query()
            ->where('tenant_id', tenant('id'))
            ->where(function ($q) use ($user) {
                $q->where('tenant_user_id', $user?->id)
                    ->orWhere('reporter_email', $user?->email);
            })
            ->with('replies')
            ->latest()
            ->limit(50)
            ->get();
    }

    public function getSelectedTicketProperty(): ?SupportTicket
    {
        if (! $this->selectedTicketId) {
            return null;
        }

        return SupportTicket::query()
            ->where('tenant_id', tenant('id'))
            ->with('replies')
            ->find($this->selectedTicketId);
    }

    public function createTicket(): void
    {
        $data = $this->form->getState();
        $user = Auth::guard('web')->user();

        $ticket = SupportTicket::create([
            'tenant_id' => (string) tenant('id'),
            'tenant_user_id' => $user?->id,
            'reporter_name' => $user?->name ?? 'Shop user',
            'reporter_email' => $user?->email ?? '',
            'category' => $data['category'],
            'priority' => $data['priority'],
            'subject' => $data['subject'],
            'body' => $data['body'],
            'status' => 'open',
        ]);

        try {
            $admins = Admin::query()->where('role', Admin::ROLE_ADMIN)->pluck('email')->filter()->all();
            if ($admins === []) {
                $admins = ['support@kipitpos.com'];
            }
            Mail::to($admins)->send(new SupportTicketCreatedMail($ticket));
        } catch (\Throwable $e) {
            report($e);
        }

        $this->form->fill([
            'category' => 'general',
            'priority' => 'normal',
            'subject' => '',
            'body' => '',
        ]);
        $this->selectedTicketId = $ticket->id;

        Notification::make()
            ->title(__('Ticket submitted'))
            ->body(__('Reference: :ref', ['ref' => $ticket->reference]))
            ->success()
            ->send();
    }

    public function openTicket(int $ticketId): void
    {
        $this->selectedTicketId = $ticketId;
        $this->replyBody = '';
    }

    public function sendReply(): void
    {
        $ticket = $this->selectedTicket;
        $body = trim($this->replyBody);

        if (! $ticket || $body === '') {
            Notification::make()->title(__('Reply cannot be empty'))->danger()->send();

            return;
        }

        if (in_array($ticket->status, ['closed'], true)) {
            Notification::make()->title(__('This ticket is closed'))->warning()->send();

            return;
        }

        $user = Auth::guard('web')->user();
        $reply = SupportTicketReply::create([
            'support_ticket_id' => $ticket->id,
            'author_type' => 'tenant_user',
            'author_id' => $user?->id,
            'author_name' => $user?->name ?? 'Shop user',
            'body' => $body,
            'is_internal' => false,
        ]);

        if ($ticket->status === 'resolved') {
            $ticket->update(['status' => 'open', 'resolved_at' => null]);
        }

        try {
            $recipients = Admin::query()->where('role', Admin::ROLE_ADMIN)->pluck('email')->filter()->all();
            if ($recipients !== []) {
                Mail::to($recipients)->send(new SupportTicketReplyMail($ticket->fresh(), $reply));
            }
        } catch (\Throwable $e) {
            report($e);
        }

        $this->replyBody = '';
        Notification::make()->title(__('Reply sent'))->success()->send();
    }
}
