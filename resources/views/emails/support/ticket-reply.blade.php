<x-mail::message>
# Reply on ticket {{ $ticket->reference }}

**Subject:** {{ $ticket->subject }}  
**From:** {{ $reply->author_name }}

{{ $reply->body }}

Thanks,<br>
{{ config('app.name') }} Support
</x-mail::message>
