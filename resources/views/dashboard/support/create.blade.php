@extends('layouts.dashboard')

@section('title', 'New Ticket')

@section('content')
    <div class="mb-4">
        <a href="{{ route('dashboard.support') }}" class="text-sm text-accent hover:underline">← Back to support</a>
    </div>

    <div class="dash-card max-w-2xl">
        <h1 class="dash-page-title">Create Support Ticket</h1>
        <form method="POST" action="{{ route('dashboard.support.store') }}" class="mt-5 space-y-4">
            @csrf
            <div>
                <label class="ui-label">Subject</label>
                <input class="ui-input" name="subject" value="{{ old('subject') }}" required maxlength="200">
            </div>
            <div>
                <label class="ui-label">Priority</label>
                <select class="ui-input" name="priority">
                    <option value="low" @selected(old('priority') === 'low')>Low</option>
                    <option value="medium" @selected(old('priority', 'medium') === 'medium')>Medium</option>
                    <option value="high" @selected(old('priority') === 'high')>High</option>
                </select>
            </div>
            <div>
                <label class="ui-label">Message</label>
                <textarea class="ui-input min-h-[160px]" name="body" required>{{ old('body') }}</textarea>
            </div>
            <button type="submit" class="ui-btn-primary">Submit Ticket</button>
        </form>
    </div>
@endsection
