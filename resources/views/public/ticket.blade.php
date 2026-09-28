@extends('layouts.app', ['title' => 'সিরিয়াল কনফার্ম', 'active' => 'book'])
@section('content')
<div class="ticket card">
  <p class="eyebrow">{{ clinic_name() }}</p>
  <h1>সিরিয়াল নিশ্চিত</h1>
  <p class="serial-big">সিরিয়াল {{ e_bn($apt->serial_no) }}</p>
  <ul class="meta">
    <li><span>রোগী</span><b>{{ $apt->patient->name }}</b></li>
    <li><span>ফোন</span><b>{{ $apt->patient->phone }}</b></li>
    <li><span>তারিখ</span><b>{{ bangla_date($apt->appointment_date->format('Y-m-d')) }}</b></li>
    <li><span>সময়</span><b>{{ format_time_12($apt->scheduled_time) }}</b></li>
    <li><span>ডাক্তার</span><b>{{ $doc?->displayName() }}</b></li>
    <li><span>পোর্টাল পিন</span><b>{{ $apt->patient->pin }}</b></li>
  </ul>
  <div class="alert ok">এই সময়ে আসুন। অনেক আগে এসে বসে থাকবেন না। দেরি হলে নতুন সময় এসএমএস যাবে।</div>
  <p class="muted">প্রেসক্রিপশন দেখতে পোর্টালে ফোন ও পিন দিন। পিন সংরক্ষণ করুন।</p>
  <div class="cta-row">
    <a class="btn" href="{{ route('status', ['phone' => $apt->patient->phone]) }}">স্ট্যাটাস দেখুন</a>
    <button class="btn ghost" type="button" onclick="window.print()">প্রিন্ট</button>
  </div>
</div>
@endsection
