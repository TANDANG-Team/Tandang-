@extends('talent.layout')

@section('title', 'Cari Proyek')

@section('content')
<div class="page-head">
    <div>
        <h1>Katalog & Pencarian Proyek</h1>
        <p>Jelajahi berbagai proyek nyata dari UMKM dan mitra terverifikasi.</p>
    </div>
</div>

<!-- Filter Bar -->
<form method="GET" action="{{ route('talent.cari') }}" class="filter-bar">
    <div class="search-input-wrap">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari judul proyek, penyedia, atau kata kunci...">
    </div>

    <select name="category" class="filter-select">
        <option value="Semua">Semua Kategori</option>
        @foreach($categories as $cat)
            <option value="{{ $cat }}" {{ ($filters['category'] ?? '') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
        @endforeach
    </select>

    <select name="work_type" class="filter-select">
        <option value="Semua">Semua Tipe Kerja</option>
        <option value="Remote" {{ ($filters['work_type'] ?? '') === 'Remote' ? 'selected' : '' }}>Remote</option>
        <option value="Onsite" {{ ($filters['work_type'] ?? '') === 'Onsite' ? 'selected' : '' }}>Onsite</option>
        <option value="Hybrid" {{ ($filters['work_type'] ?? '') === 'Hybrid' ? 'selected' : '' }}>Hybrid</option>
    </select>

    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <a href="{{ route('talent.cari') }}" class="btn btn-secondary btn-sm">Reset</a>
</form>

<div style="margin-bottom: 18px; font-size: 13.5px; color: var(--muted);">
    Ditemukan <strong>{{ $projects->count() }}</strong> proyek aktif.
</div>

@if($projects->count() > 0)
    <div class="project-grid">
        @foreach($projects as $p)
            @php
                $isApplied = in_array($p->id, $appliedProjectIds);
                $scoreClass = $p->match_score >= 80 ? 'high' : ($p->match_score >= 60 ? 'medium' : 'low');
            @endphp
            <article class="project-card">
                <div>
                    <div class="project-card-top">
                        <span class="provider">{{ $p->provider_name }}</span>
                        <span class="match-score-badge {{ $scoreClass }}">
                            {{ $p->match_score }}% Cocok
                        </span>
                    </div>

                    <h3>{{ $p->title }}</h3>
                    <p class="desc">{{ $p->description }}</p>

                    <div class="project-meta-pills">
                        <span class="meta-pill">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            {{ $p->location }} ({{ $p->work_type }})
                        </span>
                        <span class="meta-pill">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            {{ $p->schedule }}
                        </span>
                        <span class="meta-pill">
                            Kuota: {{ $p->quota }}
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
        <p style="color: var(--muted);">Tidak ada proyek yang sesuai dengan kriteria pencarian Anda.</p>
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

