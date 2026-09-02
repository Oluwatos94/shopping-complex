@extends('emails.layout')

@section('title', $update->subject)

@section('content')
    @if($update->bannerUrl)
        <div class="campaign-banner">
            <img src="{{ $update->bannerUrl }}" alt="{{ $update->subject }}">
        </div>
    @endif

    <h2>{{ $update->subject }}</h2>

    <p>Hello {{ $vendor->name }},</p>

    <div class="rich-text">
        {!! $bodyHtml !!}
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
