<x-filament-panels::page>
  <div class="space-y-6">
    <x-filament::section>
      <x-slot name="heading">{{ __('Lost or stolen device?') }}</x-slot>
      <x-slot name="description">
        {{ __('Revoke sync access so that device can no longer pull or push shop data. Also change the affected user’s password. Local data on the device may still exist until the machine is wiped; desktop apps encrypt the offline database when the app is closed.') }}
      </x-slot>

      <div class="flex flex-wrap gap-3">
        <x-filament::button
          color="warning"
          wire:click="revokeAllDesktopDevices"
          wire:confirm="{{ __('Revoke all desktop sync tokens for this shop?') }}"
        >
          {{ __('Revoke all desktop devices') }}
        </x-filament::button>
        <x-filament::button
          color="danger"
          wire:click="revokeAllDevices"
          wire:confirm="{{ __('Revoke every API/sync token for this shop?') }}"
        >
          {{ __('Revoke all tokens') }}
        </x-filament::button>
      </div>
    </x-filament::section>

    <x-filament::section>
      <x-slot name="heading">{{ __('Connected devices & tokens') }}</x-slot>

      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
          <thead class="text-xs uppercase text-gray-500 border-b border-gray-200 dark:border-gray-700">
            <tr>
              <th class="py-3 pr-4">{{ __('Device') }}</th>
              <th class="py-3 pr-4">{{ __('User') }}</th>
              <th class="py-3 pr-4">{{ __('Type') }}</th>
              <th class="py-3 pr-4">{{ __('Last used') }}</th>
              <th class="py-3 pr-4">{{ __('Created') }}</th>
              <th class="py-3">{{ __('Actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse($this->devices as $device)
              <tr wire:key="device-{{ $device->id }}">
                <td class="py-3 pr-4 font-medium">{{ $device->name }}</td>
                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">
                  {{ $device->tokenable?->name ?? __('Unknown') }}
                  <div class="text-xs text-gray-400">{{ $device->tokenable?->email }}</div>
                </td>
                <td class="py-3 pr-4">
                  @if($this->isDesktopToken($device))
                    <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-800 dark:bg-amber-950 dark:text-amber-200">
                      {{ __('Desktop sync') }}
                    </span>
                  @else
                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                      {{ __('API token') }}
                    </span>
                  @endif
                </td>
                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">
                  {{ $device->last_used_at?->diffForHumans() ?? __('Never') }}
                </td>
                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">
                  {{ $device->created_at?->toDayDateTimeString() }}
                </td>
                <td class="py-3">
                  <x-filament::button
                    size="sm"
                    color="danger"
                    wire:click="revokeDevice({{ $device->id }})"
                    wire:confirm="{{ __('Revoke access for this device?') }}"
                  >
                    {{ __('Revoke') }}
                  </x-filament::button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="py-8 text-center text-gray-500">
                  {{ __('No connected devices or API tokens yet.') }}
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </x-filament::section>
  </div>
</x-filament-panels::page>
