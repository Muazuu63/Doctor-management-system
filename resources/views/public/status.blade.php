@extends('layouts.app', ['title' => 'সিরিয়াল স্ট্যাটাস', 'active' => 'status'])
@section('content')
<h1>আমার সিরিয়াল</h1>
<form method="get" class="inline-form card">
  <label>মোবাইল নম্বর<input name="phone" value="{{ $phone }}" placeholder="01XXXXXXXXX" required></label>
  <button class="btn" type="submit">খুঁজুন</button>
</form>

@if($delay)
  <div class="alert warn">আজ ডাক্তার {{ e_bn($delay) }} মিনিট দেরি করবেন। আপনার সময় আপডেট হয়েছে।</div>
@endif
@if($nowServing)
  <div class="now-serving">এখন চেম্বারে সিরিয়াল {{ e_bn($nowServing->serial_no) }} · {{ format_time_12($nowServing->scheduled_time) }}</div>
@endif

@if($phone && !$patient)
  <div class="alert err">এই নম্বরে কোনো রোগী পাওয়া যায়নি।</div>
@endif

@foreach($rows as $r)
  <div class="card ticket-mini">
    <div>
      <p class="serial-big">সিরিয়াল {{ e_bn($r->serial_no) }}</p>
      <p>{{ bangla_date($r->appointment_date->format('Y-m-d')) }} · {{ format_time_12($r->scheduled_time) }}</p>
      <p>স্ট্যাটাস: <b>{{ status_bn($r->status) }}</b></p>
    </div>
    <a class="btn ghost" href="{{ route('ticket', $r->token) }}">টিকিট</a>
  </div>
@endforeach
@endsection
