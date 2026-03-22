@extends('emails.layout')

@section('title', 'You have a notification')

@section('content')
    <h2>New Notification</h2>

    <p>Hello {{ $user->name }},</p>

    <p>You received a notification while you were away:</p>

    <div class="info-box">
        <p><strong>{{ $notification->message }}</strong></p>
        @if($notification->is_grouped && $notification->group_count > 1)
            <p style="margin-top: 10px; font-size: 13px; color: #666;">
                And {{ $notification->group_count - 1 }} more similar notification{{ $notification->group_count > 2 ? 's' : '' }}
            </p>
        @endif
    </div>

    @if(!empty($notification->data))
        @if(isset($notification->data['sender_name']))
            <p><strong>From:</strong> {{ $notification->data['sender_name'] }}</p>
        @endif
        @if(isset($notification->data['message_preview']))
            <p><strong>Preview:</strong> "{{ Str::limit($notification->data['message_preview'], 100) }}"</p>
        @endif
        @if(isset($notification->data['product_name']))
            <p><strong>Product:</strong> {{ $notification->data['product_name'] }}</p>
        @endif
    @endif

    <div class="button-container">
        <a href="{{ url('/') }}" class="action-button">
            View All Notifications
        </a>
    </div>

    <div class="link-fallback">
        <p>You're receiving this email because you were offline when this notification was sent.</p>
        <p>You can manage your notification preferences in your <a href="{{ url('/notifications/preferences') }}">account settings</a>.</p>
    </div>
@endsection
