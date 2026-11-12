@extends('talent.layout')

@section('title', 'Rekomendasi Proyek')

@section('content')
<div class="page-head">
    <div>
        <h1>Rekomendasi Proyek Cerdas</h1>
        <p>Proyek diurutkan secara otomatis berdasarkan kecocokan kompetensi, gaya kerja, lokasi, dan ketersediaan waktumu.</p>
    </div>
</div>

@if(empty($talent->assessment_answers))
    <div class="alert alert-warning" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <strong>Kecocokan Gaya Kerja Belum Maksimal!</strong>
            <p style="margin: 0; font-size: 13px;">Selesaikan asesmen gaya kerja untuk mendapatkan kalkulasi kecocokan yang lebih akurat.</p>
        </div>
        <a href="{{ route('talent.asesmen') }}" class="btn btn-primary btn-sm" style="white-space: nowrap;">Mulai Asesmen</a>
    </div>
@endif

@if($projects->count() > 0)
    <div class="project-grid">
        @foreach($projects as $p)
            @php
                $isApplied = in_array($p->id, $appliedProjectIds);
                $scoreClass = $p->match_score >= 80 ? 'high' : ($p->match_score >= 60 ? 'medium' : 'low');
                $components = $p->match_data['components'] ?? [];
            @endphp
            <article class="project-card">
                <div>
                    <div class="project-card-top">
                        <span class="provider">{{ $p->provider_name }}</span>
                        <span class="match-score-badge {{ $scoreClass }}">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z"/></svg>
                            {{ $p->match_score }}% Cocok
                        </span>
                    </div>

                    <h3>{{ $p->title }}</h3>
                    <p class="desc">{{ $p->description }}</p>

                    <!-- Breakdown Evaluasi Kecocokan -->
                    <div style="background: #f8fafc; border: 1px solid var(--line); border-radius: 8px; padding: 10px 12px; margin-bottom: 14px; font-size: 11.5px; color: var(--muted);">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span>Kompetensi:</span>
                            <strong style="color: var(--navy);">{{ $components['skills']['detail'] ?? '-' }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span>Gaya Kerja:</span>
                            <strong style="color: var(--navy);">{{ $components['workstyle']['detail'] ?? '-' }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Lokasi & Waktu:</span>
                            <strong style="color: var(--navy);">{{ $components['location']['detail'] ?? '-' }}</strong>
                        </div>
                    </div>

                    <div class="project-meta-pills">
                        <span class="meta-pill">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            {{ $p->location }} ({{ $p->work_type }})
                        </span>
                        <span class="meta-pill">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            {{ $p->schedule }}
                        </span>
                    </div>

                    <div class="skill-tags">
                        @foreach($p->skills ?? [] as $sk)
                            @php
                                $isMatched = in_array($sk, $p->match_data['matched_skills'] ?? []);
                            @endphp
                            <span class="skill-tag {{ $isMatched ? 'matched' : '' }}">
                                {{ $sk }} {{ $isMatched ? '✓' : '' }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="project-card-footer">
                    <span class="comp">{{ $p->compensation ?: 'Sesuai Kesepakatan' }}</span>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="openDetailModal({{ json_encode($p) }}, {{ $isApplied ? 'true' : 'false' }})">
                        Lihat Detail & Lamar &rarr;
                    </button>
                </div>
            </article>
        @endforeach
    </div>
@else
    <div style="background: #fff; padding: 40px; border-radius: 12px; text-align: center; border: 1px dashed var(--line);">
        <p style="color: var(--muted);">Tidak ada proyek yang sesuai dengan kriteria saat ini.</p>
    </div>
@endif

<!-- Project Detail & Apply Modal -->
<div id="projectModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div>
                <span id="modalProvider" style="font-size: 12px; font-weight: 700; color: var(--teal); text-transform: uppercase;"></span>
                <h2 id="modalTitle" style="margin-top: 4px;"></h2>
            </div>
            <button type="button" class="btn-close" onclick="closeDetailModal()">&times;</button>
        </div>

        <div style="margin-bottom: 20px;">
            <div id="modalScoreBox" style="display: flex; align-items: center; justify-content: space-between; background: var(--teal-light); padding: 12px 16px; border-radius: 10px; margin-bottom: 16px;">
                <span style="font-weight: 700; color: var(--teal-dark);">Kecocokan Gaya Kerja & Skill</span>
                <span id="modalScoreBadge" style="font-size: 16px; font-weight: 800; color: var(--teal-dark);"></span>
            </div>

            <h4 style="font-size: 13.5px; font-weight: 700; margin-bottom: 6px;">Deskripsi Proyek</h4>
            <p id="modalDesc" style="font-size: 13.5px; color: #475569; line-height: 1.6; margin-bottom: 16px;"></p>

            <h4 style="font-size: 13.5px; font-weight: 700; margin-bottom: 6px;">Tanggung Jawab</h4>
            <p id="modalResp" style="font-size: 13.5px; color: #475569; line-height: 1.6; margin-bottom: 16px;"></p>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; font-size: 13px;">
                <div style="background: #f8fafc; padding: 10px; border-radius: 8px;">
                    <strong>Lokasi:</strong> <span id="modalLoc"></span>
                </div>
                <div style="background: #f8fafc; padding: 10px; border-radius: 8px;">
                    <strong>Jadwal:</strong> <span id="modalSchedule"></span>
                </div>
                <div style="background: #f8fafc; padding: 10px; border-radius: 8px;">
                    <strong>Kompensasi:</strong> <span id="modalComp"></span>
                </div>
                <div style="background: #f8fafc; padding: 10px; border-radius: 8px;">
                    <strong>Batas Lamaran:</strong> <span id="modalDeadline"></span>
                </div>
            </div>

            <h4 style="font-size: 13.5px; font-weight: 700; margin-bottom: 8px;">Kompetensi yang Dibutuhkan</h4>
            <div id="modalSkills" class="skill-tags" style="margin-bottom: 24px;"></div>

            <form id="applyForm" method="POST" action="">
                @csrf
                <div id="applyInputs">
                    <div class="form-group">
                        <label for="message">Pesan Pengantar Lamaran (Opsional)</label>
                        <textarea name="message" id="message" class="form-control" placeholder="Ceritakan singkat motivasi dan pengalaman relevanmu..."></textarea>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn btn-secondary" onclick="closeDetailModal()">Batal</button>
                        <button type="submit" class="btn btn-primary">Kirim Lamaran Sekarang</button>
                    </div>
                </div>
                <div id="alreadyAppliedNotice" style="display: none; background: #f1f5f9; padding: 14px; border-radius: 8px; text-align: center; color: var(--muted); font-size: 13.5px; font-weight: 600;">
                    ✓ Anda sudah melamar ke proyek ini. Cek status di menu <strong>Lamaran Saya</strong>.
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openDetailModal(project, isApplied) {
    document.getElementById('modalProvider').innerText = project.provider_name;
    document.getElementById('modalTitle').innerText = project.title;
    document.getElementById('modalScoreBadge').innerText = (project.match_score || 0) + '% Cocok';
    document.getElementById('modalDesc').innerText = project.description || '-';
    document.getElementById('modalResp').innerText = project.responsibilities || '-';
    document.getElementById('modalLoc').innerText = (project.location || 'Remote') + ' (' + (project.work_type || 'Remote') + ')';
    document.getElementById('modalSchedule').innerText = project.schedule || 'Fleksibel';
    document.getElementById('modalComp').innerText = project.compensation || 'Sesuai Kesepakatan';
    document.getElementById('modalDeadline').innerText = project.deadline ? new Date(project.deadline).toLocaleDateString('id-ID') : '-';

    const skillsContainer = document.getElementById('modalSkills');
    skillsContainer.innerHTML = '';
    const skills = project.skills || [];
    skills.forEach(s => {
        const tag = document.createElement('span');
        tag.className = 'skill-tag';
        tag.innerText = s;
        skillsContainer.appendChild(tag);
    });

    const form = document.getElementById('applyForm');
    form.action = `/talent/proyek/${project.id}/lamar`;

    if (isApplied) {
        document.getElementById('applyInputs').style.display = 'none';
        document.getElementById('alreadyAppliedNotice').style.display = 'block';
    } else {
        document.getElementById('applyInputs').style.display = 'block';
        document.getElementById('alreadyAppliedNotice').style.display = 'none';
    }

    document.getElementById('projectModal').classList.add('active');
}

function closeDetailModal() {
    document.getElementById('projectModal').classList.remove('active');
}

document.getElementById('projectModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDetailModal();
    }
});
</script>
@endpush
@endsection

