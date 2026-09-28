@extends('layouts.app', ['title' => 'আজকের কিউ', 'active' => 'queue', 'staff' => true])
@section('content')
<div class="toolbar">
  <h1>কিউ · {{ bangla_date($date) }}</h1>
  <form method="get"><input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()"></form>
  <a class="btn ghost" href="{{ route('staff.walkin', ['date' => $date]) }}">ওয়াক-ইন</a>
  <a class="btn ghost" href="{{ route('display') }}" target="_blank">ওয়েটিং ডিসপ্লে</a>
</div>
@if($delay)<div class="alert warn">দেরি {{ e_bn($delay) }} মিনিট প্রয়োগ করা আছে।</div>@endif

<div class="table-wrap">
<table class="table">
  <tr>
    <th>#</th><th>সময়</th><th>রোগী</th><th>ফোন</th><th>স্ট্যাটাস</th><th>অ্যাকশন</th>
  </tr>
  @foreach($rows as $r)
    <tr class="st-{{ $r->status }}">
      <td>{{ e_bn($r->serial_no) }}</td>
      <td>{{ format_time_12($r->scheduled_time) }}</td>
      <td>
        <a href="{{ route('staff.patients.show', $r->patient) }}">{{ $r->patient->name }}</a>
        <small>{{ $r->patient->gender }} {{ $r->patient->age ? e_bn($r->patient->age).' বছর' : '' }}</small>
      </td>
      <td>{{ $r->patient->phone }}</td>
      <td>{{ status_bn($r->status) }}</td>
      <td class="acts">
        <form method="post" action="{{ route('staff.queue.update', $r) }}">
          @csrf
          @if($r->status === 'booked')
            <button name="action" value="waiting">চেক-ইন</button>
          @endif
          @if(in_array($r->status, ['booked','waiting'], true))
            <button name="action" value="in_chamber">চেম্বারে</button>
          @endif
          @if($r->status === 'in_chamber')
            <a class="btn sm" href="{{ route('staff.prescribe', $r) }}">প্রেসক্রিপশন</a>
            <button name="action" value="done">সম্পন্ন</button>
          @endif
          @if($r->prescription)
            <a class="btn sm ghost" href="{{ route('staff.prescribe', $r) }}">Rx</a>
          @elseif($r->status === 'done')
            <a class="btn sm" href="{{ route('staff.prescribe', $r) }}">প্রেসক্রিপশন</a>
          @endif
          @if(!in_array($r->status, ['done','cancelled'], true))
            <button name="action" value="no_show">আসেননি</button>
            <button name="action" value="cancel" class="danger">বাতিল</button>
          @endif
        </form>
      </td>
    </tr>
  @endforeach
  @if($rows->isEmpty())<tr><td colspan="6">এই তারিখে সিরিয়াল নেই।</td></tr>@endif
</table>
</div>
<p class="muted">নতুন ট্যাবে কিউ অটো-রিফ্রেশ হয় প্রতি ৪০ সেকেন্ডে।</p>
@push('scripts')
<script>setTimeout(()=>location.reload(), 40000);</script>
@endpush
@endsection
