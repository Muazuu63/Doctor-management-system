@extends('layouts.app', ['title' => 'সিরিয়াল নিন', 'active' => 'book'])
@section('content')
<h1>অনলাইন সিরিয়াল</h1>
<p class="lead">তারিখ বাছুন, ফাঁকা সিরিয়াল সিলেক্ট করুন। বুকিংয়ের সাথেই আপনার সময় এসএমএস যাবে।</p>

<div class="chip-row">
  @foreach($dates as $d)
    <a class="chip {{ $d['date'] === $date ? 'on' : '' }}" href="{{ route('book', ['date' => $d['date']]) }}">
      {{ $d['day'] }}<br><small>{{ $d['label'] }}</small>
    </a>
  @endforeach
</div>

@if(!$sch)
  <div class="alert err">এই তারিখে চেম্বার নেই। অন্য দিন বাছুন।</div>
@else
  <div class="info-bar">
    <span>{{ $sch['title'] }}</span>
    <span>{{ format_time_12($sch['start_time']) }} – {{ format_time_12($sch['end_time']) }}</span>
    <span>স্লট {{ e_bn((int)$sch['slot_minutes']) }} মিনিট</span>
    @if($delay)<span class="warn-inline">দেরি {{ e_bn($delay) }} মিনিট — সময় আপডেটেড</span>@endif
  </div>

  <form method="post" action="{{ route('book.store') }}" class="book-grid">
    @csrf
    <input type="hidden" name="date" value="{{ $date }}">
    <div class="card">
      <h3>রোগীর তথ্য</h3>
      <label>নাম *<input name="name" required placeholder="পূর্ণ নাম" value="{{ old('name') }}"></label>
      <label>মোবাইল *<input name="phone" required placeholder="01XXXXXXXXX" value="{{ old('phone') }}"></label>
      <div class="row-2">
        <label>বয়স<input name="age" type="number" min="0" max="120" value="{{ old('age') }}"></label>
        <label>লিঙ্গ
          <select name="gender">
            <option value="">নির্বাচন</option>
            <option {{ old('gender') === 'পুরুষ' ? 'selected' : '' }}>পুরুষ</option>
            <option {{ old('gender') === 'নারী' ? 'selected' : '' }}>নারী</option>
            <option {{ old('gender') === 'অন্যান্য' ? 'selected' : '' }}>অন্যান্য</option>
          </select>
        </label>
      </div>
      <label>সংক্ষিপ্ত সমস্যা (ঐচ্ছিক)<input name="notes" placeholder="যেমন: জ্বর, কাশি" value="{{ old('notes') }}"></label>
      <p class="muted">আগের রোগী হলে একই নম্বরে হিস্ট্রি ও প্রেসক্রিপশন জুড়ে যাবে।</p>
    </div>
    <div class="card">
      <h3>সিরিয়াল বাছুন</h3>
      <div class="slots">
        @foreach($slots as $s)
          <label class="slot {{ $s['available'] ? '' : 'taken' }}">
            <input type="radio" name="serial" value="{{ (int)$s['serial'] }}" {{ $s['available'] ? '' : 'disabled' }} required>
            <b>{{ e_bn($s['serial']) }}</b>
            <span>{{ format_time_12($s['scheduled_time']) }}</span>
            @if($s['scheduled_time'] !== $s['original_time'])
              <small>আসল {{ format_time_12($s['original_time']) }}</small>
            @endif
          </label>
        @endforeach
      </div>
      <button class="btn" type="submit">সিরিয়াল নিশ্চিত করুন</button>
    </div>
  </form>
@endif
@endsection
