<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <title>প্রেসক্রিপশন</title>
  <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@600;700&family=Noto+Sans+Bengali:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/print.css') }}">
</head>
<body>
  <div class="rx">
    <header>
      <div>
        <h1>{{ $doc?->displayName() }}</h1>
        <p>{{ $doc?->degree }} · {{ $doc?->specialty }}</p>
        <p>{{ clinic_name() }} · {{ clinic_setting('clinic_address') }}</p>
      </div>
      <div class="right">
        <p>তারিখ: {{ bangla_date($rx->appointment->appointment_date->format('Y-m-d')) }}</p>
        <p>সিরিয়াল: {{ e_bn($rx->appointment->serial_no) }}</p>
      </div>
    </header>
    <section class="patient">
      <span>নাম: <b>{{ $rx->patient->name }}</b></span>
      <span>বয়স: {{ e_bn((int)$rx->patient->age) }}</span>
      <span>লিঙ্গ: {{ $rx->patient->gender }}</span>
      <span>ফোন: {{ $rx->patient->phone }}</span>
    </section>
    <div class="rx-body">
      <div class="left-col">
        <h3>C/C</h3><p>{!! nl2br(e($rx->complaints)) !!}</p>
        <h3>O/E</h3><p>{!! nl2br(e($rx->findings)) !!}</p>
        <h3>Dx</h3><p>{{ $rx->diagnosis }}</p>
      </div>
      <div class="right-col">
        <div class="rx-symbol">Rx</div>
        <table>
          @foreach($meds as $i => $m)
            <tr>
              <td>{{ e_bn($i+1) }}.</td>
              <td>
                <b>{{ $m['name'] ?? '' }}</b><br>
                {{ $m['dose'] ?? '' }} · {{ $m['duration'] ?? '' }}<br>
                <small>{{ $m['instruction'] ?? '' }}</small>
              </td>
            </tr>
          @endforeach
        </table>
        @if($rx->tests)<p><b>Investigation:</b> {{ $rx->tests }}</p>@endif
        @if($rx->advice)<p><b>Advice:</b> {!! nl2br(e($rx->advice)) !!}</p>@endif
        @if($rx->follow_up_date)<p><b>Follow up:</b> {{ $rx->follow_up_date }}</p>@endif
      </div>
    </div>
    <footer>
      <p>ফোন: {{ clinic_setting('clinic_phone') }}</p>
      <p class="sign">{{ $doc?->name }}<br><small>{{ $doc?->degree }}</small></p>
    </footer>
  </div>
  <p class="no-print" style="text-align:center"><button onclick="window.print()">প্রিন্ট</button></p>
</body>
</html>
