<x-mail::message>
# {{ __('Hi :name,', ['name' => $name]) }}

{{ $content }}

<x-mail::button :url="config('app.url') . '/dashboard'">
{{ __('View Dashboard') }}
</x-mail::button>

{{ __('Thanks,') }}<br>
{{ __('Your Lararun Coach') }}
</x-mail::message>
