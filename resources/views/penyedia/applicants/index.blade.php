@extends('layouts.penyedia')

@section('title', 'Pelamar')

@section('content')
<div class="page-header">
    <div class="page-title-box">
        <h1>Pelamar</h1>
        <p>Hanya pelamar pada proyek milikmu yang dapat dikelola.</p>
    </div>
</div>

<!-- Filter Tabs & Dropdown Project Filter -->
<div style="background: #ffffff; border: 1px solid #e9edf2; border-radius: 14px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="{{ route('penyedia.applicants.index', ['status' => 'semua', 'project_id' => $currentProjectId, 'search' => $search]) }}"
           style="padding: 7px 14px; border-radius: 8px; font-size: 13.5px; font-weight: 600; text-decoration: none; {{ $currentStatus === 'semua' ? 'background: #239b65; color: #ffffff;' : 'background: #f3f4f6; color: #4b5563;' }}">
            Semua ({{ $counts['semua'] }})
        </a>
        <a href="{{ route('penyedia.applicants.index', ['status' => 'menunggu', 'project_id' => $currentProjectId, 'search' => $search]) }}"
           style="padding: 7px 14px; border-radius: 8px; font-size: 13.5px; font-weight: 600; text-decoration: none; {{ $currentStatus === 'menunggu' ? 'background: #239b65; color: #ffffff;' : 'background: #f3f4f6; color: #4b5563;' }}">
            Menunggu ({{ $counts['menunggu'] }})
        </a>
        <a href="{{ route('penyedia.applicants.index', ['status' => 'dalam_review', 'project_id' => $currentProjectId, 'search' => $search]) }}"
           style="padding: 7px 14px; border-radius: 8px; font-size: 13.5px; font-weight: 600; text-decoration: none; {{ $currentStatus === 'dalam_review' ? 'background: #239b65; color: #ffffff;' : 'background: #f3f4f6; color: #4b5563;' }}">
            Dalam Review ({{ $counts['dalam_review'] }})
        </a>
        <a href="{{ route('penyedia.applicants.index', ['status' => 'diterima', 'project_id' => $currentProjectId, 'search' => $search]) }}"
           style="padding: 7px 14px; border-radius: 8px; font-size: 13.5px; font-weight: 600; text-decoration: none; {{ $currentStatus === 'diterima' ? 'background: #239b65; color: #ffffff;' : 'background: #f3f4f6; color: #4b5563;' }}">
            Diterima ({{ $counts['diterima'] }})
        </a>
        <a href="{{ route('penyedia.applicants.index', ['status' => 'ditolak', 'project_id' => $currentProjectId, 'search' => $search]) }}"
           style="padding: 7px 14px; border-radius: 8px; font-size: 13.5px; font-weight: 600; text-decoration: none; {{ $currentStatus === 'ditolak' ? 'background: #239b65; color: #ffffff;' : 'background: #f3f4f6; color: #4b5563;' }}">
            Ditolak ({{ $counts['ditolak'] }})
        </a>
    </div>

    <!-- Project Filter & Search -->
    <form method="GET" action="{{ route('penyedia.applicants.index') }}" style="display: flex; gap: 8px; flex-wrap: wrap;">
        <input type="hidden" name="status" value="{{ $currentStatus }}">

        <select name="project_id" onchange="this.form.submit()"
                style="padding: 8px 12px; border: 1.5px solid #e5e7eb; border-radius: 8px; font-size: 13.5px; background: #ffffff; outline: none;">
            <option value="">Semua Proyek</option>
            @foreach($myProjects as $proj)
                <option value="{{ $proj->id }}" {{ $currentProjectId == $proj->id ? 'selected' : '' }}>
                    {{ Str::limit($proj->title, 28) }}
                </option>
            @endforeach
        </select>

        <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama talenta..."
               style="padding: 8px 12px; border: 1.5px solid #e5e7eb; border-radius: 8px; font-size: 13.5px; outline: none; min-width: 180px;">
        <button type="submit" class="btn-secondary-action" style="padding: 8px 12px;">Cari</button>
    </form>
</div>

<!-- Applicant Cards List -->
@if($applications->isEmpty())
    <div style="background: #ffffff; border: 1px solid #e9edf2; border-radius: 14px; text-align: center; padding: 60px 20px;">
        <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="#9ca3af" stroke-width="1.5" style="margin-bottom: 12px;">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
        <h3 style="font-size: 16px; font-weight: 700; color: #374151; margin: 0 0 6px;">Belum ada pelamar pada kriteria ini</h3>
        <p style="font-size: 13.5px; color: #6b7280; margin: 0;">Pelamar yang mengajukan lamaran ke proyek aktifmu akan muncul di sini.</p>
    </div>
@else
    <div style="display: flex; flex-direction: column; gap: 16px;">
        @foreach($applications as $app)
            @php
                $talentName = $app->talent_snapshot['name'] ?? ($app->user->name ?? 'Talenta');
                $institution = $app->talent_snapshot['institution'] ?? 'Institusi Belum Ditentukan';
                $category = $app->talent_snapshot['category'] ?? 'Talenta Muda';
                $bio = $app->talent_snapshot['bio'] ?? 'Talenta muda siap berkontribusi pada proyek ini.';
                $competencies = $app->talent_snapshot['competencies'] ?? ['Komunikasi', 'Kerja Sama'];
                $portfolio = $app->talent_snapshot['portfolio'] ?? ['Proyek Portofolio Mandiri'];
                $assessmentSummary = $app->talent_snapshot['assessment_summary'] ?? 'Assessment diselesaikan. Skor kecocokan dihitung berdasarkan keselarasan preferensi kerja.';
                $matchScore = $app->match_score ?: 85;
                $initial = strtoupper(substr($talentName, 0, 1));
            @endphp
            <div style="background: #ffffff; border: 1px solid #e9edf2; border-radius: 14px; padding: 22px; display: flex; align-items: center; justify-content: space-between; gap: 18px; flex-wrap: wrap; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
                <!-- Profile & Project Info -->
                <div style="display: flex; align-items: center; gap: 16px; min-width: 280px; flex: 1;">
                    <div style="width: 48px; height: 48px; border-radius: 50%; background: #e0e7ff; color: #3730a3; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 700; flex-shrink: 0;">
                        {{ $initial }}
                    </div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <h3 style="font-size: 16px; font-weight: 700; color: #111827; margin: 0;">{{ $talentName }}</h3>
                            <span class="badge-status badge-{{ $app->status }}">{{ $app->status_label }}</span>
                            @if($matchScore)
                                <span style="background: #ecfdf5; color: #065f46; font-size: 12px; font-weight: 700; padding: 3px 8px; border-radius: 6px; border: 1px solid #a7f3d0;">
                                    Kecocokan {{ $matchScore }}%
                                </span>
                            @endif
                        </div>
                        <div style="font-size: 13px; color: #6b7280; margin-top: 4px;">
                            <strong>{{ $category }}</strong> &bull; {{ $institution }}
                        </div>
                        <div style="font-size: 12.5px; color: #4b5563; margin-top: 3px;">
                            Melamar pada: <strong style="color: #1f2a37;">{{ $app->project->title ?? '-' }}</strong> &bull; {{ $app->created_at->format('d M Y') }}
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <!-- Button Open Modal Detail -->
                    <button type="button" class="btn-secondary-action"
                            onclick="openDetailModal('modal-{{ $app->id }}')"
                            style="padding: 8px 14px; font-size: 13px;">
                        Lihat Profil
                    </button>

                    <!-- Form Terima -->
                    @if($app->status !== 'diterima')
                        <form action="{{ route('penyedia.applicants.status', $app) }}" method="POST" style="margin: 0;"
                              onsubmit="return confirm('Apakah Anda yakin ingin MENERIMA pelamar ini untuk proyek {{ $app->project->title }}?');">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="diterima">
                            <button type="submit" class="btn-primary-action" style="padding: 8px 14px; font-size: 13px;">
                                Terima
                            </button>
                        </form>
                    @endif

                    <!-- Form Tolak -->
                    @if($app->status !== 'ditolak')
                        <form action="{{ route('penyedia.applicants.status', $app) }}" method="POST" style="margin: 0;"
                              onsubmit="return confirm('Apakah Anda yakin ingin MENOLAK pelamar ini?');">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="ditolak">
                            <button type="submit" class="btn-secondary-action" style="padding: 8px 14px; font-size: 13px; color: #dc2626; border-color: #fecaca;">
                                Tolak
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- MODAL DETAIL TALENTA MATCHING PENYEDIA.PDF -->
            <div id="modal-{{ $app->id }}" style="display: none; position: fixed; inset: 0; z-index: 1000; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; padding: 20px;">
                <div style="background: #ffffff; border-radius: 16px; max-width: 620px; width: 100%; max-height: 90vh; overflow-y: auto; padding: 28px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); position: relative;">
                    <!-- Close button -->
                    <button type="button" onclick="closeDetailModal('modal-{{ $app->id }}')"
                            style="position: absolute; top: 20px; right: 20px; background: none; border: none; font-size: 22px; cursor: pointer; color: #9ca3af;">
                        &times;
                    </button>

                    <h2 style="font-size: 18px; font-weight: 800; color: #111827; margin: 0 0 18px;">Profil & Portofolio Talenta</h2>

                    <!-- Talenta Header -->
                    <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #f3f4f6;">
                        <div style="width: 54px; height: 54px; border-radius: 50%; background: #e0e7ff; color: #3730a3; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 700;">
                            {{ $initial }}
                        </div>
                        <div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <h3 style="font-size: 17px; font-weight: 800; color: #111827; margin: 0;">{{ $talentName }}</h3>
                                <span style="background: #ecfdf5; color: #065f46; font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 4px;">Terverifikasi</span>
                            </div>
                            <div style="font-size: 13px; color: #6b7280; margin-top: 2px;">
                                {{ $category }} &bull; {{ $institution }}
                            </div>
                            <div style="font-size: 13px; color: #4b5563; margin-top: 6px; line-height: 1.5;">
                                {{ $bio }}
                            </div>
                        </div>
                    </div>

                    <!-- Pesan Lamaran -->
                    <div style="margin-bottom: 20px; background: #f9fafb; border-radius: 10px; padding: 14px;">
                        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #6b7280; letter-spacing: 0.5px; margin-bottom: 4px;">Pesan Lamaran</div>
                        <p style="margin: 0; font-size: 13.5px; color: #1f2a37; line-height: 1.6;">
                            {{ $app->cover_letter ?: 'Tidak menyertakan pesan lamaran khusus.' }}
                        </p>
                        <div style="font-size: 11px; color: #9ca3af; margin-top: 6px;">
                            Dikirim pada: {{ $app->created_at->format('d/m/Y') }}
                        </div>
                    </div>

                    <!-- Kompetensi -->
                    <div style="margin-bottom: 20px;">
                        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #6b7280; letter-spacing: 0.5px; margin-bottom: 8px;">Kompetensi</div>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            @foreach($competencies as $comp)
                                <span style="background: #f3f4f6; color: #374151; font-size: 12.5px; font-weight: 600; padding: 4px 10px; border-radius: 6px;">
                                    {{ $comp }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <!-- Ringkasan Gaya Kerja -->
                    <div style="margin-bottom: 20px;">
                        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #6b7280; letter-spacing: 0.5px; margin-bottom: 6px;">Ringkasan Gaya Kerja</div>
                        <p style="margin: 0; font-size: 13px; color: #4b5563; line-height: 1.5;">
                            {{ $assessmentSummary }}
                        </p>
                    </div>

                    <!-- Portofolio -->
                    <div style="margin-bottom: 24px;">
                        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #6b7280; letter-spacing: 0.5px; margin-bottom: 8px;">Portofolio</div>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            @foreach($portfolio as $port)
                                <div style="background: #ffffff; border: 1.5px solid #e5e7eb; border-radius: 8px; padding: 8px 12px; font-size: 13px; font-weight: 600; color: #2563eb;">
                                    &bull; {{ $port }}
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div style="display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding-top: 14px; border-top: 1px solid #f3f4f6;">
                        <button type="button" class="btn-secondary-action" onclick="closeDetailModal('modal-{{ $app->id }}')">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if($applications->hasPages())
        <div style="margin-top: 24px;">
            {{ $applications->links() }}
        </div>
    @endif
@endif

<script>
    function openDetailModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.style.display = 'flex';
        }
    }

    function closeDetailModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.style.display = 'none';
        }
    }
</script>
@endsection
