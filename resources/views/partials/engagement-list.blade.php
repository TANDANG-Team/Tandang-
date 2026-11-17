{{--
    Riwayat keterlibatan proyek, dikelompokkan menurut kelayakan penilaian (Penyedia & Talenta).
    $groups ['perlu' | 'sudah' | 'belum' => Collection], $engagements, $routePrefix
--}}
@use('App\Models\ProjectWorkCompletion', 'W')
@use('App\Services\Rating\RatingService', 'R')
@php
    $me = auth()->user();
    $isProvider = $me->isPenyedia();
    $sections = [
        'perlu' => ['Perlu Dinilai', 'Pekerjaan sudah selesai dan kamu belum memberi penilaian. Batas 7 hari sejak selesai.', 'warning'],
        'sudah' => ['Sudah Dinilai', 'Penilaianmu tersimpan. Isinya dipublikasikan setelah pihak lain menilai atau 7 hari berlalu.', 'success'],
        'belum' => ['Belum Memenuhi Syarat Penilaian', 'Pekerjaan belum dikonfirmasi selesai, sedang disengketakan, atau periode penilaian telah berakhir.', 'muted'],
    ];
    $stateTone = [R::STATE_NOT_RATED => 'warning', R::STATE_WAITING_OTHER => 'info', R::STATE_PUBLISHED => 'success', R::STATE_WINDOW_CLOSED => 'muted'];
@endphp

<div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:18px;">
    @foreach($sections as $key => [$label])
        <a href="#grup-{{ $key }}" class="tdg-btn sm {{ $key === 'perlu' && $groups['perlu']->isNotEmpty() ? 'amber' : 'ghost' }}">{{ $label }} <span style="opacity:.75;">{{ $groups[$key]->count() }}</span></a>
    @endforeach
</div>

@if($engagements->isEmpty())
    <div class="rep-card rep-empty" style="padding:48px 20px;">
        <strong>Belum ada riwayat pengerjaan</strong>
        {{ $isProvider ? 'Riwayat muncul setelah kamu menerima Talenta pada proyekmu.' : 'Riwayat muncul setelah lamaranmu diterima Penyedia.' }}
    </div>
@else
    @foreach($sections as $key => [$label, $desc, $tone])
        <section id="grup-{{ $key }}" style="margin-bottom:26px;">
            <div style="display:flex; align-items:baseline; gap:10px; flex-wrap:wrap; margin-bottom:10px;">
                <h2 style="font-size:16px; font-weight:800; color:#182238; margin:0;">{{ $label }}</h2>
                <span class="tdg-pill {{ $tone }}">{{ $groups[$key]->count() }}</span>
                <p style="flex-basis:100%; margin:2px 0 0; font-size:12.5px; color:#64748b;">{{ $desc }}</p>
            </div>

            @if($groups[$key]->isEmpty())
                <div class="rep-card rep-empty" style="padding:18px;">Tidak ada.</div>
            @else
                <div class="eng-list">
                    @foreach($groups[$key] as $c)
                        @php
                            $other = $isProvider ? $c->talent : $c->provider;
                            $iRequested = (int) $c->requested_by === (int) $me->id;
                            $state = $c->rating_state;
                            $mine = $c->ratings->firstWhere('rater_id', $me->id);
                        @endphp
                        <article class="eng-card">
                            <div class="eng-thumb"><img src="{{ $c->project?->cover_url }}" alt="" loading="lazy"></div>
                            <div style="min-width:0;">
                                <h3 class="eng-title">{{ $c->project?->title ?? 'Proyek dihapus' }}</h3>
                                <div class="eng-meta">
                                    <span>{{ $isProvider ? 'Talenta' : 'Penyedia' }}:
                                        @if($isProvider)
                                            <a href="{{ route('penyedia.talents.show', $other) }}" style="color:#0c7566; font-weight:700; text-decoration:none;">{{ $other?->name }}</a>
                                        @else
                                            <a href="{{ route('talent.providers.show', $other) }}" style="color:#0c7566; font-weight:700; text-decoration:none;">{{ $c->project?->provider_name ?: $other?->name }}</a>
                                        @endif
                                    </span>
                                    <span class="tdg-pill {{ $c->status_tone }}">{{ $c->status_label }}</span>
                                    @if($state !== R::STATE_NOT_AVAILABLE)
                                        <span class="tdg-pill {{ $stateTone[$state] ?? 'muted' }}">{{ R::stateLabel($state) }}</span>
                                    @endif
                                </div>
                                <div class="eng-note">
                                    @if($mine)
                                        Penilaianmu: <strong style="color:#182238;">{{ number_format((float) $mine->final_score, 2, ',', '') }} / 5</strong> · dikirim {{ \App\Support\Wib::format($mine->created_at) }}
                                    @else
                                        @switch($c->status)
                                            @case(W::STATUS_PENDING_CONFIRMATION)
                                                {{ $iRequested ? 'Kamu mengajukan selesai ' . \App\Support\Wib::format($c->requested_at) . '. Menunggu konfirmasi pihak lain (otomatis selesai setelah ' . config('rating.auto_confirm_days') . ' hari tanpa respons).' : 'Pihak lain mengajukan pekerjaan selesai. Konfirmasi bila benar, atau ajukan sengketa.' }}
                                                @break
                                            @case(W::STATUS_COMPLETED)
                                                Selesai {{ \App\Support\Wib::format($c->completed_at) }} · penilaian dibuka sampai {{ \App\Support\Wib::format($c->rating_deadline_at) }}.
                                                @break
                                            @case(W::STATUS_DISPUTED)
                                                Sengketa sedang ditinjau Admin. Alasan: "{{ \Illuminate\Support\Str::limit($c->dispute_reason, 120) }}"
                                                @break
                                            @default
                                                Pekerjaan sedang berjalan. Ajukan selesai saat semua tugas telah diserahkan.
                                        @endswitch
                                    @endif
                                </div>
                            </div>
                            <div class="eng-actions">
                                @if($c->status === W::STATUS_IN_PROGRESS)
                                    <form method="POST" action="{{ route($routePrefix . '.request', $c) }}">
                                        @csrf
                                        <button type="submit" class="tdg-btn ghost sm">Ajukan Selesai</button>
                                    </form>
                                @elseif($c->status === W::STATUS_PENDING_CONFIRMATION && ! $iRequested)
                                    <form method="POST" action="{{ route($routePrefix . '.confirm', $c) }}">
                                        @csrf
                                        <button type="submit" class="tdg-btn primary sm">Konfirmasi Selesai</button>
                                    </form>
                                @endif

                                @if($state === R::STATE_NOT_RATED)
                                    <a href="{{ route($routePrefix . '.rate', $c) }}" class="tdg-btn amber sm">Beri Rating</a>
                                @elseif($key === 'belum')
                                    <button type="button" class="tdg-btn ghost sm" disabled title="Rating hanya bisa diberikan setelah pekerjaan selesai, dalam 7 hari.">Beri Rating</button>
                                @endif
                            </div>

                            @if($c->status === W::STATUS_PENDING_CONFIRMATION && ! $iRequested)
                                <details class="eng-dispute">
                                    <summary>Belum selesai? Ajukan sengketa ke Admin</summary>
                                    <form method="POST" action="{{ route($routePrefix . '.dispute', $c) }}">
                                        @csrf
                                        <textarea name="dispute_reason" required minlength="10" maxlength="1000" placeholder="Jelaskan bagian pekerjaan yang belum selesai atau masalah yang terjadi…"></textarea>
                                        <button type="submit" class="tdg-btn danger sm">Kirim Sengketa</button>
                                    </form>
                                </details>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    @endforeach
@endif
