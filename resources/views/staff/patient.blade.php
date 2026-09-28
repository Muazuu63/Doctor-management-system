@extends('layouts.app', ['title' => 'রোগীর ফাইল', 'active' => 'patients', 'staff' => true])
@section('content')
<h1>{{ $patient->name }}</h1>
<p>{{ $patient->phone }} · পিন {{ $patient->pin }}</p>
<div class="split">
  <form method="post" action="{{ route('staff.patients.update', $patient) }}" class="card form">
    @csrf
    <label>নাম<input name="name" value="{{ $patient->name }}"></label>
    <div class="row-2">
      <label>বয়স<input type="number" name="age" value="{{ (int)$patient->age }}"></label>
      <label>লিঙ্গ<input name="gender" value="{{ $patient->gender }}"></label>
    </div>
    <label>রক্তের গ্রুপ<input name="blood_group" value="{{ $patient->blood_group }}"></label>
    <label>ঠিকানা<input name="address" value="{{ $patient->address }}"></label>
    <button class="btn" type="submit">সেভ</button>
  </form>
  <div class="card">
    <h2>ভিজিট হিস্ট্রি</h2>
    <ul class="plain">
      @foreach($hist as $h)
        <li>
          {{ bangla_date($h->appointment_date->format('Y-m-d')) }} · সিরিয়াল {{ e_bn($h->serial_no) }} · {{ status_bn($h->status) }}
          @if($h->prescription)
            · <a href="{{ route('prescription.show', $h->prescription) }}">Rx</a>
          @elseif($h->status !== 'cancelled')
            · <a href="{{ route('staff.prescribe', $h) }}">লিখুন</a>
          @endif
        </li>
      @endforeach
    </ul>
  </div>
</div>
<h2>প্রেসক্রিপশন</h2>
@foreach($rxs as $r)
  @php $meds = $r->medicines ?: []; @endphp
  <article class="card">
    <h3>{{ bangla_date($r->appointment->appointment_date->format('Y-m-d')) }} · {{ $r->diagnosis }}</h3>
    <p>{{ $r->complaints }}</p>
    <ul>@foreach($meds as $m)<li>{{ $m['name'] ?? '' }} — {{ $m['dose'] ?? '' }}</li>@endforeach</ul>
    <a class="btn ghost sm" href="{{ route('prescription.show', $r) }}">প্রিন্ট</a>
  </article>
@endforeach
@endsection
