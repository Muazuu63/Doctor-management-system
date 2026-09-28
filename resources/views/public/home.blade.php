@extends('layouts.app', ['title' => 'হোম', 'active' => 'home'])
@section('content')
<section class="hero">
  <div>
    <p class="eyebrow">অনলাইন সিরিয়াল · সময়মতো সেবা</p>
    <h1>{{ $doc?->displayName() }}</h1>
    <p class="lead">{{ $doc?->degree }} · {{ $doc?->specialty }}</p>
    <p>{{ $doc?->bio }}</p>
    <div class="cta-row">
      <a class="btn" href="{{ route('book') }}">এখনই সিরিয়াল নিন</a>
      <a class="btn ghost" href="{{ route('status') }}">আমার সিরিয়াল</a>
    </div>
  </div>
  <div class="hero-card">
    <h3>আজকের চেম্বার</h3>
    @if(!$todaySch)
      <p>আজ চেম্বার বন্ধ। পরবর্তী দিন থেকে সিরিয়াল নিন।</p>
    @else
      <p class="big-time">{{ format_time_12($todaySch['start_time']) }} – {{ format_time_12($todaySch['end_time']) }}</p>
      <p>প্রতি রোগী {{ e_bn((int)$todaySch['slot_minutes']) }} মিনিট</p>
      <div class="stat-row">
        <div><b>{{ e_bn($booked) }}</b><span>বুকড</span></div>
        <div><b>{{ e_bn($free) }}</b><span>ফাঁকা</span></div>
        <div><b>{{ $delay ? e_bn($delay).' মি' : 'না' }}</b><span>দেরি</span></div>
      </div>
      @if($delay)
        <p class="warn">ডাক্তার {{ e_bn($delay) }} মিনিট দেরি করবেন। সব সিরিয়াল সেই অনুযায়ী সরেছে।</p>
      @endif
    @endif
  </div>
</section>

<section class="how">
  <h2>কীভাবে কাজ করে</h2>
  <div class="grid-3">
    <article>
      <span class="step">১</span>
      <h3>ওয়েবসাইট থেকে সিরিয়াল</h3>
      <p>ফোন নম্বর দিয়ে রোগী চিনে নেওয়া হয়। তারিখ বেছে সিরিয়াল নিন। প্রথম সিরিয়াল চেম্বার শুরুর সময়, পরেরটি ১৫–২০ মিনিট পর।</p>
    </article>
    <article>
      <span class="step">২</span>
      <h3>এসএমএস-এ সঠিক সময়</h3>
      <p>বুকিংয়ের সাথেই মেসেজ যায়। সিরিয়াল ৫০ হলেও আপনি আপনার নির্দিষ্ট সময়ে আসবেন—আগে এসে বসে থাকতে হবে না।</p>
    </article>
    <article>
      <span class="step">৩</span>
      <h3>দেরি হলে নতুন সময়</h3>
      <p>ডাক্তার দেরি করলে সব সিরিয়াল অটো রি-শিডিউল হয় এবং প্রত্যেক রোগীকে নতুন সময় এসএমএস যায়।</p>
    </article>
  </div>
</section>

<section>
  <h2>আসন্ন চেম্বার</h2>
  <div class="date-grid">
    @foreach($dates as $d)
      <a class="date-card" href="{{ route('book', ['date' => $d['date']]) }}">
        <strong>{{ $d['day'] }}</strong>
        <span>{{ $d['label'] }}</span>
        <em>{{ format_time_12($d['start']) }} – {{ format_time_12($d['end']) }}</em>
        <small>{{ e_bn($d['free']) }} ফাঁকা / {{ e_bn($d['total']) }}</small>
      </a>
    @endforeach
    @if(!$dates) <p>পরবর্তী ১৪ দিনে কোনো চেম্বার নেই।</p> @endif
  </div>
</section>
@endsection
