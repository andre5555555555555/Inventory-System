<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>

<div class="page-shell">

    <!-- Page header -->
    <div class="page-header">
        <div>
            <p class="page-eyebrow">Reports</p>
            <h1>Export Summary Report</h1>
            <p class="page-subtitle">
                Download the Inventory Summary Report (Beginning &rarr; Purchases &rarr; Used &rarr; Breakage &rarr; Ending)
                as a print-ready PDF, an editable Word document, or a spreadsheet-friendly CSV.
            </p>
        </div>
    </div>

    <?php if (session()->has('error')): ?>
        <div class="flash-message flash-error"><?= esc(session('error')) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= site_url('export/summary') ?>" id="sumExportForm">
        <?= csrf_field() ?>
        <input type="hidden" name="format" id="sumFormatInput" value="">

        <div class="ex-layout">

            <!-- ── LEFT COLUMN ──────────────────────────────── -->
            <div class="ex-main">

                <!-- Panel 1: Filter -->
                <div class="ex-panel">
                    <div class="ex-panel-header">
                        <span class="ex-panel-icon">🔍</span>
                        <div>
                            <h2 class="ex-panel-title">Period</h2>
                            <p class="ex-panel-sub">Select the month range to include in the report</p>
                        </div>
                    </div>
                    <div class="ex-panel-body">

                        <div class="ex-field-row ex-two-col">
                            <!-- From month -->
                            <div class="ex-field">
                                <label class="ex-label" for="sum_month_from">
                                    <span class="ex-label-icon">📅</span> From Month
                                </label>
                                <input type="month" name="month_from" id="sum_month_from" class="ex-input"
                                       value="<?= date('Y-m') ?>" required
                                       onchange="sumUpdatePreview()">
                            </div>
                            <!-- To month -->
                            <div class="ex-field">
                                <label class="ex-label" for="sum_month_to">
                                    <span class="ex-label-icon">📅</span> To Month
                                </label>
                                <input type="month" name="month_to" id="sum_month_to" class="ex-input"
                                       value="<?= date('Y-m') ?>" required
                                       onchange="sumUpdatePreview()">
                            </div>
                        </div>

                        <!-- Product Type filter -->
                        <div class="ex-field">
                            <label class="ex-label" for="sum_type_id">
                                <span class="ex-label-icon">🗂️</span> Product Type
                            </label>
                            <select name="type_id" id="sum_type_id" class="ex-select" onchange="sumUpdatePreview()">
                                <option value="0">— All Product Types —</option>
                                <?php foreach (($productTypes ?? []) as $pt): ?>
                                    <option value="<?= (int) $pt['type_id'] ?>"><?= esc($pt['type']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                    </div>
                </div>

                <!-- Panel 2: Output Options -->
                <div class="ex-panel">
                    <div class="ex-panel-header">
                        <span class="ex-panel-icon">⚙️</span>
                        <div>
                            <h2 class="ex-panel-title">Output Options</h2>
                            <p class="ex-panel-sub">Configure the paper size for PDF / Word output</p>
                        </div>
                    </div>
                    <div class="ex-panel-body">

                        <div class="ex-field-row ex-two-col">

                            <!-- Paper size -->
                            <div class="ex-field">
                                <label class="ex-label">
                                    <span class="ex-label-icon">📄</span> Paper Size
                                </label>
                                <div class="ex-card-radio-group">
                                    <label class="ex-card-radio">
                                        <input type="radio" name="paper_size" value="long" checked onchange="sumUpdatePreview()">
                                        <div class="ex-card-radio-body">
                                            <span class="ex-card-radio-icon">📄</span>
                                            <strong>Long</strong>
                                            <small>8.5″ × 13″ Folio</small>
                                        </div>
                                    </label>
                                    <label class="ex-card-radio">
                                        <input type="radio" name="paper_size" value="short" onchange="sumUpdatePreview()">
                                        <div class="ex-card-radio-body">
                                            <span class="ex-card-radio-icon">📃</span>
                                            <strong>Short</strong>
                                            <small>8.5″ × 11″ Letter</small>
                                        </div>
                                    </label>
                                    <label class="ex-card-radio">
                                        <input type="radio" name="paper_size" value="a4" onchange="sumUpdatePreview()">
                                        <div class="ex-card-radio-body">
                                            <span class="ex-card-radio-icon">📋</span>
                                            <strong>A4</strong>
                                            <small>210mm × 297mm</small>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Info box -->
                            <div class="ex-field">
                                <label class="ex-label">
                                    <span class="ex-label-icon">ℹ️</span> Report Columns
                                </label>
                                <div class="sum-info-box">
                                    <div class="sum-col-badge sum-col-beg">Beginning Inventory</div>
                                    <div class="sum-col-badge sum-col-pur">Purchases</div>
                                    <div class="sum-col-badge sum-col-use">Used</div>
                                    <div class="sum-col-badge sum-col-brk">Breakage</div>
                                    <div class="sum-col-badge sum-col-end">Ending Inventory</div>
                                    <p class="sum-info-note">Each section shows Qty, Unit Cost, and Amount.</p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div><!-- /.ex-main -->

            <!-- ── RIGHT COLUMN ─────────────────────────────── -->
            <div class="ex-sidebar">

                <!-- Preview summary -->
                <div class="ex-preview-card">
                    <div class="ex-preview-header">
                        <span>📊</span>
                        <span>Export Summary</span>
                    </div>
                    <div class="ex-preview-body">
                        <div class="ex-preview-row">
                            <span class="ex-preview-lbl">Report</span>
                            <span class="ex-preview-val">Inventory Summary</span>
                        </div>
                        <div class="ex-preview-row">
                            <span class="ex-preview-lbl">Period</span>
                            <span class="ex-preview-val" id="sum-prev-period">—</span>
                        </div>
                        <div class="ex-preview-row">
                            <span class="ex-preview-lbl">Paper</span>
                            <span class="ex-preview-val" id="sum-prev-paper">Long (Folio)</span>
                        </div>
                        <div class="ex-preview-row">
                            <span class="ex-preview-lbl">Orientation</span>
                            <span class="ex-preview-val">Landscape</span>
                        </div>
                    </div>
                </div>

                <!-- Download buttons -->
                <div class="ex-download-card">
                    <p class="ex-download-title">Download As</p>

                    <button type="button" class="ex-dl-btn ex-dl-pdf" id="sumBtnPdf" onclick="sumSubmitAs('pdf')">
                        <span class="ex-dl-icon">📄</span>
                        <span class="ex-dl-info">
                            <strong>PDF</strong>
                            <small>Print-ready landscape</small>
                        </span>
                        <span class="ex-dl-arrow">→</span>
                    </button>

                    <button type="button" class="ex-dl-btn ex-dl-word" id="sumBtnWord" onclick="sumSubmitAs('word')">
                        <span class="ex-dl-icon">📝</span>
                        <span class="ex-dl-info">
                            <strong>Word</strong>
                            <small>Editable .doc file</small>
                        </span>
                        <span class="ex-dl-arrow">→</span>
                    </button>

                    <button type="button" class="ex-dl-btn ex-dl-csv" id="sumBtnCsv" onclick="sumSubmitAs('csv')">
                        <span class="ex-dl-icon">📊</span>
                        <span class="ex-dl-info">
                            <strong>CSV</strong>
                            <small>Spreadsheet format</small>
                        </span>
                        <span class="ex-dl-arrow">→</span>
                    </button>
                </div>

            </div><!-- /.ex-sidebar -->

        </div><!-- /.ex-layout -->

    </form>
</div>

<script>
function sumSubmitAs(format) {
    document.getElementById('sumFormatInput').value = format;
    document.getElementById('sumExportForm').submit();
}

function sumUpdatePreview() {
    const from  = document.getElementById('sum_month_from').value;
    const to    = document.getElementById('sum_month_to').value;
    const fmt   = v => { if (!v) return '—'; const [y,m] = v.split('-'); return new Date(y,m-1).toLocaleString('default',{month:'short',year:'numeric'}); };
    document.getElementById('sum-prev-period').textContent = from === to ? fmt(from) : fmt(from) + ' – ' + fmt(to);

    const paper = document.querySelector('input[name="paper_size"]:checked')?.value;
    const paperLabels = { long: 'Long (Folio)', short: 'Short (Letter)', a4: 'A4' };
    document.getElementById('sum-prev-paper').textContent = paperLabels[paper] ?? 'Long (Folio)';
}

document.addEventListener('DOMContentLoaded', sumUpdatePreview);
</script>

<style>
/* ── Info box ────────────────────────────────────────────── */
.sum-info-box {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 14px 16px;
    border-radius: 12px;
    border: 1.5px solid var(--border-color, #e5e7eb);
    background: var(--sidebar-bg, #f9fafb);
}
.sum-col-badge {
    padding: 5px 10px;
    border-radius: 8px;
    font-size: 11.5px;
    font-weight: 600;
    text-align: center;
}
.sum-col-beg { background: #dbeafe; color: #1e40af; }
.sum-col-pur { background: #dcfce7; color: #166534; }
.sum-col-use { background: #fef3c7; color: #92400e; }
.sum-col-brk { background: #fee2e2; color: #991b1b; }
.sum-col-end { background: #f3e8ff; color: #6b21a8; }
.sum-info-note {
    font-size: 11px;
    color: var(--text-muted, #9ca3af);
    margin-top: 4px;
    text-align: center;
}

/* ── CSV button ──────────────────────────────────────────── */
.ex-dl-csv { background: linear-gradient(135deg, #059669 0%, #047857 100%); color: #fff; }

/* ── Inherit stockcard form styles ───────────────────────── */
/* ── Layout ──────────────────────────────────────────────── */
.ex-layout {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 24px;
    align-items: start;
    max-width: 1000px;
}
@media (max-width: 768px) {
    .ex-layout { grid-template-columns: 1fr; }
    .ex-sidebar { order: -1; }
}
.ex-panel {
    background: var(--card-bg, #fff);
    border: 1px solid var(--border-color, #e5e7eb);
    border-radius: 16px;
    margin-bottom: 20px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}
.ex-panel-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 18px 22px;
    border-bottom: 1px solid var(--border-color, #e5e7eb);
    background: var(--sidebar-bg, #f9fafb);
}
.ex-panel-icon { font-size: 1.4rem; line-height: 1; }
.ex-panel-title { font-size: 15px; font-weight: 700; margin: 0 0 2px; color: var(--text-primary, #111827); }
.ex-panel-sub   { font-size: 12px; color: var(--text-muted, #9ca3af); margin: 0; }
.ex-panel-body  { padding: 22px; display: flex; flex-direction: column; gap: 20px; }

.ex-field-row { display: flex; flex-direction: column; gap: 16px; }
.ex-two-col   { flex-direction: row; gap: 20px; }
.ex-two-col .ex-field { flex: 1; }
.ex-field { display: flex; flex-direction: column; gap: 7px; }

.ex-label { font-size: 12.5px; font-weight: 600; color: var(--text-secondary, #374151); display: flex; align-items: center; gap: 5px; }
.ex-label-icon { font-size: 13px; }

.ex-select, .ex-input {
    width: 100%; padding: 10px 13px; border-radius: 10px;
    border: 1.5px solid var(--border-color, #d1d5db);
    background: var(--input-bg, #fff); color: var(--text-primary, #111827);
    font-size: 13.5px; font-family: inherit;
    transition: border-color 0.15s, box-shadow 0.15s; box-sizing: border-box;
}
.ex-select:focus, .ex-input:focus {
    outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
}

.ex-card-radio-group { display: flex; flex-direction: column; gap: 8px; }
.ex-card-radio { display: block; cursor: pointer; }
.ex-card-radio input { display: none; }
.ex-card-radio-body {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px; border-radius: 10px;
    border: 1.5px solid var(--border-color, #d1d5db);
    background: var(--card-bg, #fff);
    transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
}
.ex-card-radio-body:hover { border-color: #93c5fd; background: #eff6ff; }
.ex-card-radio input:checked + .ex-card-radio-body {
    border-color: #3b82f6; background: #eff6ff;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.10);
}
.ex-card-radio-icon { font-size: 1.1rem; }
.ex-card-radio-body strong { font-size: 13px; color: var(--text-primary, #111827); }
.ex-card-radio-body small  { font-size: 11px; color: var(--text-muted, #9ca3af); margin-left: auto; }

.ex-sidebar { display: flex; flex-direction: column; gap: 18px; position: sticky; top: 20px; }

.ex-preview-card {
    background: var(--card-bg, #fff); border: 1px solid var(--border-color, #e5e7eb);
    border-radius: 16px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}
.ex-preview-header {
    display: flex; align-items: center; gap: 8px;
    padding: 14px 18px; font-size: 13px; font-weight: 700;
    border-bottom: 1px solid var(--border-color, #e5e7eb);
    background: linear-gradient(135deg, #1e3a5f 0%, #2d5fa6 100%); color: #fff;
}
.ex-preview-body { padding: 16px 18px; display: flex; flex-direction: column; gap: 12px; }
.ex-preview-row  { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
.ex-preview-lbl  { font-size: 11.5px; color: var(--text-muted, #9ca3af); font-weight: 500; white-space: nowrap; }
.ex-preview-val  { font-size: 12.5px; font-weight: 600; color: var(--text-primary, #111827); text-align: right; word-break: break-word; }

.ex-download-card {
    background: var(--card-bg, #fff); border: 1px solid var(--border-color, #e5e7eb);
    border-radius: 16px; padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    display: flex; flex-direction: column; gap: 12px;
}
.ex-download-title { font-size: 12.5px; font-weight: 700; color: var(--text-muted, #9ca3af); letter-spacing: 0.06em; text-transform: uppercase; margin: 0 0 4px; }

.ex-dl-btn {
    display: flex; align-items: center; gap: 12px; width: 100%;
    padding: 14px 16px; border-radius: 12px; border: none;
    cursor: pointer; font-family: inherit;
    transition: transform 0.15s, box-shadow 0.15s, filter 0.15s; text-align: left;
}
.ex-dl-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.18); filter: brightness(1.06); }
.ex-dl-btn:active { transform: translateY(0); }

.ex-dl-pdf  { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: #fff; }
.ex-dl-word { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: #fff; }

.ex-dl-icon { font-size: 1.6rem; line-height: 1; flex-shrink: 0; }
.ex-dl-info { display: flex; flex-direction: column; gap: 1px; flex: 1; }
.ex-dl-info strong { font-size: 14px; font-weight: 700; }
.ex-dl-info small  { font-size: 11px; opacity: 0.85; }
.ex-dl-arrow { font-size: 16px; opacity: 0.7; margin-left: auto; }

@media (max-width: 520px) {
    .ex-two-col { flex-direction: column; }
}

/* ── Dark theme overrides ────────────────────────────────── */
html[data-theme="dark"] .ex-layout {
    --card-bg: rgba(20, 30, 38, 0.96);
    --sidebar-bg: rgba(18, 31, 39, 0.95);
    --border-color: rgba(148, 163, 184, 0.18);
    --text-primary: #f8fafc; --text-secondary: #dce7ea;
    --text-muted: #9fb2bb; --input-bg: #0b151c;
}
html[data-theme="dark"] .ex-card-radio-body:hover { border-color: #2dd4bf; background: rgba(45,212,191,0.10); }
html[data-theme="dark"] .ex-card-radio input:checked + .ex-card-radio-body {
    border-color: #2dd4bf; background: rgba(45,212,191,0.14); box-shadow: 0 0 0 3px rgba(45,212,191,0.10);
}
html[data-theme="dark"] .ex-preview-header { background: linear-gradient(135deg, #071316 0%, #0f766e 100%); border-color: rgba(148,163,184,0.28); }
html[data-theme="dark"] .sum-info-box { --sidebar-bg: rgba(15, 30, 40, 0.95); }

/* ── BSU theme overrides ─────────────────────────────────── */
html[data-theme="bsu"] .ex-layout {
    --card-bg: rgba(255, 247, 237, 0.98); --sidebar-bg: rgba(255, 250, 234, 0.96);
    --border-color: rgba(146, 64, 14, 0.28); --text-primary: #2b170c;
    --text-secondary: #3b220f; --text-muted: #6b3f1d; --input-bg: #fff7ed;
}
html[data-theme="bsu"] .ex-card-radio-body:hover { border-color: #b45309; background: rgba(245,158,11,0.15); }
html[data-theme="bsu"] .ex-card-radio input:checked + .ex-card-radio-body {
    border-color: #b45309; background: rgba(245,158,11,0.20); box-shadow: 0 0 0 3px rgba(180,83,9,0.15);
}
html[data-theme="bsu"] .ex-preview-header { background: linear-gradient(135deg, #5b3314 0%, #92400e 100%); border-color: rgba(146,64,14,0.34); }
</style>

<?= $this->endSection() ?>
