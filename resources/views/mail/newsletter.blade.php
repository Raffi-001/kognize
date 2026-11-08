<x-mail::message>
# {{ $newsletter->subject }}

@if ($newsletter->intro)
{{ $newsletter->intro }}

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
