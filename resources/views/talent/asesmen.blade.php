@extends('talent.layout')

@section('title', 'Asesmen Gaya Kerja')

@php
    use App\Services\WorkstyleMatchingService;

    $saved = WorkstyleMatchingService::normalizeAnswers($talent->assessment_answers ?? []);
    $hasResult = WorkstyleMatchingService::isComplete($saved);
    $total = count($questions);
    $oldAnswers = old('answers', []);
    // Jika ada error validasi, langsung tampilkan layar pertanyaan
    $initialScreen = $errors->any() ? 'questions' : ($hasResult ? 'result' : 'intro');
@endphp

@push('styles')
<style>
    .ws-wrap { max-width: 760px; }
    .ws-card {
        background: #fff; border: 1px solid var(--line); border-radius: 18px;
        padding: 36px; box-shadow: var(--shadow-sm);
    }
    .ws-screen { display: none; }
    .ws-screen.active { display: block; }
    .ws-center { text-align: center; }
    .ws-hero-icon {
        width: 64px; height: 64px; border-radius: 18px; margin: 0 auto 18px;
        display: grid; place-items: center; background: var(--teal-light); color: var(--teal);
    }
    .ws-hero-icon.success { background: #fef3c7; color: #d97706; }
    .ws-eyebrow { font-size: 11.5px; font-weight: 800; letter-spacing: 1.6px; color: var(--teal); margin-bottom: 6px; }
    .ws-card h1 { font-size: 26px; font-weight: 800; color: var(--navy); margin: 0 0 10px; }
    .ws-lead { color: var(--muted); font-size: 15px; line-height: 1.7; max-width: 560px; margin: 0 auto 22px; }
    .ws-facts { display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; margin-bottom: 18px; }
    .ws-facts span {
        display: inline-flex; align-items: center; gap: 8px; background: #f8fafc;
        border: 1px solid var(--line); border-radius: 999px; padding: 8px 14px;
        font-size: 13px; font-weight: 600; color: var(--navy);
    }
    .ws-facts svg { color: var(--teal); }
    .ws-disclaimer {
        background: #fffbeb; border: 1px solid #fde68a; color: #92400e; border-radius: 10px;
        padding: 10px 14px; font-size: 12.5px; margin: 0 auto 24px; max-width: 520px;
    }

    .ws-topline { display: flex; justify-content: space-between; font-size: 13px; font-weight: 700; color: var(--muted); margin-bottom: 8px; }
    .ws-topline b { color: var(--teal); }
    .ws-progress { height: 8px; background: #e2e8f0; border-radius: 99px; overflow: hidden; margin-bottom: 26px; }
    .ws-progress i { display: block; height: 100%; background: var(--teal); border-radius: 99px; transition: width .25s ease; }
    .ws-question { display: none; }
    .ws-question.active { display: block; }
    .ws-question h2 { font-size: 20px; font-weight: 800; color: var(--navy); margin: 0 0 6px; line-height: 1.4; }
    .ws-scale { font-size: 12.5px; color: var(--muted); margin-bottom: 18px; }
    .ws-answers { display: flex; flex-direction: column; gap: 10px; }
    .ws-answer {
        display: flex; align-items: center; gap: 14px; padding: 13px 16px; cursor: pointer;
        border: 1.5px solid var(--line); border-radius: 12px; background: #fff; transition: all .15s;
    }
    .ws-answer:hover { border-color: #99f6e4; background: #f8fffd; }
    .ws-answer input { position: absolute; opacity: 0; pointer-events: none; }
    .ws-answer .letter {
        width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0; display: grid; place-items: center;
        background: #f1f5f9; color: #475569; font-weight: 800; font-size: 13px;
    }
    .ws-answer .text { flex: 1; font-size: 14px; font-weight: 600; color: var(--navy); }
    .ws-answer .check { color: var(--teal); opacity: 0; }
    .ws-answer:has(input:checked) { border-color: var(--teal); background: var(--teal-light); }
    .ws-answer:has(input:checked) .letter { background: var(--teal); color: #fff; }
    .ws-answer:has(input:checked) .check { opacity: 1; }
    .ws-answer:has(input:focus-visible) { outline: 3px solid #a8e1d9; outline-offset: 2px; }

    .ws-actions { display: flex; justify-content: space-between; gap: 12px; margin-top: 28px; flex-wrap: wrap; }
    .ws-actions.center { justify-content: center; }
    .ws-actions .btn:disabled { opacity: .45; cursor: not-allowed; }

    .ws-result-lead {
        font-size: 16px; line-height: 1.7; color: var(--navy); font-weight: 600;
        background: linear-gradient(135deg, #f0fdfa 0%, #fff 100%); border: 1px solid #99f6e4;
        border-radius: 14px; padding: 16px 20px; margin: 0 0 24px; text-align: left;
    }
    .ws-dimensions { display: flex; flex-direction: column; gap: 16px; text-align: left; }
    .ws-dim-head { display: flex; justify-content: space-between; align-items: baseline; gap: 10px; margin-bottom: 6px; }
    .ws-dim-head b { font-size: 13.5px; color: var(--navy); }
    .ws-dim-head small { font-size: 12px; font-weight: 700; color: var(--teal-dark); white-space: nowrap; }
    .ws-track { position: relative; height: 8px; background: #e2e8f0; border-radius: 99px; }
    .ws-track i { position: absolute; left: 0; top: 0; bottom: 0; background: var(--teal); border-radius: 99px; }
    .ws-track em {
        position: absolute; top: 50%; width: 16px; height: 16px; margin-left: -8px; transform: translateY(-50%);
        background: #fff; border: 3px solid var(--teal); border-radius: 50%;
    }
    .ws-dim-ends { display: flex; justify-content: space-between; font-size: 11.5px; color: var(--muted); margin-top: 5px; }
    .ws-date { font-size: 12.5px; color: var(--muted); margin-top: 22px; }

    @media (max-width: 600px) {
        .ws-card { padding: 22px 18px; }
        .ws-card h1 { font-size: 22px; }
        .ws-actions .btn { flex: 1; justify-content: center; }
    }
</style>
@endpush

@section('content')
<div class="page-head">
    <div>
        <h1>Asesmen Gaya Kerja</h1>
        <p>Kenali preferensi dan ritme kerjamu agar TANDANG bisa mencocokkanmu dengan proyek yang tepat.</p>
    </div>
</div>

<div class="ws-wrap">
    <div class="ws-card">

        {{-- ============ INTRO ============ --}}
        <section class="ws-screen ws-center {{ $initialScreen === 'intro' ? 'active' : '' }}" data-screen="intro">
            <div class="ws-hero-icon">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>
            </div>
            <div class="ws-eyebrow">WORKSTYLE ASSESSMENT</div>
            <h1>Kenali Gaya Kerjamu</h1>
            <p class="ws-lead">
                Jawab {{ $total }} pertanyaan singkat untuk membantu TANDANG menemukan proyek
                yang lebih sesuai dengan cara dan preferensi kerjamu.
            </p>
            <div class="ws-facts">
                <span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    {{ $total }} pertanyaan
                </span>
                <span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Sekitar 2–3 menit
                </span>
                <span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    Tidak ada jawaban benar atau salah
                </span>
            </div>
            <div class="ws-disclaimer">
                Asesmen ini hanya membantu memahami preferensi kerja dan bukan tes psikologi resmi.
            </div>
            <div class="ws-actions center">
                @if($hasResult)
                    <button type="button" class="btn btn-secondary" data-go="result">Batal</button>
                @endif
                <button type="button" class="btn btn-primary" data-go="questions">
                    Mulai Asesmen
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </button>
            </div>
        </section>

        {{-- ============ QUESTIONS ============ --}}
        <section class="ws-screen {{ $initialScreen === 'questions' ? 'active' : '' }}" data-screen="questions">
            @if($errors->any())
                <div class="alert alert-danger" style="margin-bottom: 18px;">
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('talent.asesmen.simpan') }}" id="wsForm">
                @csrf
                <div class="ws-topline">
                    <span id="wsStepLabel">Pertanyaan 1 dari {{ $total }}</span>
                    <b id="wsStepPct">{{ round(100 / $total) }}%</b>
                </div>
                <div class="ws-progress"><i id="wsProgress" style="width: {{ 100 / $total }}%;"></i></div>

                @foreach($questions as $index => $q)
                    <fieldset class="ws-question {{ $index === 0 ? 'active' : '' }}" data-step="{{ $index }}" style="border: 0; padding: 0; margin: 0;">
                        <legend style="padding: 0;"><h2>{{ $q['title'] }}</h2></legend>
                        <div class="ws-scale">{{ $q['low'] }} &larr;&rarr; {{ $q['high'] }}</div>
                        <div class="ws-answers">
                            @foreach($q['options'] as $score => $text)
                                <label class="ws-answer">
                                    <input type="radio" name="answers[{{ $q['key'] }}]" value="{{ $score }}"
                                        {{ (string) ($oldAnswers[$q['key']] ?? '') === (string) $score ? 'checked' : '' }}>
                                    <span class="letter">{{ chr(64 + $score) }}</span>
                                    <span class="text">{{ $text }}</span>
                                    <svg class="check" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><polyline points="20 6 9 17 4 12"/></svg>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach

                <div class="ws-actions">
                    <button type="button" class="btn btn-secondary" id="wsPrev">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                        Kembali
                    </button>
                    <button type="button" class="btn btn-primary" id="wsNext" disabled>
                        Lanjut
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </button>
                    <button type="submit" class="btn btn-primary" id="wsSubmit" style="display: none;" disabled>
                        Lihat Hasil Asesmen
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z"/></svg>
                    </button>
                </div>
            </form>
        </section>

        {{-- ============ RESULT ============ --}}
        @if($hasResult)
            <section class="ws-screen ws-center {{ $initialScreen === 'result' ? 'active' : '' }}" data-screen="result">
                <div class="ws-hero-icon success">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z"/></svg>
                </div>
                <div class="ws-eyebrow">ASESMEN SELESAI</div>
                <h1>Hasil Gaya Kerjamu</h1>

                <p class="ws-result-lead">{{ $summary }}</p>

                <div class="ws-dimensions">
                    @foreach($questions as $q)
                        @php
                            $val = $saved[$q['key']];
                            $pct = (($val - 1) / 4) * 100;
                        @endphp
                        <div>
                            <div class="ws-dim-head">
                                <b>{{ \Illuminate\Support\Str::before($q['title'], '?') }}</b>
                                <small>{{ WorkstyleMatchingService::dimensionLabel($q, $val) }}</small>
                            </div>
                            <div class="ws-track">
                                <i style="width: {{ $pct }}%;"></i>
                                <em style="left: {{ $pct }}%;"></em>
                            </div>
                            <div class="ws-dim-ends"><span>{{ $q['low'] }}</span><span>{{ $q['high'] }}</span></div>
                        </div>
                    @endforeach
                </div>

                <p class="ws-date">
                    Diselesaikan {{ $talent->assessment_completed_at ? $talent->assessment_completed_at->locale('id')->isoFormat('D MMMM Y') : '-' }}
                </p>

                <div class="ws-actions center">
                    <button type="button" class="btn btn-secondary" id="wsRetake">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                        Ulangi Asesmen
                    </button>
                    <a href="{{ route('talent.rekomendasi') }}" class="btn btn-primary">
                        Lihat Proyek yang Cocok
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                </div>
            </section>
        @endif
    </div>
</div>

@push('scripts')
<script>
(function () {
    const total = {{ $total }};
    const screens = document.querySelectorAll('[data-screen]');
    const steps = document.querySelectorAll('.ws-question');
    const prev = document.getElementById('wsPrev');
    const next = document.getElementById('wsNext');
    const submit = document.getElementById('wsSubmit');
    let step = 0;

    function show(name) {
        screens.forEach(s => s.classList.toggle('active', s.dataset.screen === name));
        if (name === 'questions') renderStep();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function answered(i) {
        return !!steps[i].querySelector('input:checked');
    }

    function renderStep() {
        steps.forEach((s, i) => s.classList.toggle('active', i === step));
        const pct = Math.round(((step + 1) / total) * 100);
        document.getElementById('wsStepLabel').textContent = `Pertanyaan ${step + 1} dari ${total}`;
        document.getElementById('wsStepPct').textContent = pct + '%';
        document.getElementById('wsProgress').style.width = pct + '%';

        const last = step === total - 1;
        prev.disabled = step === 0;
        next.style.display = last ? 'none' : '';
        submit.style.display = last ? '' : 'none';
        next.disabled = !answered(step);
        submit.disabled = !Array.from(steps).every((_, i) => answered(i));
    }

    document.querySelectorAll('[data-go]').forEach(btn =>
        btn.addEventListener('click', () => show(btn.dataset.go))
    );

    prev.addEventListener('click', () => { if (step > 0) { step--; renderStep(); } });
    next.addEventListener('click', () => { if (step < total - 1 && answered(step)) { step++; renderStep(); } });

    // Pilih jawaban -> otomatis lanjut ke pertanyaan berikutnya
    steps.forEach((s, i) => s.addEventListener('change', () => {
        renderStep();
        if (i < total - 1) setTimeout(() => { step = i + 1; renderStep(); }, 220);
    }));

    const retake = document.getElementById('wsRetake');
    if (retake) {
        retake.addEventListener('click', () => {
            document.querySelectorAll('#wsForm input[type=radio]').forEach(r => r.checked = false);
            step = 0;
            show('intro');
        });
    }

    // Setelah error validasi: lompat ke pertanyaan pertama yang belum dijawab
    if (document.querySelector('[data-screen="questions"].active')) {
        const firstEmpty = Array.from(steps).findIndex((_, i) => !answered(i));
        step = firstEmpty === -1 ? total - 1 : firstEmpty;
        renderStep();
    }
})();
</script>
@endpush
@endsection
