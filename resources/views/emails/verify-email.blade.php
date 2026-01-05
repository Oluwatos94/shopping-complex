@extends('emails.layout')

@section('title', 'Verify Your Email Address')

@section('content')
    <h2>Verify Your Email Address</h2>

    <p>Hello {{ $user->name }},</p>

    <p>Thank you for registering with Shopping Complex! To complete your registration and access all features, please verify your email address by clicking the button below.</p>

    <!-- Verify Button -->
    <div class="button-container">
        <a href="{{ $verificationUrl }}" class="action-button">
            Verify Email Address
        </a>
    </div>

    <!-- Info Box -->
    <div class="info-box">
        <p><strong>Important:</strong> This verification link will expire in 60 minutes. If the link expires, you can request a new verification email from your account dashboard.</p>
    </div>

    <p>If you did not create an account with Shopping Complex, no further action is required. Your email address will not be used without verification.</p>

    <!-- Link Fallback -->
    <div class="link-fallback">
        <p>If the button above doesn't work, copy and paste this link into your browser:</p>
        <p class="link-text">{{ $verificationUrl }}</p>
    </div>
@endsection
