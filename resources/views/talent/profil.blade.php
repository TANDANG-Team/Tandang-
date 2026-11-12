@extends('talent.layout')

@section('title', 'Profil & Portofolio')

@section('content')
<div class="page-head">
    <div>
        <h1>Profil & Portofolio</h1>
        <p>Kelola identitas, keahlian, dan hasil karya terbaikmu untuk memikat penyedia proyek.</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1.3fr 1fr; gap: 24px;">
    <!-- Bagian Kiri: Form Biodata & Preferensi -->
    <div>
        <div class="table-card" style="padding: 24px; margin-bottom: 24px;">
            <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 18px; color: var(--navy);">Informasi Utama</h3>

            <form method="POST" action="{{ route('talent.profil.update') }}">
                @csrf
                <div class="form-group">
                    <label for="name">Nama Lengkap</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Akun (Terkunci)</label>
                    <input type="email" id="email" class="form-control" value="{{ $user->email }}" disabled style="background: #f1f5f9; color: #64748b;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div class="form-group">
                        <label for="institution">Instansi / Kampus / Sekolah</label>
                        <input type="text" name="institution" id="institution" class="form-control" value="{{ old('institution', $talent->institution) }}" placeholder="Contoh: Universitas Indonesia, SMKN 1 Cimahi">
                    </div>
                    <div class="form-group">
                        <label for="major">Jurusan / Program Studi</label>
                        <input type="text" name="major" id="major" class="form-control" value="{{ old('major', $talent->major) }}" placeholder="Contoh: Ilmu Komputer, DKV">
                    </div>
                </div>

                <div class="form-group">
                    <label for="city">Kota Domisili</label>
                    <input type="text" name="city" id="city" class="form-control" value="{{ old('city', $talent->city) }}" placeholder="Contoh: Bandung, Jakarta, Depok" required>
                </div>

                <div class="form-group">
                    <label for="bio">Bio Singkat</label>
                    <textarea name="bio" id="bio" class="form-control" placeholder="Tuliskan latar belakang singkat dan ketertarikanmu...">{{ old('bio', $talent->bio) }}</textarea>
                </div>

                <div class="form-group">
                    <label for="skills">Keahlian & Kompetensi (Pisahkan dengan koma)</label>
                    @php
                        $skillsStr = is_array($talent->skills) ? implode(', ', $talent->skills) : ($talent->skills ?? '');
                    @endphp
                    <input type="text" name="skills" id="skills" class="form-control" value="{{ old('skills', $skillsStr) }}" placeholder="Contoh: HTML/CSS, Laravel, Figma, Copywriting">
                    <small style="color: var(--muted); font-size: 11.5px; margin-top: 4px; display: block;">Skill ini digunakan untuk pencocokan otomatis dengan lowongan proyek.</small>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div class="form-group">
                        <label for="availability">Ketersediaan Waktu</label>
                        <select name="availability" id="availability" class="form-control">
                            <option value="Fleksibel" {{ $talent->availability === 'Fleksibel' ? 'selected' : '' }}>Fleksibel</option>
                            <option value="Akhir pekan" {{ $talent->availability === 'Akhir pekan' ? 'selected' : '' }}>Akhir pekan</option>
                            <option value="Hari kerja" {{ $talent->availability === 'Hari kerja' ? 'selected' : '' }}>Hari kerja</option>
                            <option value="Penuh waktu" {{ $talent->availability === 'Penuh waktu' ? 'selected' : '' }}>Penuh waktu</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="work_mode">Preferensi Mode Kerja</label>
                        <select name="work_mode" id="work_mode" class="form-control">
                            <option value="Keduanya" {{ $talent->work_mode === 'Keduanya' ? 'selected' : '' }}>Remote & Onsite (Keduanya)</option>
                            <option value="Remote" {{ $talent->work_mode === 'Remote' ? 'selected' : '' }}>Remote Saja</option>
                            <option value="Onsite" {{ $talent->work_mode === 'Onsite' ? 'selected' : '' }}>Onsite Saja</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top: 8px;">Simpan Perubahan Profil</button>
            </form>
        </div>
    </div>

    <!-- Bagian Kanan: Portofolio Karya -->
    <div>
        <div class="table-card" style="padding: 24px; margin-bottom: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                <h3 style="font-size: 16px; font-weight: 700; color: var(--navy);">Galeri Portofolio</h3>
                <span class="badge badge-info">{{ count($talent->portfolios ?? []) }} Karya</span>
            </div>

            <!-- Form Tambah Portofolio Baru -->
            <form method="POST" action="{{ route('talent.profil.tambah-portofolio') }}" style="background: #f8fafc; border: 1px dashed var(--line); border-radius: 10px; padding: 16px; margin-bottom: 20px;">
                @csrf
                <div class="form-group" style="margin-bottom: 10px;">
                    <label for="portfolio_title" style="font-size: 12px;">Judul Karya / Proyek</label>
                    <input type="text" name="title" id="portfolio_title" class="form-control" placeholder="Contoh: Redesain Aplikasi Mobile Kasir" required style="font-size: 13px;">
                </div>
                <div class="form-group" style="margin-bottom: 10px;">
                    <label for="portfolio_desc" style="font-size: 12px;">Deskripsi Singkat (Opsional)</label>
                    <textarea name="description" id="portfolio_desc" class="form-control" rows="2" maxlength="1000" placeholder="Peranmu, tools yang dipakai, hasil yang dicapai..." style="font-size: 13px;">{{ old('description') }}</textarea>
                </div>
                <div class="form-group" style="margin-bottom: 10px;">
                    <label for="portfolio_url" style="font-size: 12px;">Link Karya (Opsional)</label>
                    <input type="url" name="url" id="portfolio_url" class="form-control" value="{{ old('url') }}" placeholder="https://behance.net/... / github.com/... / drive.google.com/..." style="font-size: 13px;">
                </div>
                <div class="form-group" style="margin-bottom: 14px;">
                    <label for="portfolio_image" style="font-size: 12px;">URL Gambar / Sampul Portofolio (Opsional)</label>
                    <input type="url" name="image" id="portfolio_image" class="form-control" value="{{ old('image') }}" placeholder="https://..." style="font-size: 13px;">
                </div>
                @if($errors->has('url') || $errors->has('image') || $errors->has('title'))
                    <p style="font-size: 12px; color: var(--danger); margin: -4px 0 10px;">{{ $errors->first('title') ?: ($errors->first('url') ?: $errors->first('image')) }}</p>
                @endif
                <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%; justify-content: center;">+ Tambah ke Portofolio</button>
            </form>

            <!-- List Portofolio Items -->
            @php $presented = \App\Models\TalentProfile::presentPortfolios($talent->portfolios ?? []); @endphp
            @if(count($presented) > 0)
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    @foreach($presented as $item)
                        <div style="border: 1px solid var(--line); border-radius: 8px; padding: 10px; background: #fff;">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                                <div style="display: flex; align-items: center; gap: 12px; overflow: hidden; min-width: 0;">
                                    @if($item['image'])
                                        <img src="{{ $item['image'] }}" alt="{{ $item['title'] }}" style="width: 48px; height: 48px; border-radius: 6px; object-fit: cover; flex-shrink: 0; background: #e2e8f0;">
                                    @else
                                        <div style="width: 48px; height: 48px; border-radius: 6px; flex-shrink: 0; background: var(--teal-light); color: var(--teal-dark); display: grid; place-items: center;">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                                        </div>
                                    @endif
                                    <div style="min-width: 0;">
                                        <strong style="display: block; font-size: 13px; color: var(--navy); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $item['title'] }}</strong>
                                        @if($item['link'])
                                            <a href="{{ $item['link'] }}" target="_blank" rel="noopener noreferrer" style="font-size: 11.5px; color: var(--teal); font-weight: 600;">{{ $item['domain'] }} &#8599;</a>
                                        @else
                                            <span style="font-size: 11.5px; color: #d97706; font-weight: 600;">Belum ada link &mdash; penyedia tidak bisa membuka karya ini</span>
                                        @endif
                                    </div>
                                </div>
                                <div style="display: flex; gap: 2px; flex-shrink: 0;">
                                    <button type="button" onclick="document.getElementById('pf-edit-{{ $loop->index }}').toggleAttribute('hidden')" style="background: transparent; border: none; color: var(--muted); cursor: pointer; padding: 4px;" title="Edit">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                                    </button>
                                    <form method="POST" action="{{ route('talent.profil.hapus-portofolio', $item['id']) }}" onsubmit="return confirm('Hapus item portofolio ini?')" style="margin: 0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="background: transparent; border: none; color: var(--danger); cursor: pointer; padding: 4px;" title="Hapus">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <form id="pf-edit-{{ $loop->index }}" method="POST" action="{{ route('talent.profil.ubah-portofolio', $item['id']) }}" {{ $item['link'] ? 'hidden' : '' }}
                                  style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--line);">
                                @csrf
                                @method('PUT')
                                <div class="form-group" style="margin-bottom: 8px;">
                                    <label style="font-size: 11.5px;">Judul</label>
                                    <input type="text" name="title" value="{{ $item['title'] }}" class="form-control" required style="font-size: 12.5px;">
                                </div>
                                <div class="form-group" style="margin-bottom: 8px;">
                                    <label style="font-size: 11.5px;">Deskripsi Singkat</label>
                                    <textarea name="description" class="form-control" rows="2" maxlength="1000" style="font-size: 12.5px;">{{ $item['description'] }}</textarea>
                                </div>
                                <div class="form-group" style="margin-bottom: 8px;">
                                    <label style="font-size: 11.5px;">Link Karya</label>
                                    <input type="url" name="url" value="{{ $item['link'] }}" class="form-control" placeholder="https://..." style="font-size: 12.5px;">
                                </div>
                                <div class="form-group" style="margin-bottom: 10px;">
                                    <label style="font-size: 11.5px;">URL Gambar Sampul</label>
                                    <input type="url" name="image" value="{{ $item['image'] }}" class="form-control" placeholder="https://... (.jpg / .png)" style="font-size: 12.5px;">
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm" style="width: 100%; justify-content: center;">Simpan Perubahan</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @else
                <p style="font-size: 13px; color: var(--muted); text-align: center; padding: 20px 0;">Belum ada portofolio yang ditambahkan.</p>
            @endif
        </div>
    </div>
</div>
@endsection

