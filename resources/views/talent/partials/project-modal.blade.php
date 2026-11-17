{{--
    Modal detail proyek + form lamar, dipakai di beranda, rekomendasi, dan cari proyek.
    Status tombol Apply berasal dari ProjectApplyService (dikirim saat render, lalu di-refetch
    dari route talent.lamar.status setiap kali modal dibuka agar kuota selalu terkini).
--}}
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
            <div id="modalSkills" class="skill-tags" style="margin-bottom: 20px;"></div>

            <!-- Slot pelamar (aktif = menunggu + review + diterima; ditolak tidak dihitung) -->
            <div id="modalSlots" style="display: none; margin-bottom: 16px; font-size: 13px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span><strong>Slot pelamar:</strong> <span id="modalSlotsText"></span></span>
                    <span id="modalSlotsRemaining" style="color: var(--muted); font-weight: 600;"></span>
                </div>
                <div style="height: 6px; background: #e2e8f0; border-radius: 999px; overflow: hidden;">
                    <div id="modalSlotsBar" style="height: 100%; width: 0; background: var(--teal); transition: width .2s;"></div>
                </div>
            </div>

            <!-- Form Apply -->
            <form id="applyForm" method="POST" action="">
                @csrf
                <div id="applyInputs">
                    <div class="form-group">
                        <label for="message">Pesan Pengantar Lamaran (Opsional)</label>
                        <textarea name="message" id="message" class="form-control" placeholder="Ceritakan singkat motivasi dan pengalaman relevanmu..."></textarea>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn btn-secondary" onclick="closeDetailModal()">Batal</button>
                        <button type="submit" id="applySubmit" class="btn btn-primary">Kirim Lamaran Sekarang</button>
                    </div>
                </div>
                <div id="applyBlockedNotice" role="status" style="display: none; padding: 14px; border-radius: 8px; text-align: center; font-size: 13.5px; font-weight: 600;">
                    <div id="applyBlockedLabel" style="font-weight: 800; margin-bottom: 4px;"></div>
                    <div id="applyBlockedMessage" style="font-weight: 500;"></div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
const APPLY_NOTICE_STYLES = {
    already_applied:     { background: '#f1f5f9', color: 'var(--muted)' },
    rejected:            { background: 'var(--danger-light)', color: '#991b1b' },
    cancelled:           { background: 'var(--purple-light)', color: '#6d28d9' },
    quota_full:          { background: 'var(--amber-light)', color: '#92400e' },
    registration_closed: { background: 'var(--amber-light)', color: '#92400e' },
};
let currentModalProjectId = null;

function renderApplyStatus(status) {
    const inputs = document.getElementById('applyInputs');
    const notice = document.getElementById('applyBlockedNotice');
    const slots = document.getElementById('modalSlots');
    const submit = document.getElementById('applySubmit');

    if (!status) {
        slots.style.display = 'none';
        inputs.style.display = 'block';
        notice.style.display = 'none';
        submit.disabled = false;
        return;
    }

    const max = status.max_applicants || 0;
    const pct = max > 0 ? Math.min(100, Math.round(status.active_count / max * 100)) : 0;
    document.getElementById('modalSlotsText').innerText = `${status.active_count}/${max} pelamar`;
    document.getElementById('modalSlotsRemaining').innerText = `${status.remaining_slots} slot tersisa`;
    document.getElementById('modalSlotsBar').style.width = pct + '%';
    slots.style.display = 'block';

    if (status.can_apply) {
        inputs.style.display = 'block';
        notice.style.display = 'none';
        submit.disabled = false;
        return;
    }

    const style = APPLY_NOTICE_STYLES[status.reason] || APPLY_NOTICE_STYLES.already_applied;
    notice.style.background = style.background;
    notice.style.color = style.color;
    document.getElementById('applyBlockedLabel').innerText = status.reason_label || 'Tidak dapat melamar';
    document.getElementById('applyBlockedMessage').innerText = status.message || '';
    inputs.style.display = 'none';
    notice.style.display = 'block';
    submit.disabled = true;
}

function openDetailModal(project, applyStatus) {
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
    (project.skills || []).forEach(s => {
        const tag = document.createElement('span');
        tag.className = 'skill-tag';
        tag.innerText = s;
        skillsContainer.appendChild(tag);
    });

    document.getElementById('applyForm').action = `/talent/proyek/${project.id}/lamar`;

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

// Cegah klik ganda mengirim dua request.
document.getElementById('applyForm').addEventListener('submit', function() {
    document.getElementById('applySubmit').disabled = true;
});
</script>
@endpush
