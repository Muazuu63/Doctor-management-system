@extends('layouts.app', ['title' => 'ডাক্তার পরিচিতি', 'active' => 'about'])
@section('content')
<section class="about">
  <div class="card">
    <p class="eyebrow">{{ $doc?->specialty }}</p>
    <h1>{{ $doc?->displayName() }}</h1>
    <p>{{ $doc?->degree }}</p>
    <p>{{ $doc?->bio }}</p>
    <p>ফি: {{ e_bn((int)($doc?->consultation_fee ?? 0)) }} টাকা · স্লট {{ e_bn((int)($doc?->slot_minutes ?? 20)) }} মিনিট</p>
    <a class="btn" href="{{ route('book') }}">সিরিয়াল নিন</a>
  </div>
  <div class="card">
    <h2>সাপ্তাহিক চেম্বার</h2>
    <table class="table">
      <tr><th>দিন</th><th>সময়</th><th>সিরিয়াল</th></tr>
      @foreach($schedules as $s)
        <tr>
          <td>{{ bangla_day((int)$s->day_of_week) }}</td>
          <td>{{ format_time_12($s->start_time) }} – {{ format_time_12($s->end_time) }}</td>
          <td>{{ e_bn((int)$s->max_serial) }}</td>
        </tr>
      @endforeach
    </table>
    <p class="muted">{{ clinic_setting('clinic_address') }}<br>ফোন: {{ clinic_setting('clinic_phone') }}</p>
  </div>
</section>
@endsection
