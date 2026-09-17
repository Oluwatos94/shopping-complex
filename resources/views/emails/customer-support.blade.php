@extends('emails.layout')

@section('title', 'A message from jiidaa support')

@section('content')
    <h2>Hello {{ $customer->name }},</h2>

    <div class="rich-text">
        {!! $bodyHtml !!}
    </div>

    <div class="link-fallback">
        <p>This message was sent by the jiidaa support team in reply to your enquiry.</p>
    </div>
@endsection
