<x-mail::message>
# Thank you for your purchase!

Here are the details of your transaction:

<x-mail::panel>
**Transaction Reference:** {{ $data['reference'] }}<br>
**Type:** {{ $data['type'] }}<br>
**Amount Paid:** {{ $data['amount'] }}
</x-mail::panel>

@if(!empty($data['activation_key']))
### Your Activation Key
**{{ $data['activation_key'] }}**

@if(!empty($data['is_used']))
*This key was automatically applied to your store plan.*
@else
*Please copy this key and enter it in your store's dashboard to activate your plan.*
@endif
@endif

@if(!empty($data['expires_at']))
**New Plan Expiry Date:** {{ \Carbon\Carbon::parse($data['expires_at'])->format('F j, Y') }}
@endif

If you have any questions, feel free to reply to this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
