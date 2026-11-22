<x-filament-panels::page>
  <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <x-filament::section>
      <x-slot name="heading">{{ __('Report an issue') }}</x-slot>
      <x-slot name="description">{{ __('Tell Kipit management what you are facing. We will follow up on your ticket.') }}</x-slot>

      <form wire:submit="createTicket" class="space-y-4">
        {{ $this->form }}
        <x-filament::button type="submit">
          {{ __('Submit ticket') }}
        </x-filament::button>
      </form>
    </x-filament::section>

    <x-filament::section>
      <x-slot name="heading">{{ __('My tickets') }}</x-slot>

      <div class="space-y-3 mb-6">
        @forelse($this->tickets as $ticket)
          <button
            type="button"
            wire:click="openTicket({{ $ticket->id }})"
            class="w-full text-left rounded-xl border px-4 py-3 transition {{ $selectedTicketId === $ticket->id ? 'border-primary-500 bg-primary-50 dark:bg-primary-950/30' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800' }}"
          >
            <div class="flex items-center justify-between gap-3">
              <div>
                <div class="font-semibold text-sm">{{ $ticket->subject }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ $ticket->reference }} · {{ $ticket->created_at?->diffForHumans() }}</div>
              </div>
              <span class="text-xs uppercase tracking-wide font-medium">{{ $ticket->status }}</span>
            </div>
          </button>
        @empty
          <p class="text-sm text-gray-500">{{ __('No tickets yet.') }}</p>
        @endforelse
      </div>

      @if($this->selectedTicket)
        <div class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-4">
          <div>
            <div class="text-sm font-semibold">{{ $this->selectedTicket->subject }}</div>
            <div class="text-xs text-gray-500 mt-1">
              {{ $this->selectedTicket->reference }} · {{ $this->selectedTicket->category }} · {{ $this->selectedTicket->status }}
            </div>
            <p class="mt-3 text-sm whitespace-pre-wrap">{{ $this->selectedTicket->body }}</p>
          </div>

          <div class="space-y-3 max-h-80 overflow-y-auto">
            @foreach($this->selectedTicket->replies->where('is_internal', false) as $reply)
              <div class="rounded-lg bg-gray-50 dark:bg-gray-800 px-3 py-2">
                <div class="text-xs text-gray-500 mb-1">
                  {{ $reply->author_name }} · {{ $reply->created_at?->diffForHumans() }}
                </div>
                <div class="text-sm whitespace-pre-wrap">{{ $reply->body }}</div>
              </div>
            @endforeach
          </div>

          @if($this->selectedTicket->status !== 'closed')
            <form wire:submit="sendReply" class="space-y-3">
              <textarea
                wire:model="replyBody"
                rows="3"
                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm"
                placeholder="{{ __('Write a reply...') }}"
              ></textarea>
              <x-filament::button type="submit" size="sm">
                {{ __('Send reply') }}
              </x-filament::button>
            </form>
          @endif
        </div>
      @endif
    </x-filament::section>
  </div>
</x-filament-panels::page>
