@extends('emails.layout')

@section('title', $update->subject)

@section('content')
    <h2>{{ $update->subject }}</h2>

    <p>Hello {{ $vendor->name }},</p>

    <div class="info-box">
        <p>{!! nl2br(e($update->body)) !!}</p>
    </div>

    @if($update->ctaUrl && $update->ctaLabel)
        <div class="button-container">
            <a href="{{ $update->ctaUrl }}" class="action-button">
                {{ $update->ctaLabel }}
            </a>
        </div>
    @endif

    <div class="link-fallback">
        <p>You're receiving this because you have vendor updates enabled.</p>
        <p>You can manage your notification preferences in your <a href="{{ url('/notifications/preferences') }}">account settings</a>.</p>
    </div>
@endsection
