@extends('layouts.app', ['title' => 'ওয়াক-ইন সিরিয়াল', 'active' => 'walkin', 'staff' => true])
@section('content')
<h1>চেম্বারে এসে সিরিয়াল</h1>
<form method="get" class="inline-form"><label>তারিখ<input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()"></label></form>
@if(!$sch)
  <div class="alert err">এই দিন চেম্বার নেই।</div>
@else
<form method="post" action="{{ route('staff.walkin.store') }}" class="split">
  @csrf
  <input type="hidden" name="date" value="{{ $date }}">
  <div class="card form">
    <label>নাম<input name="name" required></label>
    <label>মোবাইল<input name="phone" required placeholder="আগের রোগী হলে শুধু নম্বর"></label>
    <div class="row-2">
      <label>বয়স<input type="number" name="age"></label>
      <label>লিঙ্গ<select name="gender"><option value="">—</option><option>পুরুষ</option><option>নারী</option></select></label>
    </div>
    <button class="btn" type="submit">পরের ফাঁকা সিরিয়াল দিন</button>
  </div>
  <div class="card">
    <h3>নির্দিষ্ট সিরিয়াল (ঐচ্ছিক)</h3>
    <div class="slots">
      @foreach($slots as $s)
        <label class="slot {{ $s['available'] ? '' : 'taken' }}">
          <input type="radio" name="serial" value="{{ (int)$s['serial'] }}" {{ $s['available'] ? '' : 'disabled' }}>
          <b>{{ e_bn($s['serial']) }}</b>
          <span>{{ format_time_12($s['scheduled_time']) }}</span>
        </label>
      @endforeach
    </div>
  </div>
</form>
@endif
@endsection
