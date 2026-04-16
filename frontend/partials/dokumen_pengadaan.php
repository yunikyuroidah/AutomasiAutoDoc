<article class="card form-section" data-section="dokumen_pengadaan">
  <header>
    <div>
      <p class="eyebrow">Dokumen Pengadaan</p>
      <h2>Dokumen Pengadaan</h2>
    </div>
  </header>
  <div class="section-grid">
    <label>Nama paket
      <input type="text" data-field="dokumen_pengadaan.nama_paket" required />
    </label>
    <label>Kode RUP
      <input type="text" data-field="dokumen_pengadaan.kode_rup" required />
    </label>
    <label>Nomor DPP
      <input type="text" data-field="dokumen_pengadaan.nomor_dpp" placeholder="Misal: 123/DP3" />
    </label>
    <label>Pagu anggaran
      <input type="number" min="0" step="100" data-field="dokumen_pengadaan.pagu_anggaran" required />
    </label>
    <label>Tanggal mulai
      <input type="date" data-field="dokumen_pengadaan.tanggal_mulai" required />
    </label>
    <label>Tanggal selesai
      <input type="date" data-field="dokumen_pengadaan.tanggal_selesai" required />
    </label>
    <label>Pembuat komitmen
      <select data-field="dokumen_pengadaan.pembuat_komitmen_id" id="selectDokumenPpk" required></select>
    </label>
  </div>
</article>
