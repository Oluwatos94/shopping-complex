@extends('emails.layout')

@section('title', 'Verify Your Email — jiidaa')

@section('content')
    <h2>Verify Your Email Address</h2>

    <p>Hello <strong>{{ $user->name }}</strong>,</p>

    <p>
        Thank you for joining jiidaa! To complete your registration and
        access all features, please verify your email address by clicking the button below.
    </p>

    <!-- Verify Button -->
    <div class="button-container">
        <a href="{{ $verificationUrl }}" class="action-button">
            Verify Email Address
        </a>
    </div>

    <!-- Info box -->
    <div class="info-box">
        <p>
            <strong>Note:</strong> This verification link expires in
            <strong>60 minutes</strong>. If it expires, you can request a new
            one from the sign-in page.
        </p>
    </div>

    <p style="color: #888; font-size: 13px;">
        If you did not create an account with jiidaa, you can safely
        ignore this email — no action is required.
    </p>

    <!-- Link fallback -->
    <hr class="divider">
    <div class="link-fallback">
        <p>If the button above doesn't work, copy and paste this link into your browser:</p>
        <p class="link-text">{{ $verificationUrl }}</p>
    </div>
@endsection
