<article class="card form-section" data-section="lembar_kegiatan">
  <header>
    <div>
      <p class="eyebrow">Rangkuman</p>
      <h2>Lembar Kegiatan</h2>
    </div>
  </header>
  <div class="section-grid">
    <label>Bulan kegiatan
      <input type="text" data-field="lembar_kegiatan.bulan" placeholder="Misal: November 2025" />
    </label>
    <label>Daftar kegiatan
      <textarea rows="2" data-field="lembar_kegiatan.daftar" required></textarea>
    </label>
    <label>Program
      <textarea rows="2" data-field="lembar_kegiatan.program" required></textarea>
    </label>
    <label>Kegiatan
      <textarea rows="2" data-field="lembar_kegiatan.kegiatan" required></textarea>
    </label>
    <label>Sub kegiatan
      <textarea rows="2" data-field="lembar_kegiatan.sub_kegiatan"></textarea>
    </label>
    <label>Kode rekening
      <input type="text" data-field="lembar_kegiatan.kode_rekening" required />
    </label>
    <label>Sumber dana
      <input type="text" data-field="lembar_kegiatan.sumber_dana" required />
    </label>
    <label>Kepala Dinas
      <select data-field="lembar_kegiatan.kepdin_id" id="selectLembarKepdin" required></select>
    </label>
    <label>Bendahara
      <select data-field="lembar_kegiatan.bendahara_id" id="selectLembarBendahara" required></select>
    </label>
    <label>PPTK
      <select data-field="lembar_kegiatan.pptk_id" id="selectLembarPptk" required></select>
    </label>
  </div>
</article>
