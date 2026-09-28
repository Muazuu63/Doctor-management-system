@extends('layouts.app', ['title' => 'সময়সূচি', 'active' => 'schedule', 'staff' => true])
@section('content')
<h1>চেম্বার সময়সূচি</h1>
<p>এখান থেকে আপনি নিজে সময় বদলালে পরবর্তী সিরিয়াল নতুন সময় থেকে শুরু হবে। প্রতি রোগী ১৫–২০ মিনিট।</p>

<div class="table-wrap card">
<table class="table">
  <tr><th>দিন</th><th>শিরোনাম</th><th>শুরু</th><th>শেষ</th><th>স্লট</th><th>সর্বোচ্চ</th><th>স্ট্যাটাস</th><th></th></tr>
  @foreach($rows as $r)
    <tr>
      <td>{{ bangla_day((int)$r->day_of_week) }}</td>
      <td>{{ $r->title }}</td>
      <td>{{ $r->start_time }}</td>
      <td>{{ $r->end_time }}</td>
      <td>{{ e_bn($r->slot_minutes) }} মি</td>
      <td>{{ e_bn($r->max_serial) }}</td>
      <td>{{ $r->is_active ? 'চলছে' : 'বন্ধ' }}</td>
      <td><a href="{{ route('staff.schedule', ['edit' => $r->id]) }}">এডিট</a></td>
    </tr>
  @endforeach
</table>
</div>

@if(auth()->user()->canManageSettings())
<div class="split">
  <form method="post" action="{{ route('staff.schedule.store') }}" class="card form">
    @csrf
    <h2>{{ $edit ? 'সময়সূচি এডিট' : 'নতুন দিন যোগ' }}</h2>
    <input type="hidden" name="id" value="{{ (int)($edit->id ?? 0) }}">
    <label>শিরোনাম<input name="title" required value="{{ $edit->title ?? 'চেম্বার' }}"></label>
    <label>দিন
      <select name="day_of_week">
        @for($i=0;$i<7;$i++)
          <option value="{{ $i }}" {{ ((int)($edit->day_of_week ?? -1) === $i) ? 'selected' : '' }}>{{ bangla_day($i) }}</option>
        @endfor
      </select>
    </label>
    <div class="row-2">
      <label>শুরু<input type="time" name="start_time" required value="{{ $edit->start_time ?? '16:00' }}"></label>
      <label>শেষ<input type="time" name="end_time" required value="{{ $edit->end_time ?? '20:00' }}"></label>
    </div>
    <div class="row-2">
      <label>স্লট মিনিট<input type="number" name="slot_minutes" value="{{ $edit->slot_minutes ?? 20 }}" min="10" max="60"></label>
      <label>সর্বোচ্চ সিরিয়াল<input type="number" name="max_serial" value="{{ $edit->max_serial ?? 50 }}" min="1" max="200"></label>
    </div>
    <label class="check"><input type="checkbox" name="is_active" {{ !isset($edit->is_active) || $edit->is_active ? 'checked' : '' }}> সক্রিয়</label>
    <button class="btn" type="submit">সেভ</button>
  </form>

  <form method="post" action="{{ route('staff.schedule.exception') }}" class="card form">
    @csrf
    <h2>বিশেষ দিন / ছুটি</h2>
    <label>তারিখ<input type="date" name="except_date" required></label>
    <label class="check"><input type="checkbox" name="is_off"> পুরো দিন বন্ধ</label>
    <label>শিরোনাম<input name="title" placeholder="অতিরিক্ত চেম্বার"></label>
    <div class="row-2">
      <label>শুরু<input type="time" name="start_time"></label>
      <label>শেষ<input type="time" name="end_time"></label>
    </div>
    <div class="row-2">
      <label>স্লট<input type="number" name="slot_minutes" value="20"></label>
      <label>সর্বোচ্চ<input type="number" name="max_serial" value="50"></label>
    </div>
    <label>নোট<input name="note"></label>
    <button class="btn" type="submit">যোগ করুন</button>
  </form>
</div>
@endif

<h2>আসন্ন বিশেষ দিন</h2>
<ul class="plain">
  @foreach($ex as $x)
    <li>
      {{ bangla_date($x->except_date->format('Y-m-d')) }} —
      {{ $x->is_off ? 'বন্ধ' : ($x->start_time.'–'.$x->end_time) }}
      @if(auth()->user()->canManageSettings())
      <form method="post" action="{{ route('staff.schedule.exception.delete', $x) }}" style="display:inline">
        @csrf
        <button class="linkish" type="submit">মুছুন</button>
      </form>
      @endif
    </li>
  @endforeach
</ul>
@endsection
