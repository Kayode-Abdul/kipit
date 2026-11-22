<x-mail::message>
# New support ticket

**Reference:** {{ $ticket->reference }}  
**Shop:** {{ $ticket->tenant_id }}  
**From:** {{ $ticket->reporter_name }} &lt;{{ $ticket->reporter_email }}&gt;  
**Category:** {{ $ticket->category }}  
**Subject:** {{ $ticket->subject }}

{{ $ticket->body }}

<x-mail::button :url="url('/admin/support-tickets/'.$ticket->id.'/edit')">
Open in Admin
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
