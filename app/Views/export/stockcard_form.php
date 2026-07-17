<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>

<div class="page-shell">

    <!-- Page header -->
    <div class="page-header">
        <div>
            <p class="page-eyebrow">Reports</p>
            <h1>Export Stock Card</h1>
            <p class="page-subtitle">
                Generate a formatted stock card report. Download as a print-ready PDF or an editable Word document.
            </p>
        </div>
    </div>

    <?php if (session()->has('error')): ?>
        <div class="flash-message flash-error"><?= esc(session('error')) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= site_url('export/stockcard') ?>" id="exportForm">
        <?= csrf_field() ?>
        <input type="hidden" name="format" id="formatInput" value="">

        <div class="ex-layout">

            <!-- ── LEFT COLUMN ──────────────────────────────── -->
            <div class="ex-main">

                <!-- Panel 1: Filter -->
                <div class="ex-panel">
                    <div class="ex-panel-header">
                        <span class="ex-panel-icon">🔍</span>
                        <div>
                            <h2 class="ex-panel-title">Filter</h2>
                            <p class="ex-panel-sub">Choose which product and date range to include</p>
                        </div>
                    </div>
                    <div class="ex-panel-body">

                        <div class="ex-field-row">
                            <!-- Product -->
                            <div class="ex-field">
                                <label class="ex-label" for="product_id">
                                    <span class="ex-label-icon">📦</span> Product
                                </label>
                                <select name="product_id" id="product_id" class="ex-select" onchange="updatePreview()">
                                    <option value="0">— All Products —</option>
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?= (int) $p['product_id'] ?>">
                                            <?= esc($p['product']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="ex-field-row ex-two-col">
                            <!-- From month -->
                            <div class="ex-field">
                                <label class="ex-label" for="month_from">
                                    <span class="ex-label-icon">📅</span> From Month
                                </label>
                                <input type="month" name="month_from" id="month_from" class="ex-input"
                                       value="<?= date('Y-m', strtotime('-1 month')) ?>" required
                                       onchange="updatePreview()">
                            </div>
                            <!-- To month -->
                            <div class="ex-field">
                                <label class="ex-label" for="month_to">
                                    <span class="ex-label-icon">📅</span> To Month
                                </label>
                                <input type="month" name="month_to" id="month_to" class="ex-input"
                                       value="<?= date('Y-m') ?>" required
                                       onchange="updatePreview()">
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Panel 2: Output Options -->
                <div class="ex-panel">
                    <div class="ex-panel-header">
                        <span class="ex-panel-icon">⚙️</span>
                        <div>
                            <h2 class="ex-panel-title">Output Options</h2>
                            <p class="ex-panel-sub">Configure layout and date ordering</p>
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
                                        <input type="radio" name="paper_size" value="long" checked onchange="updatePreview()">
                                        <div class="ex-card-radio-body">
                                            <span class="ex-card-radio-icon">📄</span>
                                            <strong>Long</strong>
                                            <small>8.5″ × 13″ Folio</small>
                                        </div>
                                    </label>
                                    <label class="ex-card-radio">
                                        <input type="radio" name="paper_size" value="short" onchange="updatePreview()">
                                        <div class="ex-card-radio-body">
                                            <span class="ex-card-radio-icon">📃</span>
                                            <strong>Short</strong>
                                            <small>8.5″ × 11″ Letter</small>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Sort order -->
                            <div class="ex-field">
                                <label class="ex-label">
                                    <span class="ex-label-icon">↕️</span> Date Order
                                </label>
                                <div class="ex-card-radio-group">
                                    <label class="ex-card-radio">
                                        <input type="radio" name="sort_order" value="ASC" checked onchange="updatePreview()">
                                        <div class="ex-card-radio-body">
                                            <span class="ex-card-radio-icon">⬆</span>
                                            <strong>Ascending</strong>
                                            <small>Oldest first</small>
                                        </div>
                                    </label>
                                    <label class="ex-card-radio">
                                        <input type="radio" name="sort_order" value="DESC" onchange="updatePreview()">
                                        <div class="ex-card-radio-body">
                                            <span class="ex-card-radio-icon">⬇</span>
                                            <strong>Descending</strong>
                                            <small>Newest first</small>
                                        </div>
                                    </label>
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
                        <span>📋</span>
                        <span>Export Summary</span>
                    </div>
                    <div class="ex-preview-body">
                        <div class="ex-preview-row">
                            <span class="ex-preview-lbl">Product</span>
                            <span class="ex-preview-val" id="prev-product">All Products</span>
                        </div>
                        <div class="ex-preview-row">
                            <span class="ex-preview-lbl">Period</span>
                            <span class="ex-preview-val" id="prev-period">—</span>
                        </div>
                        <div class="ex-preview-row">
                            <span class="ex-preview-lbl">Paper</span>
                            <span class="ex-preview-val" id="prev-paper">Long (Folio)</span>
                        </div>
                        <div class="ex-preview-row">
                            <span class="ex-preview-lbl">Order</span>
                            <span class="ex-preview-val" id="prev-order">Ascending</span>
                        </div>
                    </div>
                </div>

                <!-- Download buttons -->
                <div class="ex-download-card">
                    <p class="ex-download-title">Download As</p>
                    <button type="button" class="ex-dl-btn ex-dl-pdf" onclick="submitAs('pdf')">
                        <span class="ex-dl-icon">📄</span>
                        <span class="ex-dl-info">
                            <strong>PDF</strong>
                            <small>Print-ready format</small>
                        </span>
                        <span class="ex-dl-arrow">→</span>
                    </button>
                    <button type="button" class="ex-dl-btn ex-dl-word" onclick="submitAs('word')">
                        <span class="ex-dl-icon">📝</span>
                        <span class="ex-dl-info">
                            <strong>Word</strong>
                            <small>Editable .doc file</small>
                        </span>
                        <span class="ex-dl-arrow">→</span>
                    </button>
                </div>

            </div><!-- /.ex-sidebar -->

        </div><!-- /.ex-layout -->

    </form>
</div>

<script>
function submitAs(format) {
    document.getElementById('formatInput').value = format;
    document.getElementById('exportForm').submit();
}

function updatePreview() {
    // Product
    const sel = document.getElementById('product_id');
    const prodText = sel.options[sel.selectedIndex].text.trim();
    document.getElementById('prev-product').textContent = prodText;

    // Period
    const from = document.getElementById('month_from').value;
    const to   = document.getElementById('month_to').value;
    const fmt  = v => { if (!v) return '—'; const [y,m] = v.split('-'); return new Date(y,m-1).toLocaleString('default',{month:'short',year:'numeric'}); };
    document.getElementById('prev-period').textContent = from === to ? fmt(from) : fmt(from) + ' – ' + fmt(to);

    // Paper
    const paper = document.querySelector('input[name="paper_size"]:checked')?.value;
    document.getElementById('prev-paper').textContent = paper === 'short' ? 'Short (Letter)' : 'Long (Folio)';

    // Order
    const order = document.querySelector('input[name="sort_order"]:checked')?.value;
    document.getElementById('prev-order').textContent = order === 'DESC' ? 'Descending' : 'Ascending';
}

// Init on load
document.addEventListener('DOMContentLoaded', updatePreview);
</script>

<style>
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

/* ── Panel card ───────────────────────────────────────────── */
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
.ex-panel-title {
    font-size: 15px;
    font-weight: 700;
    margin: 0 0 2px;
    color: var(--text-primary, #111827);
}
.ex-panel-sub {
    font-size: 12px;
    color: var(--text-muted, #9ca3af);
    margin: 0;
}
.ex-panel-body { padding: 22px; display: flex; flex-direction: column; gap: 20px; }

/* ── Field layout ─────────────────────────────────────────── */
.ex-field-row { display: flex; flex-direction: column; gap: 16px; }
.ex-two-col   { flex-direction: row; gap: 20px; }
.ex-two-col .ex-field { flex: 1; }
.ex-field { display: flex; flex-direction: column; gap: 7px; }

.ex-label {
    font-size: 12.5px;
    font-weight: 600;
    color: var(--text-secondary, #374151);
    display: flex;
    align-items: center;
    gap: 5px;
}
.ex-label-icon { font-size: 13px; }

.ex-select, .ex-input {
    width: 100%;
    padding: 10px 13px;
    border-radius: 10px;
    border: 1.5px solid var(--border-color, #d1d5db);
    background: var(--input-bg, #fff);
    color: var(--text-primary, #111827);
    font-size: 13.5px;
    font-family: inherit;
    transition: border-color 0.15s, box-shadow 0.15s;
    box-sizing: border-box;
}
.ex-select:focus, .ex-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
}

/* ── Card radio buttons (paper size / sort) ──────────────── */
.ex-card-radio-group { display: flex; flex-direction: column; gap: 8px; }

.ex-card-radio {
    display: block;
    cursor: pointer;
}
.ex-card-radio input { display: none; }
.ex-card-radio-body {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 10px;
    border: 1.5px solid var(--border-color, #d1d5db);
    background: var(--card-bg, #fff);
    transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
}
.ex-card-radio-body:hover {
    border-color: #93c5fd;
    background: #eff6ff;
}
.ex-card-radio input:checked + .ex-card-radio-body {
    border-color: #3b82f6;
    background: #eff6ff;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.10);
}
.ex-card-radio-icon { font-size: 1.1rem; }
.ex-card-radio-body strong { font-size: 13px; color: var(--text-primary, #111827); }
.ex-card-radio-body small  { font-size: 11px; color: var(--text-muted, #9ca3af); margin-left: auto; }

/* ── Sidebar ──────────────────────────────────────────────── */
.ex-sidebar { display: flex; flex-direction: column; gap: 18px; position: sticky; top: 20px; }

/* Preview card */
.ex-preview-card {
    background: var(--card-bg, #fff);
    border: 1px solid var(--border-color, #e5e7eb);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}
.ex-preview-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 14px 18px;
    font-size: 13px;
    font-weight: 700;
    color: var(--text-primary, #111827);
    border-bottom: 1px solid var(--border-color, #e5e7eb);
    background: linear-gradient(135deg, #1e3a5f 0%, #2d5fa6 100%);
    color: #fff;
}
.ex-preview-body { padding: 16px 18px; display: flex; flex-direction: column; gap: 12px; }
.ex-preview-row  { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
.ex-preview-lbl  { font-size: 11.5px; color: var(--text-muted, #9ca3af); font-weight: 500; white-space: nowrap; }
.ex-preview-val  {
    font-size: 12.5px;
    font-weight: 600;
    color: var(--text-primary, #111827);
    text-align: right;
    word-break: break-word;
}

/* Download card */
.ex-download-card {
    background: var(--card-bg, #fff);
    border: 1px solid var(--border-color, #e5e7eb);
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.ex-download-title {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--text-muted, #9ca3af);
    letter-spacing: 0.06em;
    text-transform: uppercase;
    margin: 0 0 4px;
}

.ex-dl-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    padding: 14px 16px;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    font-family: inherit;
    transition: transform 0.15s, box-shadow 0.15s, filter 0.15s;
    text-align: left;
}
.ex-dl-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.18);
    filter: brightness(1.06);
}
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
</style>

<?= $this->endSection() ?>
