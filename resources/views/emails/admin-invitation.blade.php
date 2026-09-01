@extends('emails.layout')

@section('title', 'Admin Invitation')

@section('content')
    <h2>You have been invited as an administrator</h2>

    <p>Hello {{ $name }},</p>

    <p>{{ $invitedBy }} has invited you to join the jiidaa admin dashboard. Click the button below to set your password and activate your account.</p>

    <div class="button-container">
        <a href="{{ $inviteUrl }}" class="action-button">
            Set Your Password
        </a>
    </div>

    <div class="warning-box">
        <p><strong>Security Notice:</strong> This invitation link will expire in 60 minutes for your security.</p>
    </div>

    <p>If you were not expecting this invitation, you can safely ignore this email.</p>

    <div class="link-fallback">
        <p>If the button above doesn't work, copy and paste this link into your browser:</p>
        <p class="link-text">{{ $inviteUrl }}</p>
    </div>
@endsection
