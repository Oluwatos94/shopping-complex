@extends('emails.layout')

@section('title', 'Reset Your Password')

@section('content')
    <h2>Reset Your Password</h2>

    <p>Hello,</p>

    <p>You are receiving this email because we received a password reset request for your account. Click the button below to reset your password.</p>

    <!-- Reset Password Button -->
    <div class="button-container">
        <a href="{{ $resetUrl }}" class="action-button">
            Reset Password
        </a>
    </div>

    <!-- Warning Box -->
    <div class="warning-box">
        <p><strong>Security Notice:</strong> This password reset link will expire in 60 minutes for your security.</p>
    </div>

    <p>If you did not request a password reset, no further action is required. Your password will remain unchanged.</p>

    <!-- Link Fallback -->
    <div class="link-fallback">
        <p>If the button above doesn't work, copy and paste this link into your browser:</p>
        <p class="link-text">{{ $resetUrl }}</p>
    </div>
@endsection
