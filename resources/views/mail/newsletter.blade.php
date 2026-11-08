<x-mail::message>
# {{ $heading }}

@if ($intro)
{{ $intro }}

@endif
@foreach ($sections as $heading => $links)
## {{ $heading }}

@foreach ($links as $link)
- [{{ $link['title'] }}]({{ $link['url'] }})
@endforeach

@endforeach
Շնորհակալություն,<br>
{{ config('app.name') }}
</x-mail::message>
