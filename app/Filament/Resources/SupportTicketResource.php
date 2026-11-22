<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportTicketResource\Pages;
use App\Mail\SupportTicketReplyMail;
use App\Models\Admin;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';

    protected static ?string $navigationLabel = 'Support Tickets';

    protected static ?string $navigationGroup = 'Support';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        /** @var Admin|null $admin */
        $admin = Auth::guard('admin')->user();

        return $admin?->canManageSupport() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Ticket')
                ->schema([
                    Forms\Components\TextInput::make('reference')->disabled(),
                    Forms\Components\TextInput::make('tenant_id')->label('Shop')->disabled(),
                    Forms\Components\TextInput::make('reporter_name')->disabled(),
                    Forms\Components\TextInput::make('reporter_email')->disabled(),
                    Forms\Components\Select::make('category')
                        ->options(SupportTicket::categories())
                        ->disabled(),
                    Forms\Components\Select::make('status')
                        ->options(SupportTicket::statuses())
                        ->required(),
                    Forms\Components\Select::make('priority')
                        ->options([
                            'low' => 'Low',
                            'normal' => 'Normal',
                            'high' => 'High',
                            'urgent' => 'Urgent',
                        ])
                        ->required(),
                    Forms\Components\Select::make('assigned_admin_id')
                        ->label('Assignee')
                        ->relationship('assignedAdmin', 'name')
                        ->searchable()
                        ->nullable(),
                    Forms\Components\TextInput::make('subject')->disabled()->columnSpanFull(),
                    Forms\Components\Textarea::make('body')->disabled()->rows(5)->columnSpanFull(),
                ])
                ->columns(2),
            Forms\Components\Section::make('Reply to customer')
                ->schema([
                    Forms\Components\Textarea::make('reply_body')
                        ->label('Message')
                        ->rows(4)
                        ->dehydrated(false),
                    Forms\Components\Toggle::make('reply_internal')
                        ->label('Internal note only (not emailed to customer)')
                        ->dehydrated(false)
                        ->default(false),
                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('sendReply')
                            ->label('Send reply')
                            ->action(function (SupportTicket $record, Forms\Get $get): void {
                                $body = trim((string) $get('reply_body'));
                                if ($body === '') {
                                    Notification::make()->title('Reply cannot be empty')->danger()->send();

                                    return;
                                }

                                /** @var Admin $admin */
                                $admin = Auth::guard('admin')->user();
                                $internal = (bool) $get('reply_internal');

                                $reply = SupportTicketReply::create([
                                    'support_ticket_id' => $record->id,
                                    'author_type' => 'admin',
                                    'author_id' => $admin->id,
                                    'author_name' => $admin->name,
                                    'body' => $body,
                                    'is_internal' => $internal,
                                ]);

                                if ($record->status === 'open') {
                                    $record->status = 'in_progress';
                                    $record->save();
                                }

                                if (! $internal) {
                                    try {
                                        Mail::to($record->reporter_email)->send(new SupportTicketReplyMail($record, $reply));
                                    } catch (\Throwable $e) {
                                        report($e);
                                    }
                                }

                                Notification::make()->title('Reply saved')->success()->send();
                            }),
                    ]),
                ])
                ->visibleOn('edit'),
            Forms\Components\Section::make('Conversation')
                ->schema([
                    Forms\Components\Placeholder::make('conversation')
                        ->label('')
                        ->content(function (?SupportTicket $record): string {
                            if (! $record) {
                                return '—';
                            }

                            $lines = $record->replies->map(function (SupportTicketReply $reply) {
                                $tag = $reply->is_internal ? ' [internal]' : '';
                                $who = $reply->author_name.' ('.$reply->author_type.')'.$tag;

                                return '<p><strong>'.e($who).'</strong> · '.e(optional($reply->created_at)->toDayDateTimeString()).'<br>'.nl2br(e($reply->body)).'</p>';
                            })->implode('<hr>');

                            return $lines !== '' ? new \Illuminate\Support\HtmlString($lines) : 'No replies yet.';
                        }),
                ])
                ->visibleOn('edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('reference')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('tenant_id')->label('Shop')->searchable(),
                Tables\Columns\TextColumn::make('subject')->limit(40)->searchable(),
                Tables\Columns\TextColumn::make('category')->badge(),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'warning',
                        'in_progress' => 'info',
                        'resolved' => 'success',
                        'closed' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('priority')->badge(),
                Tables\Columns\TextColumn::make('assignedAdmin.name')->label('Assignee')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(SupportTicket::statuses()),
                Tables\Filters\SelectFilter::make('category')->options(SupportTicket::categories()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupportTickets::route('/'),
            'edit' => Pages\EditSupportTicket::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
