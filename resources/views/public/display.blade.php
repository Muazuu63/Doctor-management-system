<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="refresh" content="15">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ডিসপ্লে | {{ clinic_name() }}</title>
  <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@600;700&family=Noto+Sans+Bengali:wght@400;600;700&family=Noto+Sans:wght@700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}?v=4">
  <style>
    body { background: #072821; color: #fff; }
    .disp { min-height: 100vh; padding: 32px; }
    .now { font-size: clamp(42px, 8vw, 96px); margin: 10px 0; color: #e8b86d; }
    .next { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-top: 28px; }
    .next div { background: #0b3d34; padding: 16px; border-radius: 16px; }
  </style>
</head>
<body>
  <div class="disp">
    <p>{{ clinic_name() }} · {{ $doc?->displayName() }}</p>
    <h1>এখন চেম্বারে</h1>
    @if($now)
      <div class="now">সিরিয়াল {{ e_bn($now->serial_no) }}</div>
      <p>{{ $now->patient->name }} · {{ format_time_12($now->scheduled_time) }}</p>
    @else
      <div class="now">—</div>
      <p>অপেক্ষা করুন</p>
    @endif
    @if($delay)<p>ডাক্তার {{ e_bn($delay) }} মিনিট দেরি করছেন</p>@endif
    <h2>পরবর্তী</h2>
    <div class="next">
      @foreach($next as $n)
        <div>
          <b>সিরিয়াল {{ e_bn($n->serial_no) }}</b>
          <div>{{ format_time_12($n->scheduled_time) }}</div>
        </div>
      @endforeach
    </div>
  </div>
</body>
</html>
