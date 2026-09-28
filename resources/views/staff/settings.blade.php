@extends('layouts.app', ['title' => 'সেটিংস', 'active' => 'settings', 'staff' => true])
@section('content')
<h1>ক্লিনিক ও ডাক্তার সেটিংস</h1>
<form method="post" action="{{ route('staff.settings.update') }}" class="split">
  @csrf
  <div class="card form">
    <h2>ক্লিনিক</h2>
    <label>নাম<input name="clinic_name" value="{{ clinic_setting('clinic_name') }}"></label>
    <label>ট্যাগলাইন<input name="clinic_tagline" value="{{ clinic_setting('clinic_tagline') }}"></label>
    <label>ঠিকানা<input name="clinic_address" value="{{ clinic_setting('clinic_address') }}"></label>
    <label>ফোন<input name="clinic_phone" value="{{ clinic_setting('clinic_phone') }}"></label>
    <label>ইমেইল<input name="clinic_email" value="{{ clinic_setting('clinic_email') }}"></label>
    <label>ডিফল্ট স্লট (মিনিট)<input type="number" name="slot_minutes" value="{{ clinic_setting('slot_minutes', 20) }}"></label>
  </div>
  <div class="card form">
    <h2>ডাক্তার প্রোফাইল</h2>
    <label>নাম<input name="name" value="{{ $doc?->name }}"></label>
    <label>বাংলা নাম<input name="name_bn" value="{{ $doc?->name_bn }}"></label>
    <label>ডিগ্রি<input name="degree" value="{{ $doc?->degree }}"></label>
    <label>বিশেষত্ব<input name="specialty" value="{{ $doc?->specialty }}"></label>
    <label>ফোন<input name="phone" value="{{ $doc?->phone }}"></label>
    <label>ইমেইল<input name="email" value="{{ $doc?->email }}"></label>
    <label>ফি<input type="number" name="consultation_fee" value="{{ $doc?->consultation_fee }}"></label>
    <label>স্লট মিনিট<input type="number" name="doc_slot" value="{{ $doc?->slot_minutes }}"></label>
    <label>বায়ো<textarea name="bio">{{ $doc?->bio }}</textarea></label>
    <label>আপনার নতুন পাসওয়ার্ড (খালি রাখলে বদলাবে না)<input type="password" name="password" minlength="8"></label>
    <button class="btn" type="submit">সেভ</button>
  </div>
</form>
@endsection
