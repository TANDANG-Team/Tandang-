@extends('layouts.penyedia')

@section('title', 'Reputasi Talenta')

@section('content')
@php $profile = $talent->talentProfile; @endphp
<div class="page-header">
    <div class="page-title-box">
        <h1>{{ $talent->name }}</h1>
        <p>{{ $profile?->category ?? 'Talenta Muda' }}{{ $profile?->institution && $profile->institution !== '-' ? ' · ' . $profile->institution : '' }}{{ $profile?->city ? ' · ' . $profile->city : '' }}</p>
    </div>
    <a href="{{ url()->previous() }}" class="btn-secondary-action">&larr; Kembali</a>
</div>

@include('partials.rating-summary', ['summary' => $summary, 'subject' => 'Talenta'])
@endsection
