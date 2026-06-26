Unverified account purge completed on {{ config('app.name') }}.

Deleted: {{ $deletedCount }} account(s)

@if (count($emails) > 0)
Emails removed:
@foreach (array_slice($emails, 0, 50) as $email)
- {{ $email }}
@endforeach
@if (count($emails) > 50)
... and {{ count($emails) - 50 }} more
@endif
@endif

View purge history in admin or run: php artisan users:purge-unverified --dry-run
