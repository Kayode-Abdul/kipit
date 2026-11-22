@props(['url'])
@php
    $logoUrl = rtrim((string) config('app.url'), '/').'/assets/logo/image.png';
@endphp
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ $logoUrl }}" class="logo" alt="{{ config('app.name', 'Kipit POS') }}" style="height: 64px; max-height: 64px; width: auto;">
</a>
</td>
</tr>
