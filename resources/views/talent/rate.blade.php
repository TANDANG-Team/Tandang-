@extends('talent.layout')

@section('title', 'Beri Penilaian')

@section('content')
<div class="page-head">
    <div>
        <h1>Nilai Penyedia</h1>
        <p>Penilaianmu membantu Talenta lain memilih Penyedia yang tepercaya.</p>
    </div>
    <a href="{{ route('talent.riwayat') }}" class="btn btn-secondary btn-sm">&larr; Riwayat</a>
</div>

@include('partials.rating-form', ['action' => route('talent.riwayat.rate.store', $completion)])
@endsection
