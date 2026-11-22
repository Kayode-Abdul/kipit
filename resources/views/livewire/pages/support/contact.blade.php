<?php

use App\Mail\ContactFormMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use function Livewire\Volt\layout;
use function Livewire\Volt\rules;
use function Livewire\Volt\state;

layout('components.marketing-layout', ['title' => 'Contact Us']);

state([
    'name' => '',
    'email' => '',
    'message' => '',
    'success' => false,
]);

rules([
    'name' => 'required|min:2',
    'email' => 'required|email',
    'message' => 'required|min:10',
]);

$submit = function () {
    $key = 'contact-form:'.request()->ip();
    if (RateLimiter::tooManyAttempts($key, 3)) {
        $this->addError('email', __('Too many attempts. Please wait a minute and try again.'));

        return;
    }

    RateLimiter::hit($key, 60);
    $this->validate();

    Mail::to(['support@kipitpos.com', 'moshoodkayodeabdul@gmail.com'])
        ->send(new ContactFormMail($this->name, $this->email, $this->message));

    $this->reset(['name', 'email', 'message']);
    $this->success = true;
};

?>

<div class="max-w-4xl mx-auto py-16 px-6 lg:px-8">
  <div class="text-center mb-16">
    <h1 class="text-4xl font-extrabold text-gray-900 mb-4">Contact Us</h1>
    <p class="text-lg text-gray-600">We're here to help. Get in touch with the Kipit POS team.</p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
    <div>
      <h2 class="text-2xl font-bold text-gray-800 mb-6">Send us a message</h2>

      @if($success)
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
          <strong class="font-bold">Message sent!</strong>
          <span class="block sm:inline">We've received your message and will get back to you shortly.</span>
        </div>
      @endif

      <form wire:submit="submit" class="space-y-6">
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
          <div class="mt-1">
            <input type="text" wire:model="name" id="name" class="shadow-sm focus:ring-kipit-primary focus:border-kipit-primary block w-full sm:text-sm border-gray-300 rounded-md py-3 px-4 border" placeholder="John Doe">
          </div>
          @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
          <div class="mt-1">
            <input type="email" wire:model="email" id="email" class="shadow-sm focus:ring-kipit-primary focus:border-kipit-primary block w-full sm:text-sm border-gray-300 rounded-md py-3 px-4 border" placeholder="john@example.com">
          </div>
          @error('email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>
        <div>
          <label for="message" class="block text-sm font-medium text-gray-700">Message</label>
          <div class="mt-1">
            <textarea id="message" wire:model="message" rows="4" class="shadow-sm focus:ring-kipit-primary focus:border-kipit-primary block w-full sm:text-sm border border-gray-300 rounded-md py-3 px-4" placeholder="How can we help you?"></textarea>
          </div>
          @error('message') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>
        <div>
          <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-kipit-primary hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-kipit-primary disabled:opacity-50" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="submit">Send Message</span>
            <span wire:loading wire:target="submit">Sending...</span>
          </button>
        </div>
      </form>
    </div>

    <div class="bg-gray-50 rounded-lg p-8 h-fit">
      <h3 class="text-xl font-bold text-gray-900 mb-6">Other ways to connect</h3>

      <div class="flex items-start mb-6">
        <div class="flex-shrink-0 text-kipit-primary">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
        </div>
        <div class="ml-3 text-base text-gray-500">
          <p class="font-medium text-gray-900">Email Support</p>
          <p class="mt-1">support@kipitpos.com</p>
        </div>
      </div>

      <div class="flex items-start mb-6">
        <div class="flex-shrink-0 text-kipit-primary">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
        </div>
        <div class="ml-3 text-base text-gray-500">
          <p class="font-medium text-gray-900">Security</p>
          <p class="mt-1">
            <a href="mailto:security@kipitpos.com" class="text-kipit-primary hover:underline">security@kipitpos.com</a>
            · <a href="/security" class="text-kipit-primary hover:underline">Security overview</a>
          </p>
        </div>
      </div>

      <div class="flex items-start mb-6">
        <div class="flex-shrink-0 text-kipit-primary">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 1.736-2 3.182-2 1.932 0 3.5 1.343 3.5 3 0 1.4-1.043 2.567-2.437 2.9-.39.093-.563.25-.563.5V14M12 17h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div class="ml-3 text-base text-gray-500">
          <p class="font-medium text-gray-900">Help Center</p>
          <p class="mt-1">
            <a href="/help" class="text-kipit-primary hover:underline">Browse FAQs and guides</a>
          </p>
        </div>
      </div>

      <div class="flex items-start">
        <div class="flex-shrink-0 text-kipit-primary">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
        </div>
        <div class="ml-3 text-base text-gray-500">
          <p class="font-medium text-gray-900">In-app Support</p>
          <p class="mt-1">Logged-in shops can open a ticket from <span class="font-medium text-gray-700">Settings → Support</span> in Kipit.</p>
        </div>
      </div>
    </div>
  </div>
</div>
