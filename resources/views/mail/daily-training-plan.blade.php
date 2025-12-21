<x-mail::message>
# Today's Training Plan: {{ $recommendation->title }}

**Type:** {{ $recommendation->type }}

{{ $recommendation->description }}

<x-mail::panel>
### Why this workout?
{{ $recommendation->reasoning }}
</x-mail::panel>

<x-mail::button :url="config('app.url') . '/dashboard'">
Go to Dashboard
</x-mail::button>

Thanks,<br>
Your Lararun Coach
</x-mail::message>
