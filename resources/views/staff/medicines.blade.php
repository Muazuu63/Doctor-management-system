@extends('layouts.app', ['title' => 'ওষুধ টেমপ্লেট', 'active' => 'queue', 'staff' => true])
@section('content')
<h1>প্রেসক্রিপশন টেমপ্লেট</h1>
<form method="post" action="{{ route('staff.medicines.store') }}" class="card form">
  @csrf
  <div class="med-row">
    <input name="name" placeholder="ওষুধের নাম" required>
    <input name="dose" placeholder="১+০+১">
    <input name="duration" placeholder="৭ দিন">
    <input name="instruction" placeholder="খাওয়ার পর">
  </div>
  <button class="btn" type="submit">যোগ</button>
</form>
<table class="table">
  <tr><th>নাম</th><th>মাত্রা</th><th>মেয়াদ</th><th>নির্দেশ</th></tr>
  @foreach($rows as $r)
    <tr><td>{{ $r->name }}</td><td>{{ $r->dose }}</td><td>{{ $r->duration }}</td><td>{{ $r->instruction }}</td></tr>
  @endforeach
</table>
@endsection
