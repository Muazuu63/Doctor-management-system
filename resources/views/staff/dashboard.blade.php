@extends('layouts.app', ['title' => 'ড্যাশবোর্ড', 'active' => 'dash', 'staff' => true])
@section('content')
<h1>স্বাগতম, {{ auth()->user()->displayName() }}</h1>
<p class="muted">রোল: {{ auth()->user()->roleLabel() }}</p>
<div class="stat-row cards">
  <div><b>{{ e_bn($apts->count()) }}</b><span>আজকের সিরিয়াল</span></div>
  <div><b>{{ e_bn($counts['done']) }}</b><span>দেখা হয়েছে</span></div>
  <div><b>{{ e_bn($counts['booked']+$counts['waiting']) }}</b><span>বাকি</span></div>
  <div><b>{{ $delay ? e_bn($delay).' মি' : '০' }}</b><span>বর্তমান দেরি</span></div>
</div>

<div class="split">
  <div class="card">
    <h2>দেরি / রি-শিডিউল</h2>
    <p>চেম্বার দেরিতে শুরু হলে মিনিট দিন। সব রোগীর সময় সরে যাবে এবং এসএমএস যাবে।</p>
    <form method="post" action="{{ route('staff.delay') }}" class="form">
      @csrf
      <input type="hidden" name="date" value="{{ $today }}">
      <label>দেরি (মিনিট)<input name="minutes" type="number" min="0" max="240" value="{{ (int)$delay }}" required></label>
      <label>কারণ<input name="reason" placeholder="যেমন: হাসপাতালে জরুরি"></label>
      <button class="btn" type="submit">সবাইকে নতুন সময় পাঠান</button>
    </form>
  </div>
  <div class="card">
    <h2>আজকের চেম্বার</h2>
    @if(!$sch)
      <p>আজ চেম্বার সেট নেই। <a href="{{ route('staff.schedule') }}">সময়সূচি</a> আপডেট করুন।</p>
    @else
      <p>{{ $sch['title'] }}<br>{{ format_time_12($sch['start_time']) }} – {{ format_time_12($sch['end_time']) }}</p>
      <a class="btn" href="{{ route('staff.queue') }}">কিউ খুলুন</a>
    @endif
    <h3 style="margin-top:24px">সাম্প্রতিক দিন</h3>
    <ul class="plain">
      @foreach($week as $w)
        <li>{{ bangla_date($w->appointment_date) }} — {{ e_bn((int)$w->c) }} জন</li>
      @endforeach
    </ul>
  </div>
</div>
@endsection
