@extends('layouts.app', ['title' => 'দুই ধাপ যাচাই', 'active' => 'security', 'staff' => true])
@section('content')
<h1>লগইন সিকিউরিটি (২এফএ)</h1>
<p>প্রতি লগইনে এসএমএস OTP অথবা Google Authenticator কোড লাগবে।</p>
<div class="split">
  <form method="post" action="{{ route('staff.security.update') }}" class="card form">
    @csrf
    <label>পদ্ধতি
      <select name="two_factor_method">
        <option value="sms" {{ $user->two_factor_method === 'sms' ? 'selected' : '' }}>এসএমএস OTP (মোবাইল)</option>
        <option value="totp" {{ $user->two_factor_method === 'totp' ? 'selected' : '' }}>Google Authenticator</option>
      </select>
    </label>
    @if($pendingSecret)
      <p>Authenticator অ্যাপে এই সিক্রেট যোগ করুন:</p>
      <p><code>{{ $pendingSecret }}</code></p>
      <p class="muted">URI: {{ $otpauth }}</p>
      <label>অ্যাপের ৬ অঙ্কের কোড<input name="code" required inputmode="numeric"></label>
    @endif
    <button class="btn" type="submit">সেভ / কনফার্ম</button>
  </form>
  <div class="card">
    <h2>বর্তমান স্ট্যাটাস</h2>
    <p>পদ্ধতি: <b>{{ $user->two_factor_method === 'totp' ? 'Google Authenticator' : 'এসএমএস' }}</b></p>
    <p>নিশ্চিত: {{ $user->two_factor_confirmed_at ? 'হ্যাঁ' : 'এসএমএস মোড / অপেক্ষমাণ' }}</p>
    <p>ফোন: {{ $user->phone }}</p>
  </div>
</div>
@endsection
