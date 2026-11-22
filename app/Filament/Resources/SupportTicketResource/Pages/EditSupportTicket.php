<?php

namespace App\Filament\Resources\SupportTicketResource\Pages;

use App\Filament\Resources\SupportTicketResource;
use App\Models\SupportTicket;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupportTicket extends EditRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('markResolved')
                ->label('Mark resolved')
                ->color('success')
                ->requiresConfirmation()
                ->action(function (): void {
                    /** @var SupportTicket $ticket */
                    $ticket = $this->record;
                    $ticket->update([
                        'status' => 'resolved',
                        'resolved_at' => now(),
                    ]);
                    $this->refreshFormData(['status', 'resolved_at']);
                }),
            Actions\Action::make('close')
                ->label('Close')
                ->color('gray')
                ->requiresConfirmation()
                ->action(function (): void {
                    /** @var SupportTicket $ticket */
                    $ticket = $this->record;
                    $ticket->update(['status' => 'closed']);
                    $this->refreshFormData(['status']);
                }),
        ];
    }
}
