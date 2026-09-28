<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $title ?? 'হোম' }} | {{ clinic_name() }}</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@500;600;700&family=Noto+Sans+Bengali:wght@400;500;600;700&family=Noto+Sans:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}?v=4">
  @stack('head')
</head>
<body class="@isset($staff) staff-body @endisset">
@isset($staff)
<aside class="staff-sidebar" id="staffSidebar">
  <a class="brand sidebar-brand" href="{{ url('/') }}">
    <span class="logo">+</span>
    <span>
      <strong>{{ clinic_name() }}</strong>
      <small>{{ auth()->user()->roleLabel() }}</small>
    </span>
  </a>
  <nav class="staff-nav" id="navLinks">
    <a class="{{ ($active ?? '') === 'dash' ? 'on' : '' }}" href="{{ route('staff.dashboard') }}">ড্যাশবোর্ড</a>
    <a class="{{ ($active ?? '') === 'queue' ? 'on' : '' }}" href="{{ route('staff.queue') }}">আজকের কিউ</a>
    <a class="{{ ($active ?? '') === 'walkin' ? 'on' : '' }}" href="{{ route('staff.walkin') }}">ওয়াক-ইন</a>
    <a class="{{ ($active ?? '') === 'schedule' ? 'on' : '' }}" href="{{ route('staff.schedule') }}">সময়সূচি</a>
    <a class="{{ ($active ?? '') === 'patients' ? 'on' : '' }}" href="{{ route('staff.patients') }}">রোগী</a>
    @if(auth()->user()->canManageSms())
      <a class="{{ ($active ?? '') === 'sms' ? 'on' : '' }}" href="{{ route('staff.sms') }}">এসএমএস</a>
    @endif
    @if(auth()->user()->canManageSettings())
      <a class="{{ ($active ?? '') === 'settings' ? 'on' : '' }}" href="{{ route('staff.settings') }}">সেটিংস</a>
    @endif
    @if(auth()->user()->canManageUsers())
      <a class="{{ ($active ?? '') === 'users' ? 'on' : '' }}" href="{{ route('staff.users') }}">ইউজার</a>
    @endif
    <a class="{{ ($active ?? '') === 'security' ? 'on' : '' }}" href="{{ route('staff.security') }}">২এফএ</a>
    <form method="post" action="{{ route('staff.logout') }}">
      @csrf
      <button class="sidebar-logout" type="submit">লগআউট</button>
    </form>
  </nav>
</aside>
<div class="staff-wrap">
  <header class="staff-top">
    <button class="menu-btn" id="menuBtn" type="button" aria-label="মেনু">☰</button>
    <span>{{ $title ?? 'ড্যাশবোর্ড' }}</span>
  </header>
  <main class="staff-main">
@else
<header class="topbar">
  <div class="wrap nav">
    <a class="brand" href="{{ url('/') }}">
      <span class="logo">+</span>
      <span>
        <strong>{{ clinic_name() }}</strong>
        <small>{{ clinic_setting('clinic_tagline') }}</small>
      </span>
    </a>
    <button class="menu-btn" id="menuBtn" type="button" aria-label="মেনু">☰</button>
    <nav id="navLinks">
      <a class="{{ ($active ?? '') === 'home' ? 'on' : '' }}" href="{{ route('home') }}">হোম</a>
      <a class="{{ ($active ?? '') === 'book' ? 'on' : '' }}" href="{{ route('book') }}">সিরিয়াল নিন</a>
      <a class="{{ ($active ?? '') === 'status' ? 'on' : '' }}" href="{{ route('status') }}">সিরিয়াল দেখুন</a>
      <a class="{{ ($active ?? '') === 'portal' ? 'on' : '' }}" href="{{ route('portal') }}">প্রেসক্রিপশন</a>
      <a class="{{ ($active ?? '') === 'about' ? 'on' : '' }}" href="{{ route('about') }}">ডাক্তার</a>
      <a class="btn-nav" href="{{ route('staff.login') }}">স্টাফ লগইন</a>
    </nav>
  </div>
</header>
<main class="wrap">
@endisset
  @if(session('ok')) <div class="alert ok">{{ session('ok') }}</div> @endif
  @if(session('err')) <div class="alert err">{{ session('err') }}</div> @endif
  @if($errors->any())
    <div class="alert err">{{ $errors->first() }}</div>
  @endif
  @yield('content')
</main>
@isset($staff)
</div>
@else
<footer class="footer">
  <div class="wrap footer-grid">
    <div>
      <strong>{{ clinic_name() }}</strong>
      <p>{{ clinic_setting('clinic_address') }}</p>
      <p>ফোন: {{ clinic_setting('clinic_phone') }}</p>
    </div>
    <div>
      <p>সিরিয়াল ওয়েবসাইট থেকে নিন। সময়মতো আসুন, অপেক্ষা কমান।</p>
      <p class="muted">প্রতি রোগী {{ e_bn(clinic_setting('slot_minutes', 20)) }} মিনিট</p>
    </div>
  </div>
</footer>
@endisset
<script src="{{ asset('js/app.js') }}?v=4"></script>
@stack('scripts')
</body>
</html>
