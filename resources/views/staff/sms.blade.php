@extends('layouts.app', ['title' => 'এসএমএস', 'active' => 'sms', 'staff' => true])
@section('content')
<h1>এসএমএস গেটওয়ে</h1>
<p>ডিফল্টে মেসেজ লগে জমা হয়। cPanel-এ BulkSMSBD / SSL Wireless API URL ও কি দিন। বুকিং হলে রোগীর নম্বরে স্লট সময় যাবে।</p>
<form method="post" action="{{ route('staff.sms.update') }}" class="card form">
  @csrf
  <label>প্রোভাইডার
    <select name="sms_provider">
      <option value="log" {{ clinic_setting('sms_provider') === 'log' ? 'selected' : '' }}>শুধু লগ (টেস্ট)</option>
      <option value="http" {{ clinic_setting('sms_provider') === 'http' ? 'selected' : '' }}>HTTP API</option>
    </select>
  </label>
  <label>API URL<input name="sms_api_url" value="{{ clinic_setting('sms_api_url') }}" placeholder="https://bulksmsbd.net/api/smsapi"></label>
  <label>API Key<input name="sms_api_key" value="{{ clinic_setting('sms_api_key') }}"></label>
  <label>Sender ID<input name="sms_sender" value="{{ clinic_setting('sms_sender', 'MediSeba') }}"></label>
  <label>মেথড
    <select name="sms_http_method">
      <option>GET</option>
      <option {{ clinic_setting('sms_http_method') === 'POST' ? 'selected' : '' }}>POST</option>
    </select>
  </label>
  <label>রিমাইন্ডার কত মিনিট আগে<input type="number" name="reminder_minutes" value="{{ clinic_setting('reminder_minutes', 30) }}"></label>
  <button class="btn" type="submit">সেভ</button>
</form>
<form method="post" action="{{ route('staff.sms.test') }}" class="inline-form card">
  @csrf
  <label>টেস্ট ফোন<input name="test_phone" placeholder="01XXXXXXXXX" required></label>
  <button class="btn ghost" type="submit">টেস্ট পাঠান</button>
</form>
<p class="muted">ক্রন কি: <code>{{ clinic_setting('cron_key') }}</code> · URL: <code>{{ url('/cron/remind') }}?key={{ clinic_setting('cron_key') }}</code></p>
<h2>লগ</h2>
<div class="table-wrap">
<table class="table">
  <tr><th>সময়</th><th>ফোন</th><th>টাইপ</th><th>স্ট্যাটাস</th><th>মেসেজ</th></tr>
  @foreach($logs as $l)
    <tr>
      <td>{{ $l->created_at }}</td>
      <td>{{ $l->phone }}</td>
      <td>{{ $l->type }}</td>
      <td>{{ $l->status }}</td>
      <td>{{ \Illuminate\Support\Str::limit($l->message, 80) }}</td>
    </tr>
  @endforeach
</table>
</div>
@endsection
