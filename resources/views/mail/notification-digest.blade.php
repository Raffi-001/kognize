<x-mail::message>
# Here's what you missed

@foreach ($items as $item)
**{{ $item['headline'] }}**
@if ($item['excerpt'])
<br>{{ $item['excerpt'] }}
@endif

@endforeach

<x-mail::button :url="$notificationsUrl">
Open notifications
</x-mail::button>

You get this daily summary because you turned it on. [Change notification settings]({{ $settingsUrl }}).

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
