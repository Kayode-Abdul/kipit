<?php

use function Livewire\Volt\layout;
use function Livewire\Volt\state;
use function Livewire\Volt\mount;
use function Livewire\Volt\with;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;

layout('livewire.components.layouts.guest');

state([
    'accessKeyPrice' => 50000,
    'email' => '',
    'checkoutType' => '',
    'checkoutPlanId' => null,
    'showCheckoutModal' => false,
]);

$openCheckout = function ($type, $planId = null) {
    $this->checkoutType = $type;
    $this->checkoutPlanId = $planId;
    $this->showCheckoutModal = true;
};

$processCheckout = function () {
    $this->validate(['email' => 'required|email']);
    
    if ($this->checkoutType === 'subscription') {
        $plan = SubscriptionPlan::with('prices')->findOrFail($this->checkoutPlanId);
        $priceObj = $plan->prices->first(); 
        $amount = $priceObj->price;
        $currency = $priceObj->currency_code;
        $metadata = [
            'type' => 'subscription_purchase',
            'duration_in_months' => $plan->duration_in_months,
            'plan_id' => $plan->id,
        ];
    } else {
        $amount = (int) SystemSetting::get('access_key_price', 50000);
        $currency = 'NGN';
        $metadata = [
            'type' => 'activation_key',
        ];
    }

    $response = \Illuminate\Support\Facades\Http::withToken(config('services.paystack.secret_key'))
            ->post('https://api.paystack.co/transaction/initialize', [
                'email' => $this->email,
                'amount' => $amount * 100, // kobo
                'currency' => $currency,
                'reference' => 'KIPIT_'.time().'_'.uniqid(),
                'callback_url' => url('/pricing?success=1'),
                'metadata' => $metadata,
            ]);

    if ($response->successful()) {
        return $this->redirect($response->json('data.authorization_url'));
    } else {
        $this->addError('email', 'Payment initialization failed. Please try again.');
    }
};

mount(function () {
    if (tenant()) {
        return redirect('/member');
    }

    $this->accessKeyPrice = (int) SystemSetting::get('access_key_price', 50000);
});

with(function () {
    // Fetch dynamic prices
    $plans = SubscriptionPlan::with('prices')->where('is_published', true)->get();
    
    $mappedPrices = $plans->map(function($plan) {
        // Fallback to NGN if multiple or none, for display purposes we might just show one or the first one.
        // Actually, we could show all currencies, or default to NGN since it was hardcoded before.
        // Let's find NGN or the first price
        $price = $plan->prices->where('currency_code', 'NGN')->first() ?? $plan->prices->first();
        $formattedPrice = $price ? price_format($price->price, $price->currency_code) : 'N/A';
        
        return [
            'id' => $plan->id,
            'type' => 'subscription',
            'title' => $plan->name,
            'description' => 'Subscription plan for ' . $plan->duration_in_months . ' months.',
            'price' => $formattedPrice,
            'button' => 'Subscribe Now',
            'route' => '',
            'includes' => [
                'All features for ' . $plan->duration_in_months . ' months',
                'Full POS access',
                'Cross-Platform Sync',
            ],
            'excludes' => [],
        ];
    })->toArray();

    return [
        'prices' => $mappedPrices,
    ];
});

?>

<div>
  <header class="relative block w-full py-6 leading-10 text-center">
    <div class="w-full px-6 mx-auto leading-10 text-center lg:px-8 max-w-7xl">
      <div class="box-border flex flex-wrap items-center justify-between -mx-4 text-indigo-900">
        <div class="relative z-10 flex items-center w-auto px-4 leading-10 lg:flex-grow-0 lg:flex-shrink-0 lg:text-left">
          <a href="/" class="flex box-border font-sans text-2xl font-bold text-left text-gray-900 no-underline bg-transparent cursor-pointer focus:no-underline items-end gap-x-2">
            <img src="{{ env('APP_URL') }}/assets/logo/image.png" class="h-10"> <p>Kipit</p>
          </a>
        </div>
        <div class="relative items-center hidden px-4 mt-2 space-x-5 font-medium leading-10 md:flex md:flex-grow-0 md:flex-shrink-0 md:mt-0 md:text-right lg:flex-grow-0 lg:flex-shrink-0">
          <a href="/download" class="font-medium text-gray-500 hover:text-gray-900">Download</a>
          <a href="/blog" class="font-medium text-gray-500 hover:text-gray-900">Blog</a>
          <a href="/pricing" class="font-medium text-kipit-primary hover:text-gray-900">Pricing</a>
          <a href="/member/login"
            class="bg-gray-100 text-gray-400 md:w-auto w-full px-8 py-3 rounded-full flex items-center justify-center font-medium text-lg focus:ring-offset-2 focus:ring-2 focus:ring-gray-100 focus:text-gray-700 hover:text-gray-700"
          >Login</a>
          <a href="{{ route('auth.register') }}" class="bg-kipit-primary text-white md:w-auto w-full px-8 py-3 rounded-full flex items-center justify-center font-medium text-lg focus:ring-offset-2 focus:ring-2 focus:ring-kipit-primary">Register POS</a>
        </div>
      </div>
    </div>
  </header>

  <section id="price">
    <div class="mx-auto max-w-7xl py-12 px-6 lg:px-8">
      <div class="sm:align-center sm:flex sm:flex-col">
        <p class="text-4xl font-extrabold text-center">Our Pricing.</p>

        @if(request()->has('success'))
        <div class="mt-8 bg-green-100 border border-green-300 text-green-900 px-6 py-4 rounded-xl shadow-sm text-center inline-block max-w-md mx-auto w-full">
            <p class="font-bold text-lg flex items-center justify-center gap-2">Payment Successful!</p>
            <p class="mt-2 text-sm">Please check your email for your activation key.</p>
        </div>
        @endif

      </div>
      <div class="mt-12 space-y-4 sm:mt-16 sm:grid sm:grid-cols-2 lg:grid-cols-3 sm:gap-6 sm:space-y-0 mx-auto max-w-4xl lg:max-w-7xl justify-items-center">
        @foreach($prices as $price)
        <div class="divide-y divide-gray-200 rounded-lg border border-gray-200 shadow-sm w-80 bg-white">
          <div class="p-6">
            <p class="text-lg font-medium leading-6 text-gray-900">{{ $price['title'] }}</p>
            <p class="mt-4 text-sm text-gray-500">{{ $price['description'] }}</p>
            <p class="mt-8">
              <span class="text-4xl font-bold tracking-tight text-gray-900">{{ $price['price'] }}</span>
            </p>
            <button wire:click="openCheckout('{{ $price['type'] }}', {{ $price['id'] ?? 'null' }})"
              class="mt-8 block w-full rounded-md border border-gray-800 bg-gray-800 py-2 text-center text-sm font-semibold text-white hover:bg-gray-900">
              {{ $price['button'] }}
            </button>
          </div>
          <div class="px-6 pt-6 pb-8">
            <h3 class="text-sm font-medium text-gray-900">Includes</h3>
            <ul role="list" class="mt-6 space-y-4">
              @foreach($price['includes'] as $include)
              <li class="flex space-x-3">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 flex-shrink-0 text-green-500">
                  <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                </svg>
                <span class="text-sm text-gray-500">{{ $include }}</span>
              </li>
              @endforeach
            </ul>
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </section>

  @if($showCheckoutModal)
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 relative">
          <button wire:click="$set('showCheckoutModal', false)" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
          </button>
          
          <h2 class="text-2xl font-bold mb-4">Checkout</h2>
          <p class="text-gray-600 mb-6">Enter your email address. We will send your activation key to this email after successful payment.</p>
          
          <form wire:submit="processCheckout">
              <div class="mb-4">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                  <input type="email" wire:model="email" class="w-full rounded-md border-gray-300 shadow-sm focus:border-kipit-primary focus:ring focus:ring-kipit-primary focus:ring-opacity-50" required placeholder="you@example.com">
                  @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
              </div>
              
              <div class="mt-6 flex justify-end gap-3">
                  <button type="button" wire:click="$set('showCheckoutModal', false)" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancel</button>
                  <button type="submit" class="px-4 py-2 bg-kipit-primary text-white rounded-md hover:bg-opacity-90 flex items-center gap-2">
                      <span wire:loading wire:target="processCheckout" class="animate-spin h-4 w-4 border-2 border-white rounded-full border-t-transparent"></span>
                      Proceed to Payment
                  </button>
              </div>
          </form>
      </div>
  </div>
  @endif

  <section id="footer" class="bg-gray-800 text-white py-10 flex justify-center gap-x-3 mt-auto">
    <p>kipit made with </p>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
      <path d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z" />
    </svg>
  </section>
</div>
