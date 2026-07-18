<?php $this->extend('layouts/main') ?>

<?php $this->section('content') ?>
<div class="page-shell stockout-page">
    <div class="page-header">
        <div>
            <p class="page-eyebrow">Stock Out</p>
            <h1>Request Stock Out</h1>
            <p class="page-subtitle">Search for a product or scan a barcode to add it to your temporary stock-out list.</p>
        </div>
        <a href="<?= site_url('stockout/temp') ?>" class="btn-primary">View My Temp List</a>
    </div>

    <!-- ── MANUAL FORM SECTION ─────────────────────────────────────────────── -->
    <div class="section-card" id="manualFormSection">
        <div class="stockout-form-card">
            <h2>Add Product to Stock-Out</h2>
            <form method="post" action="<?= site_url('stockout/add-temp') ?>" class="stockout-form" id="stockoutManualForm">
                <?= csrf_field() ?>

                <!-- Product Picker -->
                <div class="form-group">
                    <label>Product <span class="label-hint">tap to search or scan barcode</span></label>

                    <!-- Custom dropdown trigger -->
                    <div class="so-picker" id="soPicker">
                        <button type="button" class="so-picker-btn" id="soPickerBtn" aria-haspopup="listbox" aria-expanded="false">
                            <span class="so-picker-icon">📦</span>
                            <span class="so-picker-text" id="soPickerText">Select product…</span>
                            <svg class="so-chevron" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M5 8l5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>

                        <!-- Dropdown panel -->
                        <div class="so-panel" id="soPanel" role="listbox" aria-label="Products">
                            <div class="so-search-wrap">
                                <svg class="so-search-icon" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.6"/>
                                    <path d="M14 14l3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                </svg>
                                <input
                                    type="text"
                                    id="soSearchInput"
                                    class="so-search-input"
                                    placeholder="Search product or scan barcode…"
                                    autocomplete="off"
                                    spellcheck="false"
                                    data-lookup-url="<?= site_url('barcode/lookup') ?>"
                                >
                            </div>
                            <ul class="so-list" id="soList" role="listbox"></ul>
                        </div>
                    </div>

                    <input type="hidden" name="product_id" id="product_id_hidden">
                    <div id="barcodeError" class="so-error" style="display:none;"></div>
                </div>

                <!-- Autofilled read-only fields -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="unit">Unit</label>
                        <input type="text" name="unit" id="unit" class="readonly-field" placeholder="—" readonly tabindex="-1">
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <input type="text" name="description" id="description" class="readonly-field" placeholder="—" readonly tabindex="-1">
                    </div>
                </div>

                <!-- Quantity -->
                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input type="number" name="quantity" id="quantity" min="1" placeholder="Enter quantity" required>
                </div>

                <button type="submit" class="btn-primary" id="stockoutSubmitBtn" disabled>
                    <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;vertical-align:middle;margin-right:6px;">
                        <path d="M10 4v12M4 10h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Add to Temp List
                </button>
            </form>
        </div>
    </div>

    <!-- ── AVAILABLE PRODUCTS TABLE ───────────────────────────────────────── -->
    <div class="section-card">
        <h2>Available Products</h2>
        <input type="text" class="search-input" id="stockoutSearch" placeholder="Search products…" onkeyup="filterStockoutTable()">
        <table class="data-table" id="stockoutItemsTable">
            <tr>
                <th>Product</th>
                <th>Unit</th>
                <th>Description</th>
                <th>Current Stock</th>
            </tr>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= esc($item['product']) ?></td>
                    <td><?= esc($item['unit_name'] ?? '') ?></td>
                    <td><?= esc($item['product_description'] ?? $item['description'] ?? '') ?></td>
                    <td><?= (int) $item['current_stock'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<style>
/* ── Label hint ────────────────────────────────────────────────────────── */
.label-hint {
    font-weight: 400;
    font-size: 11px;
    color: #94a3b8;
    margin-left: 4px;
}

/* ── Product Picker Button ─────────────────────────────────────────────── */
.so-picker {
    position: relative;
    width: 100%;
}

.so-picker-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 11px 14px;
    border: 1.5px solid var(--border-color, #d1d5db);
    border-radius: 10px;
    background: var(--input-bg, #fff);
    cursor: pointer;
    font-family: inherit;
    font-size: 14px;
    color: var(--text-muted, #9ca3af);
    text-align: left;
    transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
    outline: none;
    -webkit-appearance: none;
    appearance: none;
}
.so-picker-btn:hover,
.so-picker-btn:focus-visible {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
}
.so-picker-btn.has-value {
    color: var(--text-primary, #111827);
    border-color: #0f766e;
    background: rgba(15, 118, 110, 0.04);
}
.so-picker-btn.has-value .so-picker-icon { opacity: 1; }

.so-picker-icon {
    font-size: 16px;
    flex-shrink: 0;
    opacity: 0.5;
    transition: opacity 0.15s;
}
.so-picker-text {
    flex: 1;
    min-width: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-weight: 500;
}
.so-chevron {
    width: 18px;
    height: 18px;
    color: #9ca3af;
    flex-shrink: 0;
    transition: transform 0.2s ease, color 0.15s;
}
.so-picker.is-open .so-chevron {
    transform: rotate(180deg);
    color: #6366f1;
}

/* ── Dropdown Panel ────────────────────────────────────────────────────── */
.so-panel {
    display: none;
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    right: 0;
    background: var(--card-bg, #fff);
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 12px 32px rgba(0,0,0,0.14);
    z-index: 1000;
    overflow: hidden;
    flex-direction: column;
    max-height: 320px;
}
.so-picker.is-open .so-panel {
    display: flex;
    animation: soPanelIn 0.15s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes soPanelIn {
    from { opacity: 0; transform: translateY(-6px) scale(0.98); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

/* ── Search inside panel ───────────────────────────────────────────────── */
.so-search-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    border-bottom: 1px solid var(--border-color, #e5e7eb);
    background: var(--sidebar-bg, #f8fafc);
    flex-shrink: 0;
}
.so-search-icon {
    width: 16px;
    height: 16px;
    color: #9ca3af;
    flex-shrink: 0;
}
.so-search-input {
    flex: 1;
    border: none;
    outline: none;
    background: transparent;
    font-size: 13.5px;
    font-family: inherit;
    color: var(--text-primary, #111827);
    min-width: 0;
}
.so-search-input::placeholder { color: #b0bec5; }

/* ── List ──────────────────────────────────────────────────────────────── */
.so-list {
    list-style: none;
    margin: 0;
    padding: 4px 0;
    overflow-y: auto;
    flex: 1;
}
.so-list:empty::after {
    content: 'No products found';
    display: block;
    text-align: center;
    color: #94a3b8;
    font-size: 13px;
    padding: 20px;
}

.so-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    cursor: pointer;
    transition: background 0.1s;
    border-bottom: 1px solid var(--border-color, #f1f5f9);
}
.so-item:last-child { border-bottom: none; }
.so-item:hover,
.so-item.is-focused {
    background: #eef2ff;
}
.so-item-icon {
    font-size: 18px;
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: -0.5px;
}
.so-item-body {
    flex: 1;
    min-width: 0;
}
.so-item-name {
    font-weight: 600;
    font-size: 13.5px;
    color: var(--text-primary, #111827);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.so-item-meta {
    font-size: 11.5px;
    color: #64748b;
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.so-stock-badge {
    flex-shrink: 0;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 20px;
    background: #dcfce7;
    color: #15803d;
}
.so-stock-badge.low  { background: #fef3c7; color: #b45309; }
.so-stock-badge.zero { background: #fee2e2; color: #b91c1c; }

/* ── Readonly filled fields ────────────────────────────────────────────── */
.readonly-field {
    background: var(--sidebar-bg, #f9fafb) !important;
    color: var(--text-muted, #6b7280) !important;
    cursor: not-allowed;
    border-color: var(--border-color, #e5e7eb) !important;
}
.readonly-field.filled {
    background: rgba(15, 118, 110, 0.05) !important;
    color: var(--text-primary, #111827) !important;
    border-color: #0f766e !important;
    font-weight: 500;
}

/* ── Error ─────────────────────────────────────────────────────────────── */
.so-error {
    color: #ef4444;
    font-size: 12.5px;
    margin-top: 5px;
    padding: 6px 10px;
    background: #fef2f2;
    border-radius: 6px;
    border: 1px solid #fecaca;
}

/* ── Form row side-by-side ─────────────────────────────────────────────── */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
@media (max-width: 480px) {
    .form-row { grid-template-columns: 1fr; }
}

/* ── Dark theme overrides ──────────────────────────────────────────────── */
html[data-theme="dark"] .so-picker-btn {
    background: var(--card-bg, #1e293b);
    color: var(--text-muted, #94a3b8);
}
html[data-theme="dark"] .so-picker-btn.has-value {
    color: var(--text-primary, #f1f5f9);
}
html[data-theme="dark"] .so-panel {
    background: var(--card-bg, #1e293b);
    border-color: rgba(148,163,184,0.15);
}
html[data-theme="dark"] .so-search-wrap {
    background: rgba(255,255,255,0.04);
    border-color: rgba(148,163,184,0.15);
}
html[data-theme="dark"] .so-search-input { color: #f1f5f9; }
html[data-theme="dark"] .so-item:hover,
html[data-theme="dark"] .so-item.is-focused { background: rgba(99,102,241,0.12); }
html[data-theme="dark"] .so-item-name { color: #f1f5f9; }
html[data-theme="dark"] .readonly-field.filled {
    background: rgba(15,118,110,0.1) !important;
    color: #a7f3d0 !important;
}
</style>

<!-- Product data embedded as JSON so JS can reliably access all fields -->
<script id="stockoutItemsJson" type="application/json">
<?= json_encode(array_values(array_map(fn($item) => [
    'id'    => (int) $item['product_id'],
    'name'  => $item['product'],
    'unit'  => $item['unit_name'] ?? '',
    'desc'  => $item['product_description'] ?? $item['description'] ?? '',
    'stock' => (int) $item['current_stock'],
], $items))) ?>
</script>

<script>
// ── Custom Searchable Dropdown ────────────────────────────────────────────
(function () {
    'use strict';

    // ── DOM refs ──────────────────────────────────────────────────────────
    const picker      = document.getElementById('soPicker');
    const pickerBtn   = document.getElementById('soPickerBtn');
    const pickerText  = document.getElementById('soPickerText');
    const panel       = document.getElementById('soPanel');
    const searchInput = document.getElementById('soSearchInput');
    const list        = document.getElementById('soList');
    const hiddenInput = document.getElementById('product_id_hidden');
    const submitBtn   = document.getElementById('stockoutSubmitBtn');
    const unitInput   = document.getElementById('unit');
    const descInput   = document.getElementById('description');
    const qtyInput    = document.getElementById('quantity');
    const errorDiv    = document.getElementById('barcodeError');
    const form        = document.getElementById('stockoutManualForm');

    // ── Data ──────────────────────────────────────────────────────────────
    const ITEMS = JSON.parse(document.getElementById('stockoutItemsJson').textContent);
    let selectedId  = null;
    let focusedIdx  = -1;
    let visibleItems = [];

    // ── Helpers ───────────────────────────────────────────────────────────
    function esc(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function initials(name) {
        return name.trim().split(/\s+/).slice(0, 2).map(w => w[0]).join('').toUpperCase();
    }

    function stockClass(stock) {
        if (stock === 0) return 'zero';
        if (stock <= 5)  return 'low';
        return '';
    }

    function highlight(text, query) {
        if (!query) return esc(text);
        const idx = text.toLowerCase().indexOf(query.toLowerCase());
        if (idx < 0) return esc(text);
        return esc(text.slice(0, idx))
             + '<mark style="background:#fef08a;border-radius:2px;padding:0 1px;">'
             + esc(text.slice(idx, idx + query.length))
             + '</mark>'
             + esc(text.slice(idx + query.length));
    }

    // ── Render list ───────────────────────────────────────────────────────
    function render(query = '') {
        const q = query.trim().toLowerCase();
        visibleItems = q
            ? ITEMS.filter(i => i.name.toLowerCase().includes(q))
            : ITEMS;
        focusedIdx = -1;

        list.innerHTML = '';
        visibleItems.forEach((item, idx) => {
            const li = document.createElement('li');
            li.className = 'so-item';
            li.setAttribute('role', 'option');
            li.dataset.idx = idx;

            const badgeCls = stockClass(item.stock);
            const metaParts = [item.unit, item.desc].filter(Boolean);

            li.innerHTML = `
                <div class="so-item-icon">${esc(initials(item.name))}</div>
                <div class="so-item-body">
                    <div class="so-item-name">${highlight(item.name, q)}</div>
                    ${metaParts.length ? `<div class="so-item-meta">${metaParts.map(esc).join(' · ')}</div>` : ''}
                </div>
                <span class="so-stock-badge ${badgeCls}">${item.stock} left</span>
            `;

            li.addEventListener('mousedown', e => {
                // Use mousedown so it fires before blur closes the panel
                e.preventDefault();
                selectItem(item);
                close();
            });

            list.appendChild(li);
        });
    }

    // ── Open / Close ──────────────────────────────────────────────────────
    function open() {
        picker.classList.add('is-open');
        pickerBtn.setAttribute('aria-expanded', 'true');
        render(searchInput.value);
        searchInput.focus();
    }

    function close() {
        picker.classList.remove('is-open');
        pickerBtn.setAttribute('aria-expanded', 'false');
        focusedIdx = -1;
    }

    function toggle() {
        picker.classList.contains('is-open') ? close() : open();
    }

    // ── Select an item ────────────────────────────────────────────────────
    function selectItem(item) {
        selectedId = item.id;
        hiddenInput.value = item.id;

        pickerBtn.classList.add('has-value');
        pickerText.textContent = item.name;

        unitInput.value = item.unit;
        descInput.value = item.desc;
        qtyInput.max    = item.stock;

        unitInput.classList.toggle('filled', !!item.unit);
        descInput.classList.toggle('filled', !!item.desc);

        submitBtn.disabled = false;
        errorDiv.style.display = 'none';
        searchInput.value = '';
    }

    function clearSelection() {
        selectedId = null;
        hiddenInput.value = '';

        pickerBtn.classList.remove('has-value');
        pickerText.textContent = 'Select product…';

        unitInput.value = '';
        descInput.value = '';
        qtyInput.max    = '';

        unitInput.classList.remove('filled');
        descInput.classList.remove('filled');

        submitBtn.disabled = true;
    }

    // ── Keyboard navigation inside list ──────────────────────────────────
    function moveFocus(dir) {
        const items = list.querySelectorAll('.so-item');
        if (!items.length) return;

        items[focusedIdx]?.classList.remove('is-focused');
        focusedIdx = Math.max(0, Math.min(focusedIdx + dir, items.length - 1));
        const target = items[focusedIdx];
        target.classList.add('is-focused');
        target.scrollIntoView({ block: 'nearest' });
    }

    // ── Events ────────────────────────────────────────────────────────────
    pickerBtn.addEventListener('click', toggle);

    searchInput.addEventListener('input', () => {
        render(searchInput.value);
    });

    searchInput.addEventListener('keydown', async e => {
        if (e.key === 'ArrowDown')  { e.preventDefault(); moveFocus(1); return; }
        if (e.key === 'ArrowUp')    { e.preventDefault(); moveFocus(-1); return; }
        if (e.key === 'Escape')     { close(); pickerBtn.focus(); return; }

        if (e.key === 'Enter') {
            e.preventDefault();
            const focused = list.querySelector('.so-item.is-focused');
            if (focused) {
                const idx = parseInt(focused.dataset.idx);
                selectItem(visibleItems[idx]);
                close();
                return;
            }
            // No item focused — treat typed value as a barcode
            const val = searchInput.value.trim();
            if (val) await doBarcodeLookup(val);
        }
    });

    // Close when clicking outside
    document.addEventListener('click', e => {
        if (!picker.contains(e.target)) close();
    });

    // ── Barcode lookup ────────────────────────────────────────────────────
    async function doBarcodeLookup(value) {
        const lookupUrl = searchInput.dataset.lookupUrl;
        errorDiv.style.display = 'none';

        try {
            const res  = await fetch(`${lookupUrl}?value=${encodeURIComponent(value)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();

            if (!res.ok) {
                showError(data.error ?? 'Barcode not found.');
            } else if (data.product_id) {
                const item = ITEMS.find(i => i.id === data.product_id);
                if (item) {
                    selectItem(item);
                    close();
                    if (data.current_qty) qtyInput.max = data.current_qty;
                } else {
                    showError('Product not found in available stock.');
                }
            } else {
                showError('Barcode not recognised.');
            }
        } catch {
            showError('Network error. Please try again.');
        }
    }

    function showError(msg) {
        errorDiv.textContent = msg;
        errorDiv.style.display = '';
    }

    // ── Submit guard ──────────────────────────────────────────────────────
    form.addEventListener('submit', e => {
        if (!selectedId) {
            e.preventDefault();
            pickerBtn.focus();
            pickerBtn.style.borderColor = '#ef4444';
            pickerBtn.style.boxShadow   = '0 0 0 3px rgba(239,68,68,0.15)';
            setTimeout(() => {
                pickerBtn.style.borderColor = '';
                pickerBtn.style.boxShadow   = '';
            }, 1400);
        }
    });
})();

function filterStockoutTable() {
    const query = document.getElementById('stockoutSearch').value.toLowerCase();
    const rows  = document.querySelectorAll('#stockoutItemsTable tr');
    rows.forEach((row, i) => {
        if (i === 0) return;
        row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
    });
}
</script>

<?= $this->endSection() ?>
