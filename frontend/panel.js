import { escapeHtml, formatRupiah, deriveYear, request } from './panel.utils.js';

const state = {
  data: null,
  masters: {},
  totals: { jumlah: 0, pajak: 0, total: 0 },
  generation: [],
  isGenerating: false,
  isSpecFinalized: false,
};

const elements = {
  form: document.getElementById('workspaceForm'),
  refresh: document.getElementById('refreshForm'),
  addSpecRow: document.getElementById('addSpecRow'),
  finalizeSpec: document.getElementById('finalizeSpec'),
  specRows: document.getElementById('specRows'),
  sumJumlah: document.getElementById('sumJumlah'),
  sumPajak: document.getElementById('sumPajak'),
  sumTotal: document.getElementById('sumTotal'),
  generateOutput: document.getElementById('generateOutput'),
  generateButton: document.getElementById('generateAll'),
  lembarCount: document.getElementById('lembarCount'),
  masterHealth: document.getElementById('masterHealth'),
  masterPane: document.getElementById('pane-master'),
  refreshMaster: document.getElementById('refreshMaster'),
  toast: document.getElementById('toast'),
};

const API_BASE = '../backend/api';

const API = {
  form: `${API_BASE}/form.php`,
  generate: `${API_BASE}/generate_documents.php`,
  master: `${API_BASE}/master.php`,
};

function resolveDownloadUrl(pathname) {
  if (!pathname) return null;
  try {
    const apiEndpoint = new URL(API.generate, window.location.href);
    return new URL(pathname, apiEndpoint).href;
  } catch (error) {
    console.error('Gagal mengonversi URL unduhan', error);
    return null;
  }
}

const OUTPUT_STATUS_LABEL = {
  pending: 'Menunggu exporter tersedia',
  processing: 'Sedang memproses',
  success: 'Siap diunduh',
  failed: 'Gagal, cek log',
};

const SPEC_SUMMARY_PENDING_TEXT = 'Klik selesai';

// --- PERBAIKAN 1: FIELD LINKING AGAR OTOMATIS TERISI ---
const LINKED_FIELD_GROUPS = [
  // Teks Kegiatan & Pekerjaan
  [
    'lembar_kegiatan.kegiatan',
    'berita_acara.paket_pekerjaan',
    'berita_acara.paket_pekerjaan_administratif',
    'nota_dinas.keperluan',
    'sptpd.pekerjaan',
  ],
  // Sinkronisasi PPK (Dokumen Pengadaan -> Berita Acara)
  [
    'dokumen_pengadaan.pembuat_komitmen_id',
    'berita_acara.pembuat_komitmen_id'
  ],
  // Sinkronisasi Kepdin (Lembar Kegiatan -> Berita Acara -> Kwitansi)
  [
    'lembar_kegiatan.kepdin_id',
    'berita_acara.kepdin_id',
    'kwitansi.kepdin_id'
  ],
  // Sinkronisasi Bendahara (Lembar Kegiatan -> Kwitansi)
  [
    'lembar_kegiatan.bendahara_id',
    'kwitansi.bendahara_id'
  ],
  // Sinkronisasi PPTK (Lembar Kegiatan -> Kwitansi)
  [
    'lembar_kegiatan.pptk_id',
    'kwitansi.pptk_id'
  ]
];

const ONE_WAY_FIELD_LINKS = {
  'dokumen_pengadaan.nama_paket': ['lembar_kegiatan.daftar'],
};

const MASTER_SELECTS = {
  'dokumen_pengadaan.pembuat_komitmen_id': { elementId: 'selectDokumenPpk', source: 'pembuat_komitmen' },
  'berita_acara.kepdin_id': { elementId: 'selectBeritaKepdin', source: 'kepdin' },
  'berita_acara.pembuat_komitmen_id': { elementId: 'selectBeritaPpk', source: 'pembuat_komitmen' },
  'berita_acara.penyedia_id': { elementId: 'selectBeritaPenyedia', source: 'penyedia' },
  'kwitansi.kepdin_id': { elementId: 'selectKwitansiKepdin', source: 'kepdin' },
  'kwitansi.bendahara_id': { elementId: 'selectKwitansiBendahara', source: 'bendahara' },
  'kwitansi.pptk_id': { elementId: 'selectKwitansiPptk', source: 'pptk' },
  'nota_dinas.kabid_id': { elementId: 'selectNotaKabid', source: 'kabid_ppa' },
  'lembar_kegiatan.kepdin_id': { elementId: 'selectLembarKepdin', source: 'kepdin' },
  'lembar_kegiatan.bendahara_id': { elementId: 'selectLembarBendahara', source: 'bendahara' },
  'lembar_kegiatan.pptk_id': { elementId: 'selectLembarPptk', source: 'pptk' },
};

const MASTER_RESOURCES = {
  kepdin: {
    label: 'Kepala Dinas',
    allowCreate: false,
    allowDelete: false,
    fields: [
      { key: 'nama', label: 'Nama lengkap', required: true },
      { key: 'nip', label: 'NIP', required: true },
      { key: 'keterangan', label: 'Catatan', required: false },
    ],
  },
  bendahara: {
    label: 'Bendahara',
    allowCreate: false,
    allowDelete: false,
    fields: [
      { key: 'nama', label: 'Nama lengkap', required: true },
      { key: 'nip', label: 'NIP', required: true },
    ],
  },
  pptk: {
    label: 'PPTK',
    allowCreate: false,
    allowDelete: false,
    fields: [
      { key: 'nama', label: 'Nama lengkap', required: true },
      { key: 'nip', label: 'NIP', required: true },
    ],
  },
  kabid_ppa: {
    label: 'Kabid PPA',
    allowCreate: false,
    allowDelete: false,
    fields: [
      { key: 'nama', label: 'Nama lengkap', required: true },
      { key: 'nip', label: 'NIP', required: true },
    ],
  },
  pembuat_komitmen: {
    label: 'PPK',
    allowCreate: true,
    allowDelete: true,
    fields: [
      { key: 'nama', label: 'Nama lengkap', required: true },
      { key: 'nip', label: 'NIP', required: true },
      { key: 'keterangan', label: 'Catatan', required: false },
    ],
  },
  penyedia: {
    label: 'Penyedia',
    allowCreate: false,
    allowDelete: false,
    fields: [
      { key: 'nama', label: 'Nama badan usaha', required: true },
      { key: 'nama_orang', label: 'Nama Penanggung Jawab (Orang)', type: 'text' },
      { key: 'keterangan', label: 'Catatan', required: false, type: 'textarea' },
      { key: 'alamat_penyedia', label: 'Alamat', required: false, type: 'textarea' },
    ],
  },
};

const MASTER_KEYS = Object.keys(MASTER_RESOURCES);

const FIELD_TYPE = {
  'general.id': 'int',
  'dokumen_pengadaan.pagu_anggaran': 'float',
  'dokumen_pengadaan.pembuat_komitmen_id': 'int',
  'berita_acara.kepdin_id': 'int',
  'berita_acara.pembuat_komitmen_id': 'int',
  'berita_acara.penyedia_id': 'int',
  'kwitansi.kepdin_id': 'int',
  'kwitansi.bendahara_id': 'int',
  'kwitansi.pptk_id': 'int',
  'kwitansi.jumlah_uang': 'float',
  'nota_dinas.jumlah_dpa': 'float',
  'nota_dinas.tahun_anggaran': 'int',
  'nota_dinas.kabid_id': 'int',
  'sptpd.tahun': 'int',
  'sptpd.harga_jual': 'float',
  'sptpd.dasar_pengenaan_pajak': 'float',
  'sptpd.pajak_terhutang': 'float',
  'lembar_kegiatan.kepdin_id': 'int',
  'lembar_kegiatan.bendahara_id': 'int',
  'lembar_kegiatan.pptk_id': 'int',
};

// --- PERBAIKAN 2: MENGEMBALIKAN FITUR SELECT ALL ---
const AUTO_SELECT_EXCLUDED_TYPES = new Set(['checkbox', 'radio', 'file', 'button', 'submit', 'reset', 'range']);
const AUTO_SELECT_SWITCH_TYPES = new Set(['number', 'date', 'datetime-local', 'month', 'week', 'time', 'color']);

document.addEventListener('DOMContentLoaded', () => {
  bindTabs();
  bindFormEvents();
  bindMasterEvents();
  enableAutoSelectOnFocus(); // Diaktifkan kembali
  loadForm();
});

async function loadForm() {
  try {
    const response = await request(API.form);
    
    // SAFETY CHECK: Cek apakah response memiliki key 'data'
    // Ini yang memperbaiki error "Cannot read properties of undefined"
    if (!response || !response.data) {
      console.warn("Backend mengembalikan data kosong/format salah", response);
      // Buat struktur dummy agar tidak crash
      state.data = {};
      state.masters = {};
      toast('Gagal memuat data dari server (Format Invalid)');
      return; 
    }

    const { data } = response;
    state.data = data;
    // Gunakan fallback {} jika data.masters tidak ada
    state.masters = data.masters || {}; 

    // Inisialisasi Master Data (Cek array)
    MASTER_KEYS.forEach((key) => {
      if (!Array.isArray(state.masters[key])) {
        state.masters[key] = [];
      }
    });

    hydrateMasterSelects();
    renderAllMasterLists();
    updateMasterHealth();
    hydrateForm();
  } catch (error) {
    console.error("Load Form Error:", error);
    toast(error.message || 'Gagal memuat form');
  }
}

function bindFormEvents() {
  if (elements.refresh) elements.refresh.addEventListener('click', () => loadForm());
  if (elements.addSpecRow) elements.addSpecRow.addEventListener('click', () => addSpecRow());
  if (elements.finalizeSpec) elements.finalizeSpec.addEventListener('click', finalizeSpecRows);
  if (elements.generateOutput) elements.generateOutput.addEventListener('click', handleDownloadClick);
  if (elements.form) {
    elements.form.addEventListener('input', handleFieldInput);
    elements.form.addEventListener('submit', handleSubmit);
  }
}

function bindMasterEvents() {
  if (elements.refreshMaster) elements.refreshMaster.addEventListener('click', () => refreshAllMasters());
  if (elements.masterPane) elements.masterPane.addEventListener('click', handleMasterClick);
}

// --- LOGIKA SELECT ALL START ---
function enableAutoSelectOnFocus() {
  document.addEventListener(
    'focusin',
    (event) => {
      const target = event.target;
      if (target instanceof HTMLTextAreaElement) {
        if (target.readOnly || target.disabled) return;
        queueFieldSelection(target);
        return;
      }
      if (target instanceof HTMLInputElement) {
        if (target.readOnly || target.disabled) return;
        const type = (target.type || 'text').toLowerCase();
        if (AUTO_SELECT_EXCLUDED_TYPES.has(type)) return;
        queueFieldSelection(target, type);
      }
    },
    true
  );
}

function queueFieldSelection(element, initialType = '') {
  requestAnimationFrame(() => {
    trySelectElement(element);
  });
}

function trySelectElement(element) {
  if (typeof element.select === 'function') {
    try {
      element.select();
      return true;
    } catch (error) { }
  }
  return false;
}
// --- LOGIKA SELECT ALL END ---

function applyOneWayLinks(sourceField, value, options = {}) {
  const targets = ONE_WAY_FIELD_LINKS[sourceField];
  if (!targets || targets.length === 0) return;

  const normalized = value ?? '';
  const onlyIfEmpty = options.onlyIfEmpty === true;

  targets.forEach((field) => {
    const input = getFieldElement(field);
    if (!input) return;
    if (document.activeElement === input) return;

    const current = input.value || '';
    const shouldOverride = !onlyIfEmpty || current.trim() === '' || input.dataset.autofillSource === sourceField;
    if (!shouldOverride) return;

    input.value = normalized;
    if (normalized === '') {
      delete input.dataset.autofillSource;
    } else {
      input.dataset.autofillSource = sourceField;
    }
  });
}

function handleSpecFieldInput(target) {
  const row = target.closest('tr');
  if (!row) return;
  const field = target.dataset.specField || '';
  if (field === 'spesifikasi_jumlah' || field === 'harga_satuan') {
    syncSpecRowPagu(row);
  }
  markSpecDirty();
  updateSpecTotals();
}

function handleFieldInput(event) {
  const field = event.target.dataset.field;
  if (field) {
    if (event.target.dataset.autofillSource) delete event.target.dataset.autofillSource;
    
    // Trigger linking setiap kali input berubah
    mirrorLinkedFields(field, event.target.value);
    applyOneWayLinks(field, event.target.value);

    if (field === 'general.tanggal') syncBudgetYear(event.target.value);
    if (field === 'sptpd.harga_jual') updateSptpdAuto();
  }
  if (event.target.dataset.specField) handleSpecFieldInput(event.target);
}

function mirrorLinkedFields(sourceField, value) {
  // Cari semua grup yang mengandung field ini
  const groups = LINKED_FIELD_GROUPS.filter((fields) => fields.includes(sourceField));
  if (!groups.length) return;

  const normalized = value ?? '';

  groups.forEach((group) => {
    group.forEach((field) => {
      if (field === sourceField) return;
      const input = getFieldElement(field);
      // Update field target jika field tersebut ada dan tidak sedang diketik user
      if (input && document.activeElement !== input) {
        input.value = normalized;
        // Jika input adalah select, trigger change event agar UI update (opsional)
        if(input.tagName === 'SELECT') {
             input.dispatchEvent(new Event('change'));
        }
      }
    });
  });
}

function syncBudgetYear(dateValue) {
  if (!dateValue) return;
  const year = new Date(dateValue).getFullYear();
  const yearInput = getFieldElement('nota_dinas.tahun_anggaran');
  const sptpdYearInput = getFieldElement('sptpd.tahun');
  if (yearInput) yearInput.value = year;
  if (sptpdYearInput && !sptpdYearInput.value) sptpdYearInput.value = year;
}

function updateSptpdAuto() {
  const harga = getNumericValue('sptpd.harga_jual');
  const dasar = harga > 0 ? harga / 1.1 : 0;
  const pajak = dasar * 0.1;
  setFieldValue('sptpd.dasar_pengenaan_pajak', dasar.toFixed(2));
  setFieldValue('sptpd.pajak_terhutang', pajak.toFixed(2));
}

function hydrateMasterSelects() {
  Object.values(MASTER_SELECTS).forEach((config) => {
    const select = document.getElementById(config.elementId);
    if (!select) return;
    const collection = state.masters[config.source] || [];
    select.innerHTML = collection
      .map((item) => `<option value="${item.id}">${escapeHtml(item.nama || item.name || '')}</option>`)
      .join('');
    // Tambahkan opsi kosong di awal agar user sadar harus memilih
    select.insertAdjacentHTML('afterbegin', '<option value="">-- Pilih --</option>');
  });
}

function hydrateForm() {
  if (!state.data) return;

  setFieldValue('general.objek', state.data.general?.objek || '');
  setFieldValue('general.tanggal', state.data.general?.tanggal || '');

  setFieldValue('dokumen_pengadaan.nama_paket', state.data.dokumen_pengadaan?.nama_paket || '');
  setFieldValue('dokumen_pengadaan.kode_rup', state.data.dokumen_pengadaan?.kode_rup || '');
  setFieldValue('dokumen_pengadaan.nomor_dpp', state.data.dokumen_pengadaan?.nomor_dpp || '');
  setFieldValue('dokumen_pengadaan.pagu_anggaran', state.data.dokumen_pengadaan?.pagu_anggaran || '');
  setFieldValue('dokumen_pengadaan.tanggal_mulai', state.data.dokumen_pengadaan?.tanggal_mulai || '');
  setFieldValue('dokumen_pengadaan.tanggal_selesai', state.data.dokumen_pengadaan?.tanggal_selesai || '');
  setFieldValue('dokumen_pengadaan.pembuat_komitmen_id', state.data.dokumen_pengadaan?.pembuat_komitmen_id || '');

  setFieldValue('lembar_kegiatan.bulan', state.data.lembar_kegiatan?.bulan || '');
  setFieldValue('lembar_kegiatan.daftar', state.data.lembar_kegiatan?.daftar || '');
  setFieldValue('lembar_kegiatan.program', state.data.lembar_kegiatan?.program || '');
  setFieldValue('lembar_kegiatan.kegiatan', state.data.lembar_kegiatan?.kegiatan || '');
  setFieldValue('lembar_kegiatan.sub_kegiatan', state.data.lembar_kegiatan?.sub_kegiatan || '');
  setFieldValue('lembar_kegiatan.kode_rekening', state.data.lembar_kegiatan?.kode_rekening || '');
  setFieldValue('lembar_kegiatan.sumber_dana', state.data.lembar_kegiatan?.sumber_dana || '');
  setFieldValue('lembar_kegiatan.kepdin_id', state.data.lembar_kegiatan?.kepdin_id || '');
  setFieldValue('lembar_kegiatan.bendahara_id', state.data.lembar_kegiatan?.bendahara_id || '');
  setFieldValue('lembar_kegiatan.pptk_id', state.data.lembar_kegiatan?.pptk_id || '');

  // Berita Acara
  setFieldValue('berita_acara.nomor_satuan_kerja', state.data.berita_acara?.nomor_satuan_kerja || '');
  setFieldValue('berita_acara.tanggal_satuan_kerja', state.data.berita_acara?.tanggal_satuan_kerja || '');
  setFieldValue('berita_acara.nomor_sp', state.data.berita_acara?.nomor_sp || '');
  setFieldValue('berita_acara.tanggal_sp', state.data.berita_acara?.tanggal_sp || '');
  setFieldValue('berita_acara.kepdin_id', state.data.berita_acara?.kepdin_id || '');
  setFieldValue('berita_acara.pembuat_komitmen_id', state.data.berita_acara?.pembuat_komitmen_id || '');
  setFieldValue('berita_acara.penyedia_id', state.data.berita_acara?.penyedia_id || '');
  setFieldValue('berita_acara.nomor_sk_bahp', state.data.berita_acara?.nomor_sk_bahp || '');
  setFieldValue('berita_acara.tanggal_sk_bahp', state.data.berita_acara?.tanggal_sk_bahp || '');
  setFieldValue('berita_acara.nomor_sp_bahp', state.data.berita_acara?.nomor_sp_bahp || '');
  setFieldValue('berita_acara.nomor_kontrak_bahp', state.data.berita_acara?.nomor_kontrak_bahp || '');
  setFieldValue('berita_acara.tanggal_sp_bahp', state.data.berita_acara?.tanggal_sp_bahp || '');
  setFieldValue('berita_acara.nomor_sk_bahpa', state.data.berita_acara?.nomor_sk_bahpa || '');
  setFieldValue('berita_acara.tanggal_sk_bahpa', state.data.berita_acara?.tanggal_sk_bahpa || '');
  setFieldValue('berita_acara.nomor_sp_bahpa', state.data.berita_acara?.nomor_sp_bahpa || '');
  setFieldValue('berita_acara.tanggal_sp_bahpa', state.data.berita_acara?.tanggal_sp_bahpa || '');
  setFieldValue('berita_acara.paket_pekerjaan', state.data.berita_acara?.paket_pekerjaan || '');
  setFieldValue('berita_acara.paket_pekerjaan_administratif', state.data.berita_acara?.paket_pekerjaan_administratif || '');
  setFieldValue('berita_acara.tanggal_serah_terima', state.data.berita_acara?.tanggal_serah_terima || '');
  setFieldValue('berita_acara.keterangan', state.data.berita_acara?.keterangan || '');

  setFieldValue('kwitansi.nama_penerima', state.data.kwitansi?.nama_penerima || '');
  setFieldValue('kwitansi.nama_bank', state.data.kwitansi?.nama_bank || '');
  setFieldValue('kwitansi.npwp', state.data.kwitansi?.npwp || '');
  setFieldValue('kwitansi.norek', state.data.kwitansi?.norek || '');
  setFieldValue('kwitansi.kepdin_id', state.data.kwitansi?.kepdin_id || '');
  setFieldValue('kwitansi.bendahara_id', state.data.kwitansi?.bendahara_id || '');
  setFieldValue('kwitansi.pptk_id', state.data.kwitansi?.pptk_id || '');
  setFieldValue('kwitansi.jumlah_uang', state.data.kwitansi?.jumlah_uang || '');
  setFieldValue('kwitansi.tanggal_pembayaran', state.data.kwitansi?.tanggal_pembayaran || '');

  setFieldValue('nota_dinas.nomor', state.data.nota_dinas?.nomor || '');
  setFieldValue('nota_dinas.perihal', state.data.nota_dinas?.perihal || '');
  setFieldValue('nota_dinas.keperluan', state.data.nota_dinas?.keperluan || '');
  setFieldValue('nota_dinas.jumlah_dpa', state.data.nota_dinas?.jumlah_dpa || '');
  setFieldValue('nota_dinas.tahun_anggaran', state.data.nota_dinas?.tahun_anggaran || deriveYear(state.data.general?.tanggal));
  setFieldValue('nota_dinas.kabid_id', state.data.nota_dinas?.kabid_id || '');

  setFieldValue('sptpd.tahun', state.data.sptpd?.tahun || deriveYear(state.data.general?.tanggal));
  setFieldValue('sptpd.harga_jual', state.data.sptpd?.harga_jual || '');
  setFieldValue('sptpd.dasar_pengenaan_pajak', state.data.sptpd?.dasar_pengenaan_pajak || '');
  setFieldValue('sptpd.pajak_terhutang', state.data.sptpd?.pajak_terhutang || '');
  setFieldValue('sptpd.nama_badan_usaha', state.data.sptpd?.nama_badan_usaha || '');
  setFieldValue('sptpd.masa_pajak', state.data.sptpd?.masa_pajak || '');
  setFieldValue('sptpd.pekerjaan', state.data.sptpd?.pekerjaan || '');
  setFieldValue('sptpd.telp_kantor', state.data.sptpd?.telp_kantor || '');

  renderSpecRows(state.data.spesifikasi_anggaran || []);
  hydrateLinkedNarrative();
  applyOneWayLinks('dokumen_pengadaan.nama_paket', state.data.dokumen_pengadaan?.nama_paket || '', { onlyIfEmpty: true });
  state.isSpecFinalized = true;
  updateSpecTotals({ force: true });
  updateSptpdAuto();
}

function renderSpecRows(items) {
  if (!elements.specRows) return;
  const rows = items.length ? items : [createEmptySpecItem()];
  elements.specRows.innerHTML = rows.map((item, index) => specRowTemplate(item, index)).join('');
  const rowElements = Array.from(elements.specRows.querySelectorAll('tr'));
  rowElements.forEach((row) => syncSpecRowPagu(row));
  elements.specRows.querySelectorAll('button[data-remove-row]').forEach((button) => {
    button.addEventListener('click', () => removeSpecRow(Number(button.dataset.removeRow)));
  });
}

function hydrateLinkedNarrative() {
  LINKED_FIELD_GROUPS.forEach((group) => {
    const source = group
      .map((field) => ({ field, input: getFieldElement(field) }))
      .find(({ input }) => input && input.value && input.value.trim() !== '');
    if (source && source.input) {
      mirrorLinkedFields(source.field, source.input.value);
    }
  });
}

function specRowTemplate(item, index) {
  return `
    <tr data-spec-index="${index}">
      <td>${index + 1}</td>
      <td><input type="text" value="${escapeHtml(item.spesifikasi || '')}" data-spec-field="spesifikasi" required /></td>
      <td><input type="number" min="0" step="1" value="${escapeHtml(item.spesifikasi_jumlah || '')}" data-spec-field="spesifikasi_jumlah" required /></td>
      <td><input type="text" value="${escapeHtml(item.satuan_ukuran || '')}" data-spec-field="satuan_ukuran" required /></td>
      <td><input type="number" min="0" step="100" value="${escapeHtml(item.harga_satuan || '')}" data-spec-field="harga_satuan" required /></td>
      <td><input type="number" min="0" step="100" value="${escapeHtml(item.pagu_anggaran || '')}" data-spec-field="pagu_anggaran" class="spec-field--readonly" readonly /></td>
      <td><button type="button" class="spec-row-remove danger" title="Hapus baris" data-remove-row="${index}">✕</button></td>
    </tr>
  `;
}

function addSpecRow() {
  const items = collectSpecItems();
  items.push(createEmptySpecItem());
  renderSpecRows(items);
  markSpecDirty();
  updateSpecTotals();
}

function removeSpecRow(index) {
  const remaining = collectSpecItems().filter((_, idx) => idx !== index);
  renderSpecRows(remaining.length ? remaining : [createEmptySpecItem()]);
  markSpecDirty();
  updateSpecTotals();
}

function collectSpecItems() {
  if (!elements.specRows) return [];
  return Array.from(elements.specRows.querySelectorAll('tr')).map((row) => {
    const readText = (selector) => {
      const input = row.querySelector(`[data-spec-field="${selector}"]`);
      return input ? input.value : '';
    };
    const readNumber = (selector) => {
      const raw = readText(selector).replace(',', '.');
      const parsed = parseFloat(raw);
      return Number.isFinite(parsed) ? parsed : 0;
    };
    const volume = readNumber('spesifikasi_jumlah');
    const price = readNumber('harga_satuan');
    const pagu = syncSpecRowPagu(row, volume, price);
    return {
      spesifikasi: readText('spesifikasi').trim(),
      spesifikasi_jumlah: volume,
      satuan_ukuran: readText('satuan_ukuran').trim(),
      harga_satuan: price,
      pagu_anggaran: pagu,
    };
  });
}

function calculateSpecTotals(items) {
  const jumlah = items.reduce((sum, item) => sum + (item.pagu_anggaran || 0), 0);
  const pajak = jumlah * 0.1;
  const total = jumlah + pajak;
  return { jumlah, pajak, total };
}

function renderSpecSummaryTotals(totals) {
  if (elements.sumJumlah) elements.sumJumlah.textContent = formatRupiah(totals.jumlah);
  if (elements.sumPajak) elements.sumPajak.textContent = formatRupiah(totals.pajak);
  if (elements.sumTotal) elements.sumTotal.textContent = formatRupiah(totals.total);
}

function renderSpecSummaryPending() {
  state.totals = { jumlah: 0, pajak: 0, total: 0 };
  if (elements.sumJumlah) elements.sumJumlah.textContent = SPEC_SUMMARY_PENDING_TEXT;
  if (elements.sumPajak) elements.sumPajak.textContent = SPEC_SUMMARY_PENDING_TEXT;
  if (elements.sumTotal) elements.sumTotal.textContent = SPEC_SUMMARY_PENDING_TEXT;
}

function markSpecDirty() {
  if (state.isSpecFinalized) state.isSpecFinalized = false;
  renderSpecSummaryPending();
}

function syncSpecRowPagu(row, presetVolume = null, presetPrice = null) {
  const readNumber = (selector) => {
    const input = row.querySelector(`[data-spec-field="${selector}"]`);
    if (!input) return 0;
    const raw = input.value.replace(',', '.');
    const parsed = parseFloat(raw);
    return Number.isFinite(parsed) ? parsed : 0;
  };
  const volume = presetVolume !== null ? presetVolume : readNumber('spesifikasi_jumlah');
  const price = presetPrice !== null ? presetPrice : readNumber('harga_satuan');
  let computed = volume * price;
  if (!Number.isFinite(computed) || computed < 0) computed = 0;
  const pagu = Math.round(computed);
  const input = row.querySelector('[data-spec-field="pagu_anggaran"]');
  if (input) {
    input.value = (pagu === 0 && volume === 0 && price === 0) ? '' : String(pagu);
  }
  return pagu;
}

function finalizeSpecRows() {
  if (!elements.specRows) return;
  Array.from(elements.specRows.querySelectorAll('tr')).forEach((row) => syncSpecRowPagu(row));
  state.isSpecFinalized = true;
  updateSpecTotals({ force: true });
  toast('Perhitungan anggaran selesai');
}

function updateSpecTotals(options = {}) {
  const items = collectSpecItems();
  if (elements.lembarCount) elements.lembarCount.textContent = `${items.length} item`;
  if (!state.isSpecFinalized && options.force !== true) {
    renderSpecSummaryPending();
    return;
  }
  const totals = calculateSpecTotals(items);
  state.totals = totals;
  renderSpecSummaryTotals(totals);
}

function renderGenerationResult(files = []) {
  if (!elements.generateOutput) return;
  const hasData = files.length > 0;
  elements.generateOutput.hidden = !hasData;
  files.forEach((file) => {
    const card = elements.generateOutput.querySelector(`.output-card[data-file="${file.key}"]`);
    if (!card) return;
    const caption = card.querySelector('small');
    if (caption) {
      const label = OUTPUT_STATUS_LABEL[file.status] || 'Status tidak diketahui';
      caption.textContent = file.message ? `Status: ${label} — ${file.message}` : `Status: ${label}`;
    }
    const button = card.querySelector('button');
    if (button) {
      const downloadUrl = resolveDownloadUrl(file.download_url);
      if (downloadUrl) {
        button.disabled = false;
        button.textContent = 'Unduh';
        button.dataset.downloadUrl = downloadUrl;
      } else {
        button.disabled = true;
        button.textContent = 'Menunggu';
        delete button.dataset.downloadUrl;
      }
    }
  });
}

function handleDownloadClick(event) {
  const button = event.target.closest('button');
  if (!button || !button.dataset.downloadUrl) return;
  window.open(button.dataset.downloadUrl, '_blank');
}

function setGenerateLoading(isLoading, message) {
  state.isGenerating = isLoading;
  if (!elements.generateButton) return;
  if (!elements.generateButton.dataset.defaultLabel) {
    elements.generateButton.dataset.defaultLabel = elements.generateButton.textContent;
  }
  elements.generateButton.disabled = isLoading;
  elements.generateButton.textContent = isLoading ? message || 'Memproses...' : elements.generateButton.dataset.defaultLabel;
}

// Di file frontend/panel.js

async function handleSubmit(event) {
  event.preventDefault();
  if (state.isGenerating) return;

  // 1. Ambil data Form utama
  const payload = collectPayload();
  setGenerateLoading(true, 'Menyimpan data...');

  try {
    // Simpan ke database
    await request(API.form, {
      method: 'POST',
      body: JSON.stringify(payload),
    });

    toast('Data tersimpan. Menjalankan generator...');
    setGenerateLoading(true, 'Menjalankan generator...');

    // 2. Kirim request Generate tanpa checklist
    const currentId = state.data?.general?.id;
    const result = await request(API.generate, { 
      method: 'POST',
      body: JSON.stringify({ id: currentId })
    });

    state.generation = result.data?.files || [];
    renderGenerationResult(state.generation);

    if (result.message) toast(result.message);
    await loadForm();
  } catch (error) {
    console.error(error);
    toast(error.message || 'Gagal menyimpan atau menjalankan generator');
  } finally {
    setGenerateLoading(false);
  }
}

function collectPayload() {
  return {
    general: {
      id: state.data?.general?.id || undefined,
      objek: getFieldValue('general.objek'),
      tanggal: getFieldValue('general.tanggal'),
    },
    dokumen_pengadaan: {
      nama_paket: getFieldValue('dokumen_pengadaan.nama_paket'),
      kode_rup: getFieldValue('dokumen_pengadaan.kode_rup'),
      nomor_dpp: getFieldValue('dokumen_pengadaan.nomor_dpp'),
      pagu_anggaran: getNumericValue('dokumen_pengadaan.pagu_anggaran'),
      tanggal_mulai: getFieldValue('dokumen_pengadaan.tanggal_mulai'),
      tanggal_selesai: getFieldValue('dokumen_pengadaan.tanggal_selesai'),
      pembuat_komitmen_id: getNumericValue('dokumen_pengadaan.pembuat_komitmen_id'),
    },
    lembar_kegiatan: {
      bulan: getFieldValue('lembar_kegiatan.bulan'),
      daftar: getFieldValue('lembar_kegiatan.daftar'),
      program: getFieldValue('lembar_kegiatan.program'),
      kegiatan: getFieldValue('lembar_kegiatan.kegiatan'),
      sub_kegiatan: getFieldValue('lembar_kegiatan.sub_kegiatan'),
      kode_rekening: getFieldValue('lembar_kegiatan.kode_rekening'),
      sumber_dana: getFieldValue('lembar_kegiatan.sumber_dana'),
      kepdin_id: getNumericValue('lembar_kegiatan.kepdin_id'),
      bendahara_id: getNumericValue('lembar_kegiatan.bendahara_id'),
      pptk_id: getNumericValue('lembar_kegiatan.pptk_id'),
    },
    berita_acara: {
      nomor_satuan_kerja: getFieldValue('berita_acara.nomor_satuan_kerja'),
      tanggal_satuan_kerja: getFieldValue('berita_acara.tanggal_satuan_kerja'),
      nomor_sp: getFieldValue('berita_acara.nomor_sp'),
      tanggal_sp: getFieldValue('berita_acara.tanggal_sp'),
      kepdin_id: getNumericValue('berita_acara.kepdin_id'),
      pembuat_komitmen_id: getNumericValue('berita_acara.pembuat_komitmen_id'),
      penyedia_id: getNullableNumericValue('berita_acara.penyedia_id'),
      nomor_sk_bahp: getFieldValue('berita_acara.nomor_sk_bahp'),
      tanggal_sk_bahp: getFieldValue('berita_acara.tanggal_sk_bahp'),
      nomor_sp_bahp: getFieldValue('berita_acara.nomor_sp_bahp'),
      nomor_kontrak_bahp: getFieldValue('berita_acara.nomor_kontrak_bahp'),
      tanggal_sp_bahp: getFieldValue('berita_acara.tanggal_sp_bahp'),
      nomor_sk_bahpa: getFieldValue('berita_acara.nomor_sk_bahpa'),
      tanggal_sk_bahpa: getFieldValue('berita_acara.tanggal_sk_bahpa'),
      nomor_sp_bahpa: getFieldValue('berita_acara.nomor_sp_bahpa'),
      tanggal_sp_bahpa: getFieldValue('berita_acara.tanggal_sp_bahpa'),
      paket_pekerjaan: getFieldValue('berita_acara.paket_pekerjaan'),
      paket_pekerjaan_administratif: getFieldValue('berita_acara.paket_pekerjaan_administratif'),
      tanggal_serah_terima: getFieldValue('berita_acara.tanggal_serah_terima'),
      keterangan: getFieldValue('berita_acara.keterangan'),
    },
    kwitansi: {
      nama_penerima: getFieldValue('kwitansi.nama_penerima'),
      nama_bank: getFieldValue('kwitansi.nama_bank'),
      npwp: getFieldValue('kwitansi.npwp'),
      norek: getFieldValue('kwitansi.norek'),
      kepdin_id: getNumericValue('kwitansi.kepdin_id'),
      bendahara_id: getNumericValue('kwitansi.bendahara_id'),
      pptk_id: getNumericValue('kwitansi.pptk_id'),
      jumlah_uang: getNumericValue('kwitansi.jumlah_uang'),
      tanggal_pembayaran: getFieldValue('kwitansi.tanggal_pembayaran'),
    },
    nota_dinas: {
      nomor: getFieldValue('nota_dinas.nomor'),
      perihal: getFieldValue('nota_dinas.perihal'),
      keperluan: getFieldValue('nota_dinas.keperluan'),
      // Use the calculated total from the specs card (state.totals.total) so NotaDinasExporter
      // receives the form's arithmetic result rather than relying solely on DB aggregation.
      jumlah_dpa: Number.isFinite(state.totals.total) ? state.totals.total : getNumericValue('nota_dinas.jumlah_dpa'),
      tahun_anggaran: getNumericValue('nota_dinas.tahun_anggaran') || deriveYear(getFieldValue('general.tanggal')),
      kabid_id: getNumericValue('nota_dinas.kabid_id'),
    },
    sptpd: {
      tahun: getNumericValue('sptpd.tahun') || deriveYear(getFieldValue('general.tanggal')),
      harga_jual: getNumericValue('sptpd.harga_jual'),
      dasar_pengenaan_pajak: getNumericValue('sptpd.dasar_pengenaan_pajak'),
      pajak_terhutang: getNumericValue('sptpd.pajak_terhutang'),
      nama_badan_usaha: getFieldValue('sptpd.nama_badan_usaha'),
      masa_pajak: getFieldValue('sptpd.masa_pajak'),
      pekerjaan: getFieldValue('sptpd.pekerjaan'),
      telp_kantor: getFieldValue('sptpd.telp_kantor'),
    },
    spesifikasi_anggaran: collectSpecItems(),
    // checklist removed
  };
}

function getFieldElement(field) {
  return document.querySelector(`[data-field="${field}"]`);
}

function getFieldValue(field) {
  const element = getFieldElement(field);
  return element ? element.value.trim() : '';
}

function getNumericValue(field) {
  const raw = getFieldValue(field);
  if (raw === '') return 0;
  return FIELD_TYPE[field] === 'int' ? parseInt(raw, 10) || 0 : parseFloat(raw) || 0;
}

function getNullableNumericValue(field) {
  const raw = getFieldValue(field);
  if (raw === '') return null;
  const parsed = FIELD_TYPE[field] === 'int' ? parseInt(raw, 10) : parseFloat(raw);
  return Number.isNaN(parsed) ? null : parsed;
}

function setFieldValue(field, value) {
  const element = getFieldElement(field);
  if (!element) return;
  if (element.type === 'date' && value) {
    element.value = value.slice(0, 10);
    return;
  }
  element.value = value ?? '';
}

// `deriveYear` moved to `panel.utils.js`

// `request` moved to `panel.utils.js`

function toast(message) {
  if (!elements.toast) { alert(message); return; }
  elements.toast.textContent = message;
  elements.toast.classList.add('is-visible');
  setTimeout(() => elements.toast.classList.remove('is-visible'), 2500);
}

function bindTabs() {
  const triggers = document.querySelectorAll('[data-pane], .tab');
  triggers.forEach((button) => {
    button.addEventListener('click', () => {
      const pane = button.dataset.pane;
      if (!pane) return;
      document.querySelectorAll('.tab').forEach((tab) => {
        tab.classList.toggle('is-active', tab.dataset.pane === pane);
      });
      document.querySelectorAll('.pane').forEach((section) => {
        const isActive = section.id === `pane-${pane}`;
        section.classList.toggle('is-active', isActive);
        section.setAttribute('aria-hidden', isActive ? 'false' : 'true');
      });
    });
  });
}

function renderAllMasterLists() {
  MASTER_KEYS.forEach((entity) => renderMasterList(entity));
}

function renderMasterList(entity) {
  const container = document.querySelector(`[data-master-list="${entity}"]`);
  if (!container) return;
  const config = MASTER_RESOURCES[entity];
  const items = state.masters[entity] || [];
  if (!items.length) {
    container.innerHTML = `<p class="master-empty">${config.allowCreate ? 'Belum ada data. Gunakan tombol Tambah untuk membuat entri baru.' : 'Belum ada data. Hubungi admin jika membutuhkan penambahan.'}</p>`;
    return;
  }
  container.innerHTML = items.map((item) => masterRowTemplate(entity, config, item)).join('');
}

function masterRowTemplate(entity, config, item = {}, options = {}) {
  const dataset = [`data-entity="${entity}"`];
  if (item.id) dataset.push(`data-id="${Number(item.id)}"`);
  if (options.isNew) dataset.push('data-mode="create"');
  if (options.tempId) dataset.push(`data-temp-id="${options.tempId}"`);

  const fields = config.fields
    .map((field) => masterFieldTemplate(field, item[field.key] ?? ''))
    .join('');

  const actions = [];
  const saveLabel = options.isNew || !item.id ? 'Simpan' : 'Perbarui';
  actions.push(`<button type="button" class="master-button" data-master-action="save">${saveLabel}</button>`);
  if (config.allowDelete) {
    const deleteLabel = options.isNew && !item.id ? 'Batalkan' : 'Hapus';
    actions.push(`<button type="button" class="master-button master-button--danger" data-master-action="delete">${deleteLabel}</button>`);
  }
  const actionsHtml = actions.length ? `<div class="master-row__actions">${actions.join('')}</div>` : '';
  return `<div class="master-row" ${dataset.join(' ')}><div class="master-row__fields">${fields}${actionsHtml}</div></div>`;
}

function masterFieldTemplate(field, value) {
  const requiredAttr = field.required ? 'required' : '';
  const placeholder = field.placeholder ? ` placeholder="${escapeHtml(field.placeholder)}"` : '';
  const attrs = `data-master-field="${field.key}" ${requiredAttr}${placeholder}`;
  if (field.type === 'textarea') {
    return `<label><span>${field.label}</span><textarea rows="3" ${attrs}>${escapeHtml(value || '')}</textarea></label>`;
  }
  return `<label><span>${field.label}</span><input type="text" value="${escapeHtml(value || '')}" ${attrs} /></label>`;
}

function handleMasterClick(event) {
  const addButton = event.target.closest('[data-master-add]');
  if (addButton) { addMasterRow(addButton.dataset.masterAdd); return; }
  const actionButton = event.target.closest('[data-master-action]');
  if (!actionButton) return;
  const row = actionButton.closest('.master-row');
  if (!row) return;
  const entity = row.dataset.entity;
  if (!entity || !MASTER_RESOURCES[entity]) return;
  if (actionButton.dataset.masterAction === 'save') saveMasterRow(row, entity);
  else if (actionButton.dataset.masterAction === 'delete') deleteMasterRow(row, entity);
}

function addMasterRow(entity) {
  const config = MASTER_RESOURCES[entity];
  if (!config || !config.allowCreate) return;
  const container = document.querySelector(`[data-master-list="${entity}"]`);
  if (!container) return;
  const emptyState = container.querySelector('.master-empty');
  if (emptyState) emptyState.remove();
  const tempId = `temp-${Date.now()}`;
  container.insertAdjacentHTML('beforeend', masterRowTemplate(entity, config, {}, { isNew: true, tempId }));
  const focusTarget = container.querySelector(`.master-row[data-temp-id="${tempId}"] [data-master-field]`);
  focusTarget?.focus();
}

function collectMasterRowData(row, config) {
  const payload = {};
  let invalidField = null;
  config.fields.forEach((field) => {
    const input = row.querySelector(`[data-master-field="${field.key}"]`);
    const value = input ? input.value.trim() : '';
    payload[field.key] = value;
    if (!invalidField && field.required && value === '') invalidField = { field, input };
  });
  if (invalidField) {
    if (typeof invalidField.input?.reportValidity === 'function') invalidField.input.reportValidity();
    else invalidField.input?.focus();
    toast(`${invalidField.field.label} wajib diisi`);
    return null;
  }
  return payload;
}

async function saveMasterRow(row, entity) {
  const config = MASTER_RESOURCES[entity];
  if (!config) return;
  const payload = collectMasterRowData(row, config);
  if (!payload) return;
  const isCreate = row.dataset.mode === 'create';
  const id = Number(row.dataset.id || 0);
  if (!isCreate && id <= 0) { toast('Data master tidak valid'); return; }
  setMasterRowLoading(row, true, isCreate ? 'Menambah...' : 'Menyimpan...');
  try {
    const query = new URLSearchParams({ entity });
    if (!isCreate) query.append('id', String(id));
    await request(`${API.master}?${query.toString()}`, { method: isCreate ? 'POST' : 'PUT', body: JSON.stringify(payload) });
    toast('Master tersimpan');
    await refreshMasterEntity(entity);
  } catch (error) {
    console.error(error);
    toast(error.message || 'Gagal memperbarui master');
  } finally {
    setMasterRowLoading(row, false);
  }
}

async function deleteMasterRow(row, entity) {
  const config = MASTER_RESOURCES[entity];
  if (!config || !config.allowDelete) { row.remove(); return; }
  if (row.dataset.mode === 'create') { row.remove(); return; }
  const id = Number(row.dataset.id || 0);
  if (id <= 0) { toast('Data master tidak valid'); return; }
  if (!window.confirm('Hapus data ini?')) return;
  setMasterRowLoading(row, true, 'Menghapus...');
  try {
    const query = new URLSearchParams({ entity, id: String(id) });
    await request(`${API.master}?${query.toString()}`, { method: 'DELETE' });
    toast('Data master dihapus');
    await refreshMasterEntity(entity);
  } catch (error) {
    console.error(error);
    toast(error.message || 'Gagal menghapus master');
  } finally {
    setMasterRowLoading(row, false);
  }
}

async function refreshMasterEntity(entity, options = {}) {
  const result = await request(`${API.master}?entity=${entity}`);
  state.masters[entity] = result.data || [];
  renderMasterList(entity);
  if (!options.skipHydrateSelects) hydrateMasterSelects();
  if (!options.skipHealthUpdate) updateMasterHealth();
}

async function refreshAllMasters() {
  setMasterRefreshLoading(true);
  try {
    await Promise.all(MASTER_KEYS.map((entity) => refreshMasterEntity(entity, { skipHydrateSelects: true, skipHealthUpdate: true })));
    hydrateMasterSelects();
    updateMasterHealth();
    toast('Master diperbarui');
  } catch (error) {
    console.error(error);
    toast(error.message || 'Gagal memuat master');
  } finally {
    setMasterRefreshLoading(false);
  }
}

function updateMasterHealth() {
  if (!elements.masterHealth) return;
  const missing = MASTER_KEYS.filter((key) => (state.masters[key] || []).length === 0).map((key) => formatMasterLabel(key));
  elements.masterHealth.textContent = missing.length === 0 ? 'Master siap dipakai' : `Butuh data: ${missing.join(', ')}`;
}

function setMasterRowLoading(row, isLoading, label) {
  const saveButton = row.querySelector('[data-master-action="save"]');
  const deleteButton = row.querySelector('[data-master-action="delete"]');
  if (saveButton) {
    if (!saveButton.dataset.defaultLabel) saveButton.dataset.defaultLabel = saveButton.textContent;
    saveButton.disabled = isLoading;
    saveButton.textContent = isLoading ? label || 'Memproses...' : saveButton.dataset.defaultLabel;
  }
  if (deleteButton) deleteButton.disabled = isLoading;
}

function setMasterRefreshLoading(isLoading) {
  if (!elements.refreshMaster) return;
  if (!elements.refreshMaster.dataset.defaultLabel) elements.refreshMaster.dataset.defaultLabel = elements.refreshMaster.textContent;
  elements.refreshMaster.disabled = isLoading;
  elements.refreshMaster.textContent = isLoading ? 'Memuat...' : elements.refreshMaster.dataset.defaultLabel;
}

function formatMasterLabel(key) {
  return MASTER_RESOURCES[key]?.label || key;
}

function createEmptySpecItem() {
  return { spesifikasi: '', spesifikasi_jumlah: 0, satuan_ukuran: '', harga_satuan: 0, pagu_anggaran: 0 };
}

