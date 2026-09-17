<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-shell narrow-shell">
    <div class="page-header">
        <div>
            <p class="page-eyebrow">Products</p>
            <h1><?= esc($title) ?></h1>
            <p class="page-subtitle">Keep product setup tidy with a flatter, easier-to-scan form.</p>
        </div>
    </div>

    <form method="post" class="stock-form flat-form-card" id="product-form">
        <?= csrf_field() ?>
        <?php
            // Track original values so JS can detect changes (edit mode only)
            $isEdit      = !empty($product['product_id']);
            $origName    = $isEdit ? esc($product['product']) : '';
            $origDesc    = $isEdit ? esc($product['product_description']) : '';
        ?>
        <?php if ($isEdit): ?>
            <!-- Hidden fields consumed by the controller to know intent -->
            <input type="hidden" name="product_action" id="product_action" value="existing">
            <input type="hidden" id="orig_product"     value="<?= $origName ?>">
            <input type="hidden" id="orig_description" value="<?= $origDesc ?>">
        <?php endif; ?>

        <label>Product No:</label>
        <input type="number" name="product_no" min="1" value="<?= esc((string) old('product_no', $product['product_no'])) ?>" required>

        <label>Product Name:</label>
        <input type="text" name="product" id="input_product" value="<?= esc(old('product', $product['product'])) ?>" required>

        <label>Description:</label>
        <textarea name="product_description" id="input_description"><?= esc(old('product_description', $product['product_description'])) ?></textarea>

        <label>Re-order Point:</label>
        <input type="number" name="product_reorder_point" min="0" value="<?= esc((string) old('product_reorder_point', $product['product_reorder_point'] ?? 10)) ?>" required>

        <div class="flat-section-divider">
            <span>Expiration Alert Settings</span>
        </div>

        <div class="expiry-threshold-row">
            <div class="expiry-threshold-col">
                <label>⚠️ Warning — days before expiry</label>
                <input
                    type="number"
                    name="expiry_warning_days"
                    min="1" max="365"
                    value="<?= (int) old('expiry_warning_days', $product['expiry_warning_days'] ?? 30) ?>"
                    required
                >
                <small class="field-hint">Shows this product in the "Expiring Soon" dashboard card</small>
            </div>
            <div class="expiry-threshold-col">
                <label style="color:#dc2626;">🔴 Danger — highlighted red below</label>
                <input
                    type="number"
                    name="expiry_danger_days"
                    min="1" max="365"
                    value="<?= (int) old('expiry_danger_days', $product['expiry_danger_days'] ?? 7) ?>"
                    required
                >
                <small class="field-hint">Items at or below this many days are highlighted red</small>
            </div>
        </div>

        <label>Entity:</label>
        <div class="hover-dropdown">
            <input type="text" name="entity_name" class="hover-input" autocomplete="off" placeholder="Select or type entity" value="<?= esc(old('entity_name', $product['entity_name'] ?? '')) ?>" required>
            <div class="hover-dropdown-content">
                <?php foreach ($entities as $entity): ?>
                    <div class="hover-option"><?= esc($entity['entity']) ?></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="measurement-unit-row">
            <div class="measurement-unit-col">
                <label>Measurement:</label>
                <input type="text" name="measurement" placeholder="e.g. 500ml, 1kg, 250g" value="<?= esc(old('measurement', $product['measurement'] ?? '')) ?>">
            </div>
            <div class="measurement-unit-col">
                <label>Unit:</label>
                <div class="hover-dropdown">
                    <input type="text" name="unit_name" class="hover-input" autocomplete="off" placeholder="Select or type unit" value="<?= esc(old('unit_name', $product['unit_name'] ?? '')) ?>" required>
                    <div class="hover-dropdown-content">
                        <?php foreach ($units as $unit): ?>
                            <div class="hover-option"><?= esc($unit['unit']) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <label>Product Type:</label>
        <div class="hover-dropdown">
            <input type="text" name="type_name" class="hover-input" autocomplete="off" placeholder="Select or type product type" value="<?= esc(old('type_name', $product['type_name'] ?? '')) ?>" required>
            <div class="hover-dropdown-content">
                <?php foreach ($productTypes as $pType): ?>
                    <div class="hover-option"><?= esc($pType['type']) ?></div>
                <?php endforeach; ?>
            </div>
        </div>

        <style>
        .flat-section-divider {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0 4px;
        }
        .flat-section-divider::before,
        .flat-section-divider::after {
            content: '';
            flex: 1;
            border-top: 1px solid var(--border-color, #e5e7eb);
        }
        .flat-section-divider span {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            color: var(--text-muted, #94a3b8);
            text-transform: uppercase;
            white-space: nowrap;
        }
        .expiry-threshold-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .expiry-threshold-col {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .expiry-threshold-col label {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 0;
        }
        .expiry-threshold-col input {
            width: 100%;
        }
        .field-hint {
            font-size: 11px;
            color: var(--text-muted, #94a3b8);
            line-height: 1.4;
        }
        @media (max-width: 500px) {
            .expiry-threshold-row { grid-template-columns: 1fr; }
            .measurement-unit-row { grid-template-columns: 1fr; }
        }
        .measurement-unit-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .measurement-unit-col {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .measurement-unit-col label {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 0;
        }
        .measurement-unit-col input {
            width: 100%;
        }
        </style>

        <button type="submit"><?= $product['product_id'] ? 'Update' : 'Save Product' ?></button>
    </form>
</div>

<div id="product-confirm-overlay" class="product-confirm-overlay" style="display:none;"></div>
<div id="product-confirm-modal" class="product-confirm-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="productConfirmTitle">
    <h2 id="productConfirmTitle">Confirm Product Details</h2>
    <p>Review the product details before saving.</p>
    <div id="product-confirm-details" class="product-confirm-details"></div>
    <div class="product-confirm-actions">
        <button type="button" id="product-confirm-cancel" class="product-confirm-cancel">Cancel</button>
        <button type="button" id="product-confirm-save" class="product-confirm-save"><?= $product['product_id'] ? 'Confirm Update' : 'Confirm Save' ?></button>
    </div>
</div>

<style>
.product-confirm-overlay {
    position: fixed;
    inset: 0;
    z-index: 8990;
    background: rgba(10, 30, 30, .55);
    backdrop-filter: blur(4px);
}
.product-confirm-modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 8991;
    width: min(520px, 92vw);
    background: var(--card-bg, #fff);
    color: var(--text-primary, #0f172a);
    border-radius: 16px;
    box-shadow: 0 28px 70px rgba(15, 61, 62, .24);
    border: 1px solid rgba(15, 118, 110, .18);
    padding: 28px 28px 24px;
}
.product-confirm-modal h2 {
    margin: 0 0 8px;
    font-size: 1.25rem;
    color: var(--text-primary, #0f3d3e);
}
.product-confirm-modal p {
    margin: 0 0 16px;
    color: var(--text-secondary, #475569);
    font-size: 14px;
}
.product-confirm-details {
    margin: 14px 0 20px;
    border: 1px solid var(--border-color, #e5e7eb);
    border-radius: 10px;
    background: var(--sidebar-bg, #f8fafc);
    overflow: hidden;
}
.product-confirm-row {
    display: flex;
    justify-content: space-between;
    gap: 18px;
    padding: 10px 12px;
    font-size: 13px;
}
.product-confirm-row + .product-confirm-row {
    border-top: 1px solid rgba(148, 163, 184, .24);
}
.product-confirm-label {
    color: var(--text-muted, #64748b);
    font-weight: 700;
}
.product-confirm-value {
    color: var(--text-primary, #111827);
    font-weight: 700;
    text-align: right;
    overflow-wrap: anywhere;
}
.product-confirm-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
.product-confirm-actions button {
    border-radius: 8px;
    padding: 10px 16px;
    font-weight: 700;
    cursor: pointer;
}
.product-confirm-cancel {
    border: 1px solid var(--border-color, #d1d5db);
    background: transparent;
    color: var(--text-primary, #334155);
}
.product-confirm-save {
    border: 0;
    background: #0f766e;
    color: #fff;
}
</style>

<script>
(function () {
    var form = document.getElementById('product-form');
    var overlay = document.getElementById('product-confirm-overlay');
    var modal = document.getElementById('product-confirm-modal');
    var details = document.getElementById('product-confirm-details');
    var cancelBtn = document.getElementById('product-confirm-cancel');
    var saveBtn = document.getElementById('product-confirm-save');
    var confirmedDetails = false;

    if (!form || !overlay || !modal || !details || !cancelBtn || !saveBtn) return;

    function fieldValue(name) {
        return (form.querySelector('[name="' + name + '"]') || {}).value || '';
    }

    function addRow(rows, label, value) {
        var clean = String(value || '').trim();
        if (clean !== '') rows.push({ label: label, value: clean });
    }

    function renderDetails() {
        var rows = [];
        addRow(rows, 'Product No', fieldValue('product_no'));
        addRow(rows, 'Product Name', fieldValue('product'));
        addRow(rows, 'Description', fieldValue('product_description'));
        addRow(rows, 'Re-order Point', fieldValue('product_reorder_point'));
        addRow(rows, 'Warning Days', fieldValue('expiry_warning_days'));
        addRow(rows, 'Danger Days', fieldValue('expiry_danger_days'));
        addRow(rows, 'Entity', fieldValue('entity_name'));
        addRow(rows, 'Measurement', fieldValue('measurement'));
        addRow(rows, 'Unit', fieldValue('unit_name'));
        addRow(rows, 'Product Type', fieldValue('type_name'));

        details.innerHTML = '';
        rows.forEach(function (row) {
            var item = document.createElement('div');
            item.className = 'product-confirm-row';

            var label = document.createElement('span');
            label.className = 'product-confirm-label';
            label.textContent = row.label;

            var value = document.createElement('span');
            value.className = 'product-confirm-value';
            value.textContent = row.value;

            item.append(label, value);
            details.appendChild(item);
        });
    }

    function showModal() {
        renderDetails();
        overlay.style.display = 'block';
        modal.style.display = 'block';
    }

    function hideModal() {
        overlay.style.display = 'none';
        modal.style.display = 'none';
    }

    form.addEventListener('submit', function (e) {
        if (confirmedDetails) return;
        e.preventDefault();
        showModal();
    });

    form.addEventListener('input', function () {
        confirmedDetails = false;
    });

    cancelBtn.addEventListener('click', hideModal);
    overlay.addEventListener('click', hideModal);

    saveBtn.addEventListener('click', function () {
        confirmedDetails = true;
        hideModal();
        form.requestSubmit();
    });
})();
</script>

<?php if (!empty($product['product_id'])): /* modal only needed for edits */ ?>
<!-- ── "New or Existing Product?" Modal ─────────────────────────────────── -->
<div id="product-type-overlay" style="
    display:none;
    position:fixed;inset:0;z-index:9000;
    background:rgba(10,30,30,.55);
    backdrop-filter:blur(4px);
    align-items:center;justify-content:center;
"></div>

<div id="product-type-modal" style="
    display:none;
    position:fixed;
    top:50%;left:50%;
    transform:translate(-50%,-50%);
    z-index:9001;
    background:#fff;
    border-radius:24px;
    box-shadow:0 32px 70px rgba(15,61,62,.22);
    border:1px solid rgba(15,118,110,.18);
    width:min(520px,92vw);
    padding:36px 32px 28px;
    animation:modalIn .22s cubic-bezier(.34,1.56,.64,1);
">
    <style>
        @keyframes modalIn {
            from { opacity:0; transform:translate(-50%,-46%) scale(.95); }
            to   { opacity:1; transform:translate(-50%,-50%) scale(1);  }
        }
        #product-type-modal h2 {
            margin:0 0 8px;
            font-size:1.35rem;
            color:#0f3d3e;
        }
        #product-type-modal p {
            margin:0 0 24px;
            font-size:14px;
            color:#475569;
            line-height:1.6;
        }
        .ptype-btn-row {
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:12px;
        }
        .ptype-btn {
            padding:16px 14px;
            border-radius:16px;
            border:2px solid transparent;
            cursor:pointer;
            font-size:14px;
            font-weight:600;
            text-align:center;
            transition:all .18s;
            line-height:1.45;
        }
        .ptype-btn-new {
            background:linear-gradient(135deg,#0f3d3e,#0f766e);
            color:#fff;
            box-shadow:0 8px 22px rgba(15,118,110,.28);
        }
        .ptype-btn-new:hover {
            transform:translateY(-2px);
            box-shadow:0 12px 28px rgba(15,118,110,.38);
        }
        .ptype-btn-existing {
            background:#f8fafc;
            color:#0f3d3e;
            border-color:#0f766e;
        }
        .ptype-btn-existing:hover {
            background:#ccfbf1;
            border-color:#0f766e;
        }
        .ptype-icon { display:block; font-size:1.6rem; margin-bottom:6px; }
        .ptype-cancel {
            margin-top:16px;
            width:100%;
            padding:10px;
            border:none;
            border-radius:10px;
            background:transparent;
            color:#94a3b8;
            cursor:pointer;
            font-size:13px;
        }
        .ptype-cancel:hover { color:#ef4444; }
    </style>

    <h2>⚡ Name or Description Changed</h2>
    <p>
        You updated the product <strong>name</strong> or <strong>description</strong>.<br>
        Is this the <em>same product</em> (keep all existing transactions), or a
        <em>brand-new product</em> (same product no., fresh transaction history)?
    </p>

    <div class="ptype-btn-row">
        <button type="button" class="ptype-btn ptype-btn-new" id="modal-choose-new">
            <span class="ptype-icon">🆕</span>
            New Product<br>
            <small style="font-weight:400;font-size:12px;opacity:.85;">Same prod no · No transactions</small>
        </button>
        <button type="button" class="ptype-btn ptype-btn-existing" id="modal-choose-existing">
            <span class="ptype-icon">📦</span>
            Same Product<br>
            <small style="font-weight:400;font-size:12px;color:#64748b;">Keep all transactions</small>
        </button>
    </div>
    <button type="button" class="ptype-cancel" id="modal-cancel">Cancel — go back to editing</button>
</div>

<script>
(function () {
    var form      = document.getElementById('product-form');
    var overlay   = document.getElementById('product-type-overlay');
    var modal     = document.getElementById('product-type-modal');
    var actionIn  = document.getElementById('product_action');
    var origName  = (document.getElementById('orig_product')     || {}).value || '';
    var origDesc  = (document.getElementById('orig_description') || {}).value || '';

    // Guard: only run on edit pages (modal elements exist)
    if (!form || !overlay || !modal || !actionIn) return;

    var pendingSubmit = false;

    function showModal() {
        overlay.style.display = 'flex';
        modal.style.display   = 'block';
    }

    function hideModal() {
        overlay.style.display = 'none';
        modal.style.display   = 'none';
    }

    function doSubmit(action) {
        actionIn.value  = action;
        pendingSubmit   = true;
        hideModal();
        form.submit();
    }

    form.addEventListener('submit', function (e) {
        if (e.defaultPrevented) return;
        if (pendingSubmit) return; // already chosen — let it through

        var currentName = (document.getElementById('input_product')     || {}).value || '';
        var currentDesc = (document.getElementById('input_description') || {}).value || '';

        var nameChanged = currentName.trim() !== origName.trim();
        var descChanged = currentDesc.trim() !== origDesc.trim();

        if (nameChanged || descChanged) {
            e.preventDefault();
            showModal();
        }
        // If nothing changed, form submits normally (actionIn stays "existing")
    });

    document.getElementById('modal-choose-new').addEventListener('click', function () {
        doSubmit('new');
    });

    document.getElementById('modal-choose-existing').addEventListener('click', function () {
        doSubmit('existing');
    });

    document.getElementById('modal-cancel').addEventListener('click', hideModal);
    overlay.addEventListener('click', hideModal);
})();
</script>
<?php endif; ?>

<?= $this->endSection() ?>
