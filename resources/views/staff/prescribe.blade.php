@extends('layouts.app', ['title' => 'প্রেসক্রিপশন', 'active' => 'queue', 'staff' => true])
@section('content')
<h1>প্রেসক্রিপশন · {{ $apt->patient->name }}</h1>
<p>{{ $apt->patient->phone }} · {{ $apt->patient->gender }} · {{ e_bn((int)$apt->patient->age) }} বছর · সিরিয়াল {{ e_bn($apt->serial_no) }}</p>
<p><a href="{{ route('staff.patients.show', $apt->patient) }}">পূর্ণ হিস্ট্রি</a></p>

<form method="post" action="{{ route('staff.prescribe.store', $apt) }}" class="rx-form">
  @csrf
  <div class="split">
    <div class="card">
      <label>অভিযোগ (C/C)<textarea name="complaints">{{ old('complaints', $existing->complaints ?? $apt->notes) }}</textarea></label>
      <label>পরীক্ষা (O/E)<textarea name="findings">{{ old('findings', $existing->findings ?? '') }}</textarea></label>
      <label>রোগ নির্ণয় (Dx)<input name="diagnosis" value="{{ old('diagnosis', $existing->diagnosis ?? '') }}"></label>
      <label>পরীক্ষা-নিরীক্ষা<input name="tests" value="{{ old('tests', $existing->tests ?? '') }}" placeholder="CBC, RBS..."></label>
      <label>পরামর্শ<textarea name="advice">{{ old('advice', $existing->advice ?? '') }}</textarea></label>
      <label>ফলো-আপ তারিখ<input name="follow_up_date" value="{{ old('follow_up_date', $existing->follow_up_date ?? '') }}" placeholder="৭ দিন পর"></label>
    </div>
    <div class="card">
      <h3>ওষুধ</h3>
      <p class="muted">টেমপ্লেট ক্লিক করে যোগ করুন · <a href="{{ route('staff.medicines') }}">টেমপ্লেট ম্যানেজ</a></p>
      <div class="tpl">
        @foreach($templates as $t)
          <button type="button" class="chip" data-med='@json($t->only(["name","dose","duration","instruction"]))'>{{ $t->name }}</button>
        @endforeach
      </div>
      <div id="meds">
        @foreach($meds as $m)
          <div class="med-row">
            <input name="med_name[]" placeholder="ওষুধ" value="{{ $m['name'] ?? '' }}">
            <input name="med_dose[]" placeholder="১+০+১" value="{{ $m['dose'] ?? '' }}">
            <input name="med_duration[]" placeholder="৭ দিন" value="{{ $m['duration'] ?? '' }}">
            <input name="med_instruction[]" placeholder="খাওয়ার পর" value="{{ $m['instruction'] ?? '' }}">
          </div>
        @endforeach
      </div>
      <button type="button" class="btn ghost" id="addMed">আরও ওষুধ</button>
      <button class="btn" type="submit">সেভ ও প্রিন্ট</button>
    </div>
  </div>
</form>

@if($history->count() > 1)
  <h2>আগের ভিজিট</h2>
  <ul class="plain">
    @foreach($history as $h)
      @if((int)$h->id === (int)$apt->id) @continue @endif
      <li>{{ bangla_date($h->appointment_date->format('Y-m-d')) }} · {{ status_bn($h->status) }} {{ $h->prescription?->diagnosis ? '· '.$h->prescription->diagnosis : '' }}</li>
    @endforeach
  </ul>
@endif
@push('scripts')
<script>
document.getElementById('addMed').onclick = () => {
  const d = document.createElement('div');
  d.className = 'med-row';
  d.innerHTML = '<input name="med_name[]" placeholder="ওষুধ"><input name="med_dose[]" placeholder="১+০+১"><input name="med_duration[]" placeholder="৭ দিন"><input name="med_instruction[]" placeholder="খাওয়ার পর">';
  document.getElementById('meds').appendChild(d);
};
document.querySelectorAll('[data-med]').forEach(btn => {
  btn.onclick = () => {
    const t = JSON.parse(btn.dataset.med);
    const empty = [...document.querySelectorAll('#meds .med-row')].find(r => !r.querySelector('[name="med_name[]"]').value);
    const row = empty || (() => { document.getElementById('addMed').click(); return document.querySelector('#meds .med-row:last-child'); })();
    row.children[0].value = t.name;
    row.children[1].value = t.dose;
    row.children[2].value = t.duration;
    row.children[3].value = t.instruction;
  };
});
</script>
@endpush
@endsection
