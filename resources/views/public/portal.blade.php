@extends('layouts.app', ['title' => 'রোগী পোর্টাল', 'active' => 'portal'])
@section('content')
<h1>প্রেসক্রিপশন ও হিস্ট্রি</h1>
@if(!$patient)
  <form method="post" action="{{ route('portal.login') }}" class="card form" style="max-width:420px">
    @csrf
    <label>মোবাইল<input name="phone" required placeholder="01XXXXXXXXX"></label>
    <label>পিন (৪ অঙ্ক)<input name="pin" required></label>
    <button class="btn" type="submit">ঢুকুন</button>
    <p class="muted">পিন সিরিয়াল কনফার্ম পেজে দেওয়া হয়। হারিয়ে গেলে ক্লিনিকে ফোন করুন।</p>
  </form>
@else
  <p>{{ $patient->name }} · {{ $patient->phone }} <a href="{{ route('portal.logout') }}">লগআউট</a></p>
  @if($records->isEmpty()) <p>এখনো কোনো প্রেসক্রিপশন নেই।</p> @endif
  @foreach($records as $r)
    @php $meds = $r->medicines ?: []; @endphp
    <article class="card rx-card">
      <h3>{{ bangla_date($r->appointment->appointment_date->format('Y-m-d')) }} · সিরিয়াল {{ e_bn($r->appointment->serial_no) }}</h3>
      <p><b>রোগ নির্ণয়:</b> {{ $r->diagnosis }}</p>
      <p><b>অভিযোগ:</b> {{ $r->complaints }}</p>
      @if($meds)
        <table class="table">
          <tr><th>ওষুধ</th><th>মাত্রা</th><th>সময়</th><th>নির্দেশ</th></tr>
          @foreach($meds as $m)
            <tr><td>{{ $m['name'] ?? '' }}</td><td>{{ $m['dose'] ?? '' }}</td><td>{{ $m['duration'] ?? '' }}</td><td>{{ $m['instruction'] ?? '' }}</td></tr>
          @endforeach
        </table>
      @endif
      @if($r->tests)<p><b>পরীক্ষা:</b> {{ $r->tests }}</p>@endif
      @if($r->advice)<p><b>পরামর্শ:</b> {{ $r->advice }}</p>@endif
      <a class="btn ghost" href="{{ route('prescription.show', $r) }}">প্রিন্ট</a>
    </article>
  @endforeach
@endif
@endsection
