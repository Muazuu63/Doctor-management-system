@extends('layouts.app', ['title' => 'দুই ধাপ যাচাই'])
@section('content')
<form method="post" action="{{ route('staff.2fa.verify') }}" class="card form" style="max-width:420px;margin:40px auto">
  @csrf
  <h1>দুই ধাপ যাচাই</h1>
  @if($method === 'totp')
    <p>Google Authenticator অ্যাপ থেকে ৬ অঙ্কের কোড দিন।</p>
  @else
    <p>আপনার মোবাইলে পাঠানো ৬ অঙ্কের কোড দিন।</p>
  @endif
  <label>কোড<input name="code" required inputmode="numeric" autocomplete="one-time-code" maxlength="8"></label>
  <button class="btn" type="submit">যাচাই করুন</button>
</form>
@if($method === 'sms')
  <form method="post" action="{{ route('staff.2fa.resend') }}" style="max-width:420px;margin:0 auto">
    @csrf
    <button class="btn ghost" type="submit">আবার কোড পাঠান</button>
  </form>
@endif
@endsection
