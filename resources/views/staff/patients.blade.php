@extends('layouts.app', ['title' => 'রোগী', 'active' => 'patients', 'staff' => true])
@section('content')
<h1>রোগী খুঁজুন</h1>
<form method="get" class="inline-form card">
  <label>নাম বা ফোন (পরের ভিজিটে শুধু নম্বর দিলেই হিস্ট্রি উঠবে)
    <input name="q" value="{{ $q }}" placeholder="01XXXXXXXXX" autofocus>
  </label>
  <button class="btn" type="submit">খুঁজুন</button>
</form>
<div class="table-wrap">
<table class="table">
  <tr><th>নাম</th><th>ফোন</th><th>বয়স</th><th>লিঙ্গ</th><th></th></tr>
  @foreach($rows as $r)
    <tr>
      <td>{{ $r->name }}</td>
      <td>{{ $r->phone }}</td>
      <td>{{ e_bn((int)$r->age) }}</td>
      <td>{{ $r->gender }}</td>
      <td><a href="{{ route('staff.patients.show', $r) }}">ফাইল</a></td>
    </tr>
  @endforeach
</table>
</div>
@endsection
