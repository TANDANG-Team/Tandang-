@extends('talent.layout')

@section('title', 'Riwayat & Penilaian')

@section('content')
<div class="page-head">
    <div>
        <h1>Riwayat Proyek & Penilaian</h1>
        <p>Konfirmasi penyelesaian pekerjaan dan beri penilaian untuk Penyedia setelah proyek selesai.</p>
    </div>
    <a href="{{ route('talent.profil') }}" class="btn btn-secondary btn-sm">Lihat Reputasiku</a>
</div>

@include('partials.engagement-list', ['routePrefix' => 'talent.riwayat'])
@endsection
