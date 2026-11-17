{{-- Form "Saya Sudah Bayar". $late: konfirmasi setelah batas waktu (ditinjau Admin, tidak aktif otomatis). --}}
<form method="POST" action="{{ route('penyedia.promotions.proof.submit', $promotion) }}" enctype="multipart/form-data" class="pay-form">
    @csrf
    <div class="full">
        <label for="proof{{ $late ? 'Late' : '' }}">Bukti pembayaran <span style="color:#dc2626;">*</span></label>
        <div class="pay-file">
            <input type="file" id="proof{{ $late ? 'Late' : '' }}" name="proof" accept=".jpg,.jpeg,.png,.pdf" required>
        </div>
        <div class="hint">JPG, JPEG, PNG, atau PDF · maks. 2 MB · disimpan privat, hanya kamu dan Admin yang bisa melihat.</div>
    </div>
    <div>
        <label for="payer_amount{{ $late ? 'Late' : '' }}">Nominal yang dibayar (Rp) <span style="color:#dc2626;">*</span></label>
        <input type="number" id="payer_amount{{ $late ? 'Late' : '' }}" name="payer_amount" min="1" step="1" inputmode="numeric" required value="{{ old('payer_amount', $transaction->amount) }}">
    </div>
    <div>
        <label for="payer_paid_at{{ $late ? 'Late' : '' }}">Waktu pembayaran (WIB)</label>
        <input type="datetime-local" id="payer_paid_at{{ $late ? 'Late' : '' }}" name="payer_paid_at" value="{{ old('payer_paid_at') }}">
    </div>
    <div>
        <label for="payer_reference{{ $late ? 'Late' : '' }}">Nomor referensi / ID transaksi</label>
        <input type="text" id="payer_reference{{ $late ? 'Late' : '' }}" name="payer_reference" maxlength="100" value="{{ old('payer_reference') }}" placeholder="Opsional, dari struk aplikasi">
    </div>
    <div>
        <label for="payer_name{{ $late ? 'Late' : '' }}">Nama pengirim</label>
        <input type="text" id="payer_name{{ $late ? 'Late' : '' }}" name="payer_name" maxlength="150" value="{{ old('payer_name', auth()->user()->name) }}">
    </div>
    <div class="full" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <button type="submit" class="tdg-btn primary">{{ $late ? 'Kirim Konfirmasi Terlambat' : 'Saya Sudah Bayar — Kirim Bukti' }}</button>
        <span class="hint" style="margin:0;">Mengirim bukti tidak langsung mengaktifkan promosi.</span>
    </div>
</form>
