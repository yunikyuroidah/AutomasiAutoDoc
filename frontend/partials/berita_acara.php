<article class="card form-section" data-section="berita_acara">
  <header>
    <div>
      <p class="eyebrow">Dokumen 1</p>
      <h2>Berita Acara</h2>
    </div>
  </header>
  <div class="section-grid">
    <label>Nomor Satuan Kerja <input type="text" data-field="berita_acara.nomor_satuan_kerja" required /></label>
    <label>Tanggal Satuan Kerja <input type="date" data-field="berita_acara.tanggal_satuan_kerja" required /></label>
    <label>Nomor SP <input type="text" data-field="berita_acara.nomor_sp" required /></label>
    <label>Tanggal SP <input type="date" data-field="berita_acara.tanggal_sp" required /></label>
    <label>Kepala Dinas <select data-field="berita_acara.kepdin_id" id="selectBeritaKepdin" required></select></label>
    <label>Pembuat Komitmen <select data-field="berita_acara.pembuat_komitmen_id" id="selectBeritaPpk" required></select></label>
    <label>Penyedia <select data-field="berita_acara.penyedia_id" id="selectBeritaPenyedia"></select></label>
  </div>

  <div class="form-subsection">
    <p class="eyebrow">Data BAHP</p>
    <div class="section-grid">
      <label>Nomor SK BAHP <input type="text" data-field="berita_acara.nomor_sk_bahp" /></label>
      <label>Tanggal SK BAHP <input type="date" data-field="berita_acara.tanggal_sk_bahp" /></label>
      <label>Nomor SP BAHP <input type="text" data-field="berita_acara.nomor_sp_bahp" /></label>
      <label>Tanggal SP BAHP <input type="date" data-field="berita_acara.tanggal_sp_bahp" /></label>
      <label>Nomor Kontrak BAHP <input type="text" data-field="berita_acara.nomor_kontrak_bahp" /></label>
    </div>
  </div>

  <div class="form-subsection">
    <p class="eyebrow">Data BAHPA (Administratif)</p>
    <div class="section-grid">
      <label>Nomor SK BAHPA <input type="text" data-field="berita_acara.nomor_sk_bahpa" /></label>
      <label>Tanggal SK BAHPA <input type="date" data-field="berita_acara.tanggal_sk_bahpa" /></label>
      <label>Nomor SP BAHPA <input type="text" data-field="berita_acara.nomor_sp_bahpa" /></label>
      <label>Tanggal SP BAHPA <input type="date" data-field="berita_acara.tanggal_sp_bahpa" /></label>
    </div>
  </div>

  <div class="section-grid">
    <label>Paket Pekerjaan <textarea rows="2" data-field="berita_acara.paket_pekerjaan" required></textarea></label>
    <label>Paket Pekerjaan Administratif <textarea rows="2" data-field="berita_acara.paket_pekerjaan_administratif" required></textarea></label>
    <label>Tanggal Serah Terima <input type="date" data-field="berita_acara.tanggal_serah_terima" required /></label>
    <label>Keterangan <textarea rows="2" data-field="berita_acara.keterangan"></textarea></label>
  </div>
</article>
