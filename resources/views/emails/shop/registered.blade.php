<x-mail::message>
# Welcome to Kipit POS!

Hi {{ $ownerName }},

Your shop **{{ $shopName }}** has been created successfully. Keep these details safe:

<x-mail::panel>
**Shop link:** [{{ $shopUrl }}]({{ $shopUrl }})<br>
**Username (email):** {{ $username }}<br>
**Password:** {{ $passwordMask }}
</x-mail::panel>

The password above is masked on purpose. It is the same password you set when you registered — we never email your real password.

<x-mail::button :url="$shopUrl">
Open your shop
</x-mail::button>

If you forget your password, use **Forgot password** on the login page.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
