<x-mail::message>
# New Contact Form Submission

You have received a new message from the Kipit contact form.

**Name:** {{ $name }}  
**Email:** {{ $email }}

**Message:**  
{{ $message }}

<x-mail::button :url="'mailto:' . $email">
Reply to {{ $name }}
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
