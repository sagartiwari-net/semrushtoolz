@extends('layouts.admin')

@section('title', 'Reseller pricing')

@section('content')
    <x-admin.reseller-nav />

    <div class="mb-5">
        <h1 class="dash-page-title !mb-1">Default reseller pricing</h1>
        <p class="text-sm text-ink-secondary">Monthly INR cost charged to resellers. Blank = not sellable unless a per-reseller override exists.</p>
    </div>

    <form method="POST" action="{{ route('admin.resellers.pricing.update') }}" class="ui-card p-5">
        @csrf
        @method('PUT')
        <div class="dash-table-wrap">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Tool / Plan</th>
                        <th>Shop type</th>
                        <th>Price INR / month</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tools as $tool)
                        <tr>
                            <td class="font-semibold">{{ $tool->name }}</td>
                            <td class="text-sm text-ink-secondary">{{ $tool->sellableTypeLabel() }}</td>
                            <td>
                                <input type="number" name="prices[{{ $tool->id }}]" min="0" step="1"
                                       value="{{ old('prices.'.$tool->id, $defaults[$tool->id] ?? '') }}"
                                       class="ui-input w-40" placeholder="—">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <button type="submit" class="ui-btn-primary mt-4">Save defaults</button>
    </form>
@endsection
