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

        <label>Entity:</label>
        <div class="hover-dropdown">
            <input type="text" name="entity_name" class="hover-input" autocomplete="off" placeholder="Select or type entity" value="<?= esc(old('entity_name', $product['entity_name'] ?? '')) ?>" required>
            <div class="hover-dropdown-content">
                <?php foreach ($entities as $entity): ?>
                    <div class="hover-option"><?= esc($entity['entity']) ?></div>
                <?php endforeach; ?>
            </div>
        </div>

        <label>Unit:</label>
        <div class="hover-dropdown">
            <input type="text" name="unit_name" class="hover-input" autocomplete="off" placeholder="Select or type unit" value="<?= esc(old('unit_name', $product['unit_name'] ?? '')) ?>" required>
            <div class="hover-dropdown-content">
                <?php foreach ($units as $unit): ?>
                    <div class="hover-option"><?= esc($unit['unit']) ?></div>
                <?php endforeach; ?>
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

        <button type="submit"><?= $product['product_id'] ? 'Update' : 'Save Product' ?></button>
    </form>
</div>

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
