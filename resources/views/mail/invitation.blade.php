<x-mail::message>
# You're invited

{{ $inviter->name }} has invited you to join {{ config('app.name') }}, a place to analyze arguments for logical fallacies, rhetorical devices and the questions worth asking.

<x-mail::button :url="$registrationUrl">
Create your account
</x-mail::button>

Your invitation code is **{{ $invitation->code }}**. It can be used once, with this email address.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
