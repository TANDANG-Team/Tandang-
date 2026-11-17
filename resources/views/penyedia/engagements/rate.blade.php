@extends('layouts.penyedia')

@section('title', 'Nilai Talenta')

@section('content')
<div class="page-header">
    <div class="page-title-box">
        <h1>Nilai Talenta</h1>
        <p>Penilaian objektif membantu Talenta membangun reputasi dari pengalaman kerja nyata.</p>
    </div>
    <a href="{{ route('penyedia.engagements.index') }}" class="btn-secondary-action">&larr; Penilaian Talenta</a>
</div>

@include('partials.rating-form', ['action' => route('penyedia.engagements.rate.store', $completion)])
@endsection
