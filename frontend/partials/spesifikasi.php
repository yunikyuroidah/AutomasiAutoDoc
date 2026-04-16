<article class="card form-section" data-section="spesifikasi">
  <header>
    <div>
      <p class="eyebrow">Lampiran</p>
      <h2>Spesifikasi Anggaran</h2>
    </div>
    <div class="spec-actions">
      <button class="ghost" type="button" id="addSpecRow">+ Baris</button>
      <button class="ghost" type="button" id="finalizeSpec">Selesai</button>
    </div>
  </header>
  <div class="table-wrapper table-wrapper--scroll">
    <table class="spec-table spec-table--wide">
      <thead>
        <tr>
          <th>No</th>
          <th>Spesifikasi</th>
          <th>Volume</th>
          <th>Satuan</th>
          <th>Harga satuan</th>
          <th>Pagu anggaran</th>
          <th></th>
        </tr>
      </thead>
      <tbody id="specRows"></tbody>
    </table>
  </div>
  <div class="spec-summary">
    <div class="metric">
      <p>Jumlah</p>
      <strong id="sumJumlah">Rp 0</strong>
    </div>
    <div class="metric">
      <p>Pajak daerah (10%)</p>
      <strong id="sumPajak">Rp 0</strong>
    </div>
    <div class="metric">
      <p>Total</p>
      <strong id="sumTotal">Rp 0</strong>
    </div>
  </div>
</article>
