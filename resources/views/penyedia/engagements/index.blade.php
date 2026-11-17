@extends('layouts.penyedia')

@section('title', 'Penilaian Talenta')

@section('content')
<div class="page-header">
    <div class="page-title-box">
        <h1>Penilaian Talenta</h1>
        <p>Kelola status pengerjaan setiap Talenta yang diterima, lalu beri penilaian setelah pekerjaan selesai.</p>
    </div>
    <a href="{{ route('penyedia.profile') }}#reputasi" class="btn-secondary-action">Reputasi Usahaku</a>
</div>

@include('partials.engagement-list', ['routePrefix' => 'penyedia.engagements'])
@endsection
