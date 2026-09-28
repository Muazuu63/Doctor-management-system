@extends('layouts.app', ['title' => 'ইউজার', 'active' => 'users', 'staff' => true])
@section('content')
<h1>স্টাফ ইউজার</h1>
<p>৩টি রোল: সুপার অ্যাডমিন, অ্যাডমিন, ম্যানেজার। পরে আরও রোল যোগ করা যাবে।</p>
<div class="table-wrap card">
<table class="table">
  <tr><th>নাম</th><th>ফোন</th><th>ইমেইল</th><th>রোল</th><th>স্ট্যাটাস</th><th></th></tr>
  @foreach($rows as $r)
    <tr>
      <td>{{ $r->displayName() }}</td>
      <td>{{ $r->phone }}</td>
      <td>{{ $r->email }}</td>
      <td>{{ $r->roleLabel() }}</td>
      <td>{{ $r->is_active ? 'সক্রিয়' : 'বন্ধ' }}</td>
      <td>
        <form method="post" action="{{ route('staff.users.update', $r) }}" class="acts">
          @csrf
          <input name="name" value="{{ $r->name }}" style="width:140px">
          <select name="role">
            <option value="super_admin" {{ $r->role === 'super_admin' ? 'selected' : '' }}>সুপার অ্যাডমিন</option>
            <option value="admin" {{ $r->role === 'admin' ? 'selected' : '' }}>অ্যাডমিন</option>
            <option value="manager" {{ $r->role === 'manager' ? 'selected' : '' }}>ম্যানেজার</option>
          </select>
          <label class="check"><input type="checkbox" name="is_active" {{ $r->is_active ? 'checked' : '' }}> সক্রিয়</label>
          <input type="password" name="password" placeholder="নতুন পাসওয়ার্ড" minlength="8">
          <button class="btn sm" type="submit">আপডেট</button>
        </form>
      </td>
    </tr>
  @endforeach
</table>
</div>
<form method="post" action="{{ route('staff.users.store') }}" class="card form" style="max-width:560px;margin-top:24px">
  @csrf
  <h2>নতুন ইউজার</h2>
  <label>নাম<input name="name" required></label>
  <label>বাংলা নাম<input name="name_bn"></label>
  <label>ইমেইল<input type="email" name="email" required></label>
  <label>ফোন<input name="phone" required placeholder="01XXXXXXXXX"></label>
  <label>পাসওয়ার্ড<input type="password" name="password" required minlength="8"></label>
  <label>রোল
    <select name="role">
      <option value="manager">ম্যানেজার</option>
      <option value="admin">অ্যাডমিন</option>
      <option value="super_admin">সুপার অ্যাডমিন</option>
    </select>
  </label>
  <button class="btn" type="submit">তৈরি করুন</button>
</form>
@endsection
