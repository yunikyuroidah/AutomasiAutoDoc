<?php
declare(strict_types=1);

require __DIR__ . '/../backend/includes/auth.php';

require_auth();

$rawUsername = current_username();
$username = htmlspecialchars($rawUsername, ENT_QUOTES, 'UTF-8');
$initial = htmlspecialchars(strtoupper(substr($rawUsername, 0, 1)), ENT_QUOTES, 'UTF-8');
$panelCssVersion = filemtime(__DIR__ . '/panel.css') ?: time();
$panelJsVersion = filemtime(__DIR__ . '/panel.js') ?: time();
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Workspace Demo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&family=Space+Grotesk:wght@500&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="./panel.css?v=<?= $panelCssVersion; ?>" />
  </head>
  <body>
    <div class="app-shell">
      <main class="workspace">
        <header class="workspace__head">
          <div>
            <p class="eyebrow">Workspace Demo</p>
            <h1>Pengisian Dokumen</h1>
          </div>
          <div class="head-actions">
            <div class="status-pills">
              <span class="pill" id="lembarCount">0 item</span>
              <span class="pill" id="masterHealth">Master belum sinkron</span>
            </div>
            <div class="user-chip">
              <div class="avatar"><?= $initial; ?></div>
              <div>
                <p class="user__label">Operator</p>
                <p class="user__name"><?= $username; ?></p>
              </div>
            </div>
            <div class="head-links">
              <a class="linkish" href="./login.html" data-action="logout">Keluar</a>
            </div>
          </div>
        </header>
        <section class="workspace__body">
          <div class="tabs" role="tablist">
            <button class="tab is-active" data-pane="lembar">Form Utama</button>
            <button class="tab" data-pane="master">Master Data</button>
          </div>
          <section id="pane-lembar" class="pane is-active" role="tabpanel">
            <div class="workspace-form__head">
              <div>
                <p class="eyebrow">Form utama</p>
              </div>
              <button class="ghost" type="button" id="refreshForm">Segarkan</button>
            </div>
            <form id="workspaceForm" class="stacked-form" autocomplete="off">
              <article class="card form-section" data-section="general">
                <header>
                  <div>
                    <p class="eyebrow">Ringkasan</p>
                    <h2>General</h2>
                  </div>
                </header>
                <div class="section-grid">
                  <label>Objek
                    <input type="text" data-field="general.objek" placeholder="Misal: Objek Kegiatan Dummy" required />
                  </label>
                  <label>Tanggal kegiatan
                    <input type="date" data-field="general.tanggal" required />
                  </label>
                </div>
              </article>

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
              </article>

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

              <?php include __DIR__ . '/partials/berita_acara.php'; ?>

              <article class="card form-section" data-section="kwitansi">
                <header>
                  <div>
                    <p class="eyebrow">Dokumen 2</p>
                    <h2>Kwitansi</h2>
                  </div>
                </header>
                <div class="section-grid">
                  <label>Nama penerima
                    <input type="text" data-field="kwitansi.nama_penerima" required />
                  </label>
                  <label>Nama bank
                    <input type="text" data-field="kwitansi.nama_bank" required />
                  </label>
                  <label>NPWP
                    <input type="text" data-field="kwitansi.npwp" required />
                  </label>
                  <label>Nomor rekening
                    <input type="text" data-field="kwitansi.norek" required />
                  </label>
                  <label>Kepala Dinas
                    <select data-field="kwitansi.kepdin_id" id="selectKwitansiKepdin" required></select>
                  </label>
                  <label>Bendahara
                    <select data-field="kwitansi.bendahara_id" id="selectKwitansiBendahara" required></select>
                  </label>
                  <label>PPTK
                    <select data-field="kwitansi.pptk_id" id="selectKwitansiPptk" required></select>
                  </label>
                  <label>Jumlah uang
                    <input type="number" min="0" step="100" data-field="kwitansi.jumlah_uang" required />
                  </label>
                  <label>Tanggal pembayaran
                    <input type="date" data-field="kwitansi.tanggal_pembayaran" required />
                  </label>
                </div>
              </article>

              <article class="card form-section" data-section="nota_dinas">
                <header>
                  <div>
                    <p class="eyebrow">Dokumen 3</p>
                    <h2>Nota Dinas</h2>
                  </div>
                </header>
                <div class="section-grid">
                  <label>Nomor nota
                    <input type="text" data-field="nota_dinas.nomor" required />
                  </label>
                  <label>Perihal
                    <input type="text" data-field="nota_dinas.perihal" required />
                  </label>
                  <label>Keperluan
                    <textarea rows="3" data-field="nota_dinas.keperluan" required></textarea>
                  </label>
                  <label>Jumlah DPA
                    <input type="number" min="0" step="100" data-field="nota_dinas.jumlah_dpa" />
                  </label>
                  <label>Tahun anggaran
                    <input type="number" min="2000" max="2100" data-field="nota_dinas.tahun_anggaran" readonly />
                  </label>
                  <label>Kepala Bidang
                    <select data-field="nota_dinas.kabid_id" id="selectNotaKabid" required></select>
                  </label>
                </div>
              </article>

              <article class="card form-section" data-section="sptpd">
                <header>
                  <div>
                    <p class="eyebrow">Dokumen 4</p>
                    <h2>SPTPD</h2>
                  </div>
                </header>
                <div class="section-grid">
                  <label>Tahun pajak
                    <input type="number" min="2000" max="2100" data-field="sptpd.tahun" required />
                  </label>
                  <label>Harga jual
                    <input type="number" min="0" step="100" data-field="sptpd.harga_jual" required />
                  </label>
                  <label>Dasar pengenaan pajak
                    <input type="number" data-field="sptpd.dasar_pengenaan_pajak" readonly />
                  </label>
                  <label>Pajak restoran terhutang
                    <input type="number" data-field="sptpd.pajak_terhutang" readonly />
                  </label>
                  <label>Nama badan usaha
                    <input type="text" data-field="sptpd.nama_badan_usaha" required />
                  </label>
                  <label>Masa pajak
                    <input type="text" data-field="sptpd.masa_pajak" required />
                  </label>
                  <label>Pekerjaan
                    <textarea rows="3" data-field="sptpd.pekerjaan" required></textarea>
                  </label>
                  <label>Telepon Kantor
                    <input type="text" data-field="sptpd.telp_kantor" placeholder="Contoh: (031) 395xxxx" />
                  </label>
                </div>
              </article>

              <div class="cta-flex-row">
                <button type="submit" class="primary" id="generateAll">Generate</button>
                <span class="cta-note">Tombol ini menyimpan perubahan lalu menyiapkan semua dokumen .docx/.xlsx</span>
                <div class="output-grid" id="generateOutput" hidden>
                  <article class="output-card" data-file="dokumen_pengadaan">
                    <div>
                      <p>dokumen_pengadaan.docx</p>
                      <small>Status: belum digenerate</small>
                    </div>
                    <button type="button" class="ghost" disabled>Unduh</button>
                  </article>
                  <article class="output-card" data-file="berita_acara">
                    <div>
                      <p>berita_acara.docx</p>
                      <small>Status: belum digenerate</small>
                    </div>
                    <button type="button" class="ghost" disabled>Unduh</button>
                  </article>
                  <article class="output-card" data-file="kwitansi">
                    <div>
                      <p>kwitansi.xlsx</p>
                      <small>Status: belum digenerate</small>
                    </div>
                    <button type="button" class="ghost" disabled>Unduh</button>
                  </article>
                  <article class="output-card" data-file="lembar_kegiatan">
                    <div>
                      <p>lembar_kegiatan.docx</p>
                      <small>Status: belum digenerate</small>
                    </div>
                    <button type="button" class="ghost" disabled>Unduh</button>
                  </article>
                  <article class="output-card" data-file="nota_dinas">
                    <div>
                      <p>nota_dinas.xlsx</p>
                      <small>Status: belum digenerate</small>
                    </div>
                    <button type="button" class="ghost" disabled>Unduh</button>
                  </article>
                  <article class="output-card" data-file="sptpd">
                    <div>
                      <p>sptpd.xlsx</p>
                      <small>Status: belum digenerate</small>
                    </div>
                    <button type="button" class="ghost" disabled>Unduh</button>
                  </article>
                </div>
              </div>
            </form>
          </section>

          <section id="pane-master" class="pane" role="tabpanel" aria-hidden="true">
            <div class="workspace-form__head">
              <div>
                <p class="eyebrow">Master</p>
                <h2>Referensi Penandatangan</h2>
              </div>
              <button class="ghost" type="button" id="refreshMaster">Segarkan master</button>
            </div>
            <p class="master-note">Semua data di bawah mengisi dropdown pada form utama. Seluruh kolom dapat diperbarui kapan saja, namun hanya PPK yang boleh ditambah atau dihapus.</p>

            <div class="master-grid">
              <article class="card master-card" data-master-card="kepdin">
                <header class="card__head master-card__head">
                  <div>
                    <p class="eyebrow">Penanggung jawab</p>
                    <h3>Kepala Dinas</h3>
                  </div>
                  <p class="master-card__desc">Dipakai pada berkas berita acara, kwitansi, dan lembar kegiatan.</p>
                </header>
                <div class="master-list" data-master-list="kepdin"></div>
              </article>

              <article class="card master-card" data-master-card="bendahara">
                <header class="card__head master-card__head">
                  <div>
                    <p class="eyebrow">Keuangan</p>
                    <h3>Bendahara Pengeluaran</h3>
                  </div>
                  <p class="master-card__desc">Digunakan di kwitansi serta laporan pembiayaan.</p>
                </header>
                <div class="master-list" data-master-list="bendahara"></div>
              </article>

              <article class="card master-card" data-master-card="pptk">
                <header class="card__head master-card__head">
                  <div>
                    <p class="eyebrow">Pelaksana</p>
                    <h3>PPTK</h3>
                  </div>
                  <p class="master-card__desc">Wajib untuk kwitansi dan dokumen pembayaran lainnya.</p>
                </header>
                <div class="master-list" data-master-list="pptk"></div>
              </article>

              <article class="card master-card" data-master-card="kabid_ppa">
                <header class="card__head master-card__head">
                  <div>
                    <p class="eyebrow">Bidang</p>
                    <h3>Kabid PPA</h3>
                  </div>
                  <p class="master-card__desc">Mengisi kolom penandatangan Nota Dinas.</p>
                </header>
                <div class="master-list" data-master-list="kabid_ppa"></div>
              </article>

              <article class="card master-card" data-master-card="pembuat_komitmen">
                <header class="card__head master-card__head">
                  <div>
                    <p class="eyebrow">PPK</p>
                    <h3>Pejabat Pembuat Komitmen</h3>
                  </div>
                  <div class="master-card__actions">
                    <p class="master-card__desc">Digunakan di dokumen pengadaan dan berita acara.</p>
                    <button class="master-add-button" type="button" data-master-add="pembuat_komitmen">+ Tambah PPK</button>
                  </div>
                </header>
                <div class="master-list" data-master-list="pembuat_komitmen"></div>
              </article>

              <article class="card master-card" data-master-card="penyedia">
                <header class="card__head master-card__head">
                  <div>
                    <p class="eyebrow">Vendor</p>
                    <h3>Penyedia</h3>
                  </div>
                  <p class="master-card__desc">Dipakai untuk berita acara dan catatan pengadaan.</p>
                </header>
                <div class="master-list" data-master-list="penyedia"></div>
              </article>
            </div>
          </section>
        </section>
      </main>
    </div>

    <div id="toast" class="toast" role="status" aria-live="polite"></div>

    <script>
      window.__PANEL_CONTEXT__ = { username: <?= json_encode($rawUsername, JSON_UNESCAPED_UNICODE); ?> };
    </script>
    <script type="module" src="./panel.js?v=<?= $panelJsVersion; ?>"></script>
  </body>
</html>
