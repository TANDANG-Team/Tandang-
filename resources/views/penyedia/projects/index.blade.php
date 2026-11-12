@extends('layouts.penyedia')

@section('title', 'Proyek Saya')

@section('content')
<div class="page-header">
    <div class="page-title-box">
        <h1>Proyek Saya</h1>
        <p>Kelola proyek milik usahamu.</p>
    </div>
    <div>
        <a href="{{ route('penyedia.projects.create') }}" class="btn-primary-action">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            <span>Buat Proyek</span>
        </a>
    </div>
</div>

<!-- Filter Tabs & Search Bar -->
<div style="background: #ffffff; border: 1px solid #e9edf2; border-radius: 14px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="{{ route('penyedia.projects.index', ['status' => 'semua', 'search' => $search]) }}"
           style="padding: 7px 14px; border-radius: 8px; font-size: 13.5px; font-weight: 600; text-decoration: none; {{ $currentStatus === 'semua' ? 'background: #0fa48f; color: #ffffff;' : 'background: #f3f4f6; color: #4b5563;' }}">
            Semua ({{ $counts['semua'] }})
        </a>
        <a href="{{ route('penyedia.projects.index', ['status' => 'draft', 'search' => $search]) }}"
           style="padding: 7px 14px; border-radius: 8px; font-size: 13.5px; font-weight: 600; text-decoration: none; {{ $currentStatus === 'draft' ? 'background: #0fa48f; color: #ffffff;' : 'background: #f3f4f6; color: #4b5563;' }}">
            Draf ({{ $counts['draft'] }})
        </a>
        <a href="{{ route('penyedia.projects.index', ['status' => 'published', 'search' => $search]) }}"
           style="padding: 7px 14px; border-radius: 8px; font-size: 13.5px; font-weight: 600; text-decoration: none; {{ $currentStatus === 'published' ? 'background: #0fa48f; color: #ffffff;' : 'background: #f3f4f6; color: #4b5563;' }}">
            Dipublikasikan ({{ $counts['published'] }})
        </a>
        <a href="{{ route('penyedia.projects.index', ['status' => 'closed', 'search' => $search]) }}"
           style="padding: 7px 14px; border-radius: 8px; font-size: 13.5px; font-weight: 600; text-decoration: none; {{ $currentStatus === 'closed' ? 'background: #0fa48f; color: #ffffff;' : 'background: #f3f4f6; color: #4b5563;' }}">
            Ditutup ({{ $counts['closed'] }})
        </a>
        <a href="{{ route('penyedia.projects.index', ['status' => 'archived', 'search' => $search]) }}"
           style="padding: 7px 14px; border-radius: 8px; font-size: 13.5px; font-weight: 600; text-decoration: none; {{ $currentStatus === 'archived' ? 'background: #0fa48f; color: #ffffff;' : 'background: #f3f4f6; color: #4b5563;' }}">
            Diarsipkan ({{ $counts['archived'] }})
        </a>
    </div>

    <form method="GET" action="{{ route('penyedia.projects.index') }}" style="display: flex; gap: 8px; min-width: 260px;">
        <input type="hidden" name="status" value="{{ $currentStatus }}">
        <input type="text" name="search" value="{{ $search }}" placeholder="Cari proyek..."
               style="flex: 1; padding: 8px 12px; border: 1.5px solid #e5e7eb; border-radius: 8px; font-size: 13.5px; outline: none;">
        <button type="submit" class="btn-secondary-action" style="padding: 8px 12px;">Cari</button>
    </form>
</div>

<!-- Project Table List -->
<div style="background: #ffffff; border: 1px solid #e9edf2; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
    @if($projects->isEmpty())
        <div style="text-align: center; padding: 60px 20px;">
            <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="#9ca3af" stroke-width="1.5" style="margin-bottom: 12px;">
                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
            </svg>
            <h3 style="font-size: 16px; font-weight: 700; color: #374151; margin: 0 0 6px;">Tidak ada proyek yang ditemukan</h3>
            <p style="font-size: 13.5px; color: #6b7280; margin: 0 0 16px;">Mulai dengan membuat proyek baru untuk usahamu sekarang.</p>
            <a href="{{ route('penyedia.projects.create') }}" class="btn-primary-action">+ Buat Proyek Baru</a>
        </div>
    @else
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px;">
                <thead>
                    <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-weight: 600; font-size: 12.5px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <th style="padding: 14px 18px;">Proyek</th>
                        <th style="padding: 14px 18px;">Kategori</th>
                        <th style="padding: 14px 18px;">Lokasi</th>
                        <th style="padding: 14px 18px;">Periode</th>
                        <th style="padding: 14px 18px;">Pelamar</th>
                        <th style="padding: 14px 18px;">Status</th>
                        <th style="padding: 14px 18px;">Diperbarui</th>
                        <th style="padding: 14px 18px; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody style="divide-y divide-gray-100;">
                    @foreach($projects as $project)
                        <tr style="border-bottom: 1px solid #f3f4f6; transition: background 0.15s;">
                            <td style="padding: 16px 18px;">
                                <div style="font-weight: 700; color: #111827; font-size: 14px;">{{ $project->title }}</div>
                                <div style="font-size: 12px; color: #6b7280; margin-top: 2px;">{{ Str::limit($project->description, 50) }}</div>
                            </td>
                            <td style="padding: 16px 18px; color: #374151;">{{ $project->category }}</td>
                            <td style="padding: 16px 18px; color: #374151;">{{ $project->location }}</td>
                            <td style="padding: 16px 18px; color: #4b5563; font-size: 12.5px;">
                                {{ $project->start_date?->format('d/m/Y') }} &ndash; {{ $project->end_date?->format('d/m/Y') }}
                            </td>
                            <td style="padding: 16px 18px;">
                                <a href="{{ route('penyedia.applicants.index', ['project_id' => $project->id]) }}"
                                   style="font-weight: 700; color: #2563eb; text-decoration: underline;">
                                    {{ $project->applications_count }} pelamar
                                </a>
                            </td>
                            <td style="padding: 16px 18px;">
                                <span class="badge-status badge-{{ $project->status }}">
                                    {{ $project->status_label }}
                                </span>
                            </td>
                            <td style="padding: 16px 18px; color: #6b7280; font-size: 12.5px;">
                                {{ $project->updated_at->format('d/m/Y') }}
                            </td>
                            <td style="padding: 16px 18px; text-align: right;">
                                <div style="display: inline-flex; align-items: center; gap: 8px;">
                                    <a href="{{ route('penyedia.projects.edit', $project) }}" class="btn-secondary-action" style="padding: 5px 10px; font-size: 12px;">
                                        Edit
                                    </a>

                                    @if($project->isDraft())
                                        <form action="{{ route('penyedia.projects.status', $project) }}" method="POST" style="margin: 0;">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="published">
                                            <button type="submit" class="btn-primary-action" style="padding: 5px 10px; font-size: 12px;">
                                                Publikasikan
                                            </button>
                                        </form>
                                    @elseif($project->isPublished())
                                        <form action="{{ route('penyedia.projects.status', $project) }}" method="POST" style="margin: 0;">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="closed">
                                            <button type="submit" class="btn-secondary-action" style="padding: 5px 10px; font-size: 12px; color: #dc2626; border-color: #fecaca;">
                                                Tutup
                                            </button>
                                        </form>
                                    @endif

                                    <!-- Delete Button -->
                                    <form action="{{ route('penyedia.projects.destroy', $project) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus proyek ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="background: none; border: none; color: #9ca3af; cursor: pointer; padding: 6px;" title="Hapus proyek">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($projects->hasPages())
            <div style="padding: 16px 20px; border-top: 1px solid #e5e7eb;">
                {{ $projects->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
