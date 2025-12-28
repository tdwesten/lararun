<x-mail::message>
# {{ __('Today\'s Training Plan: :title', ['title' => $recommendation->title]) }}

**{{ __('Type:') }}** {{ $recommendation->type }}

{{ $recommendation->description }}

<x-mail::panel>
### {{ __('Why this workout?') }}
{{ $recommendation->reasoning }}
</x-mail::panel>

<x-mail::button :url="config('app.url') . '/dashboard'">
{{ __('Go to Dashboard') }}
</x-mail::button>

{{ __('Thanks,') }}<br>
{{ __('Your Lararun Coach') }}
</x-mail::message>
