<x-mail::message>
# Hi {{ $name }},

{{ $content }}

<x-mail::button :url="config('app.url') . '/dashboard'">
View Dashboard
</x-mail::button>

Thanks,<br>
Your Lararun Coach
</x-mail::message>
