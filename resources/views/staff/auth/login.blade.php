@extends('layouts.app', ['title' => 'স্টাফ লগইন'])
@section('content')
<form method="post" action="{{ route('staff.login.attempt') }}" class="card form" style="max-width:420px;margin:40px auto">
  @csrf
  <h1>স্টাফ লগইন</h1>
  <p class="muted">সুপার অ্যাডমিন / অ্যাডমিন / ম্যানেজার। লগইনের পর এসএমএস বা Authenticator কোড লাগবে।</p>
  <label>ফোন বা ইমেইল<input name="login" required value="{{ old('login') }}" placeholder="01XXXXXXXXX"></label>
  <label>পাসওয়ার্ড<input name="password" type="password" required></label>
  <label class="check"><input type="checkbox" name="remember" value="1"> মনে রাখুন</label>
  <button class="btn" type="submit">ঢুকুন</button>
</form>
@endsection
