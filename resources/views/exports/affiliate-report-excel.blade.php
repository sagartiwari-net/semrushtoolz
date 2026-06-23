<table border="1">
    <tr><th colspan="2">Affiliate Report — {{ $report['period_label'] ?? now()->format('Y-m-d') }} — {{ now()->format('Y-m-d H:i') }}</th></tr>
    <tr><th>Metric</th><th>Value</th></tr>
    @foreach ($report['summary'] as $key => $value)
        <tr><td>{{ ucwords(str_replace('_', ' ', $key)) }}</td><td>{{ $value }}</td></tr>
    @endforeach
    <tr><th colspan="2">{{ ($report['breakdown_type'] ?? 'daily') === 'monthly' ? 'Monthly' : 'Daily' }} Breakdown</th></tr>
    <tr><th>{{ ($report['breakdown_type'] ?? 'daily') === 'monthly' ? 'Month' : 'Date' }}</th><th>Clicks</th><th>Signups</th><th>Orders</th></tr>
    @foreach ($report['breakdown'] as $row)
        <tr><td>{{ $row['date'] }}</td><td>{{ $row['clicks'] }}</td><td>{{ $row['signups'] }}</td><td>{{ $row['orders'] }}</td></tr>
    @endforeach
</table>
