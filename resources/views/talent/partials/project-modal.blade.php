{{--
    Modal "Konfirmasi Lamaran", dipakai di beranda, rekomendasi, dan cari proyek.
    Tampilan mengikuti desain Figma (dialog-message, detail-box, match-breakdown).
    Status tombol Lamar berasal dari ProjectApplyService (dikirim saat render, lalu di-refetch
    dari route talent.lamar.status setiap kali modal dibuka agar kuota selalu terkini).
--}}
<div id="projectModal" class="modal-overlay">
    <div class="modal-box apply-modal" role="dialog" aria-modal="true" aria-labelledby="applyModalTitle">
        <div class="modal-header">
            <h2 id="applyModalTitle">Konfirmasi Lamaran</h2>
            <button type="button" class="apply-modal-close" onclick="closeDetailModal()" aria-label="Tutup dialog">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <div id="modalCoverWrap" class="apply-cover" hidden>
            <img id="modalCover" src="" alt="">
        </div>

        <!-- Ringkasan proyek -->
        <div class="apply-summary">
            <span class="apply-summary-icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="7" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
            </span>
            <div>
                <h3 id="modalTitle"></h3>
                <p id="modalSubtitle"></p>
                <a id="modalProviderLink" class="apply-provider-link" href="#" hidden></a>
            </div>
        </div>

        <p class="apply-intro">Pastikan informasi lamaranmu sudah sesuai sebelum dikirim. Profil dan portofolio akan otomatis dilampirkan.</p>

        <p id="modalEyebrow" class="apply-eyebrow"></p>
        <p id="modalDesc" class="apply-text"></p>

        <div id="modalRespBlock">
            <h4 class="apply-subhead">Tanggung jawab</h4>
            <p id="modalResp" class="apply-text"></p>
        </div>

        <div id="modalSkills" class="apply-tags"></div>

        <!-- Kompensasi, jadwal, batas daftar -->
        <div class="apply-detail-box">
            <b id="modalComp"></b>
            <p id="modalWhere"></p>
            <p id="modalDates"></p>
            <p id="modalDeadline"></p>
        </div>

        <!-- Slot pelamar (aktif = menunggu + review + diterima; ditolak/dibatalkan tidak dihitung) -->
        <div id="modalSlots" class="apply-slots" hidden>
            <div class="apply-slots-head">
                <span><strong>Slot pelamar</strong> <span id="modalSlotsText"></span></span>
                <span id="modalSlotsRemaining" class="apply-muted"></span>
            </div>
            <div class="apply-slots-track"><div id="modalSlotsBar" class="apply-slots-bar"></div></div>
        </div>

        <!-- Rincian kecocokan -->
        <div id="modalMatch" class="apply-match" hidden>
            <div class="apply-match-head">
                <div>
                    <span id="modalMatchScore" class="apply-match-score"></span>
                    <b>Cocok dengan profilmu</b>
                </div>
                <small id="modalMatchNote"></small>
            </div>
            <div id="modalMatchFactors"></div>
        </div>

        <!-- Form Apply -->
        <form id="applyForm" method="POST" action="">
            @csrf
            <div id="applyInputs" class="form-group">
                <label for="message">Pesan lamaran (opsional)</label>
                <textarea name="message" id="message" class="form-control" rows="3" placeholder="Ceritakan singkat motivasi dan pengalaman relevanmu..."></textarea>
            </div>

            <div id="applyBlockedNotice" class="apply-blocked" role="status" hidden>
                <div id="applyBlockedLabel" class="apply-blocked-label"></div>
                <div id="applyBlockedMessage"></div>
            </div>

            <div class="apply-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDetailModal()">Tutup</button>
                <button type="submit" id="applySubmit" class="btn btn-primary">Lamar Proyek</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
const APPLY_NOTICE_CLASS = {
    already_applied: 'is-neutral',
    cancelled: 'is-neutral',
    rejected: 'is-danger',
    quota_full: 'is-warning',
    registration_closed: 'is-warning',
};
const WORK_METHOD_LABEL = { onsite: 'Onsite', hybrid: 'Hybrid', remote: 'Remote' };
let currentModalProjectId = null;

// Tanggal dari server berbentuk "YYYY-MM-DD..." — ambil bagian tanggalnya saja agar tidak bergeser zona waktu.
function formatTanggal(value) {
    if (!value) return null;
    const m = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (!m) return null;
    return new Date(+m[1], +m[2] - 1, +m[3]).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

function setText(id, text) {
    const el = document.getElementById(id);
    el.textContent = text || '';
    el.hidden = !text;
}

function renderApplyStatus(status) {
    const inputs = document.getElementById('applyInputs');
    const notice = document.getElementById('applyBlockedNotice');
    const slots = document.getElementById('modalSlots');
    const submit = document.getElementById('applySubmit');

    if (!status) {
        slots.hidden = true;
        inputs.hidden = false;
        notice.hidden = true;
        submit.disabled = false;
        submit.textContent = 'Lamar Proyek';
        return;
    }

    const max = status.max_applicants || 0;
    const pct = max > 0 ? Math.min(100, Math.round(status.active_count / max * 100)) : 0;
    document.getElementById('modalSlotsText').textContent = `${status.active_count}/${max} pelamar`;
    document.getElementById('modalSlotsRemaining').textContent = `${status.remaining_slots} slot tersisa`;
    document.getElementById('modalSlotsBar').style.width = pct + '%';
    slots.hidden = false;

    if (status.can_apply) {
        inputs.hidden = false;
        notice.hidden = true;
        submit.disabled = false;
        submit.textContent = 'Lamar Proyek';
        return;
    }

    notice.className = 'apply-blocked ' + (APPLY_NOTICE_CLASS[status.reason] || 'is-neutral');
    document.getElementById('applyBlockedLabel').textContent = status.reason_label || 'Tidak dapat melamar';
    document.getElementById('applyBlockedMessage').textContent = status.message || '';
    inputs.hidden = true;
    notice.hidden = false;
    submit.disabled = true;
    submit.textContent = status.reason_label || 'Tidak dapat melamar';
}

function renderMatch(project) {
    const box = document.getElementById('modalMatch');
    const match = project.match_data;
    if (!match || match.score === undefined) {
        box.hidden = true;
        return;
    }

    document.getElementById('modalMatchScore').textContent = (match.score || 0) + '%';
    document.getElementById('modalMatchNote').textContent = match.is_partial
        ? 'Skor sementara. Lengkapi profil dan asesmen gaya kerja agar hasilnya lebih akurat.'
        : 'Dihitung dari empat faktor profil.';

    const list = document.getElementById('modalMatchFactors');
    list.innerHTML = '';
    Object.values(match.components || {}).forEach(c => {
        const row = document.createElement('div');
        row.className = 'apply-match-factor';
        const info = document.createElement('div');
        const label = document.createElement('b');
        label.textContent = c.label;
        const detail = document.createElement('span');
        detail.textContent = c.detail || '';
        info.append(label, detail);
        const value = document.createElement('strong');
        value.textContent = (c.value === null || c.value === undefined) ? 'Belum tersedia' : Math.round(c.value) + '%';
        row.append(info, value);
        list.appendChild(row);
    });
    box.hidden = false;
}

function openDetailModal(project, applyStatus) {
    const workMethod = WORK_METHOD_LABEL[project.work_method] || project.work_type || 'Remote';
    const location = project.location || 'Remote';

    document.getElementById('modalTitle').textContent = project.title;
    document.getElementById('modalSubtitle').textContent = `${project.provider_name || '-'} · ${location}`;

    const coverWrap = document.getElementById('modalCoverWrap');
    document.getElementById('modalCover').src = project.cover_url || '';
    coverWrap.hidden = !project.cover_url;

    // Rating Penyedia hanya dikirim di halaman Cari; di halaman lain cukup tautan profil.
    const providerLink = document.getElementById('modalProviderLink');
    providerLink.hidden = !project.user_id;
    if (project.user_id) {
        providerLink.href = '{{ url('/talent/penyedia') }}/' + project.user_id;
        let rating = '';
        if (project.provider_rating !== undefined) {
            rating = project.provider_rating !== null
                ? `★ ${Number(project.provider_rating).toFixed(1).replace('.', ',')} / 5 (${project.provider_rating_count} penilaian) · `
                : 'Belum Ada Penilaian · ';
        }
        providerLink.textContent = rating + 'Lihat profil Penyedia';
    }
    setText('modalEyebrow', [project.provider_name, project.category].filter(Boolean).join(' · '));
    document.getElementById('modalDesc').textContent = project.description || '-';
    document.getElementById('modalResp').textContent = project.responsibilities || '';
    document.getElementById('modalRespBlock').hidden = !project.responsibilities;

    const skillsContainer = document.getElementById('modalSkills');
    skillsContainer.innerHTML = '';
    const matched = (project.match_data && project.match_data.matched_skills) || [];
    (project.skills || []).forEach(s => {
        const tag = document.createElement('span');
        tag.className = 'apply-tag' + (matched.includes(s) ? ' is-matched' : '');
        tag.textContent = matched.includes(s) ? s + ' ✓' : s;
        skillsContainer.appendChild(tag);
    });
    skillsContainer.hidden = !skillsContainer.children.length;

    document.getElementById('modalComp').textContent = project.compensation || 'Sesuai kesepakatan';
    const where = location.toLowerCase() === workMethod.toLowerCase() ? location : `${location} (${workMethod})`;
    document.getElementById('modalWhere').textContent = [where, project.schedule || project.availability].filter(Boolean).join(' · ');
    const start = formatTanggal(project.start_date);
    const end = formatTanggal(project.end_date);
    setText('modalDates', start ? `Pelaksanaan: ${start}${end ? ' – ' + end : ''}` : '');
    const deadline = formatTanggal(project.registration_deadline || project.deadline);
    document.getElementById('modalDeadline').textContent = `Batas daftar: ${deadline || 'Tidak ditentukan'}`;

    renderMatch(project);

    document.getElementById('applyForm').action = `/talent/proyek/${project.id}/lamar`;
    document.getElementById('message').value = '';

    // Tampilkan status saat render dulu, lalu refetch agar kuota tidak basi.
    currentModalProjectId = project.id;
    renderApplyStatus(applyStatus);
    fetch(`/talent/proyek/${project.id}/status-lamaran`, {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
    })
        .then(r => r.ok ? r.json() : null)
        .then(fresh => {
            if (fresh && currentModalProjectId === project.id) {
                renderApplyStatus(fresh);
            }
        })
        .catch(() => {});

    document.getElementById('projectModal').classList.add('active');
    document.querySelector('#projectModal .apply-modal-close').focus();
}

function closeDetailModal() {
    currentModalProjectId = null;
    document.getElementById('projectModal').classList.remove('active');
}

document.getElementById('projectModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDetailModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('projectModal').classList.contains('active')) {
        closeDetailModal();
    }
});

// Cegah klik ganda mengirim dua request.
document.getElementById('applyForm').addEventListener('submit', function() {
    document.getElementById('applySubmit').disabled = true;
});
</script>
@endpush
