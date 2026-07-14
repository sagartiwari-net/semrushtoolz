@extends('layouts.reseller')

@section('title', 'Reset password')

@section('content')
    <div class="mb-5">
        <h1 class="dash-page-title">Reset password</h1>
        <p class="text-sm text-ink-secondary">Only for users you created or previously provisioned.</p>
    </div>

    <form method="POST" action="{{ route('reseller.password.store') }}" class="ui-card max-w-md space-y-4 p-5">
        @csrf
        <div>
            <label class="mb-1 block text-sm font-medium text-ink">Customer email</label>
            <input type="email" name="email" value="{{ old('email', request('email')) }}" required class="ui-input w-full">
        </div>
        <button type="submit" class="ui-btn-primary">Generate new password</button>
    </form>
@endsection
