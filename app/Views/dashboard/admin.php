<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php $levelId = (int) ($levelId ?? session('user')['level_id'] ?? 0); ?>

<script type="application/json" id="settingsConfigJson"><?= json_encode([
    'definitions'  => $definitions,
    'userOffices'  => $userOffices ?? [],
    'levels'       => $levels ?? [],
    'fetchBase'    => site_url('settings/fetch'),
    'saveBase'     => site_url('settings/save'),
    'deleteBase'   => site_url('settings/delete'),
    'activateBase' => site_url('settings/activate'),
    'deactivateBase' => site_url('settings/deactivate'),
    'levelId'      => $levelId,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

<div id="settingsPage" class="dashboard-shell">

    <!-- ── Hero Section ── -->
    <section class="dashboard-hero">
        <div>
            <p class="dashboard-eyebrow">Administration</p>
            <h1>User Management</h1>
            <p class="dashboard-subtitle">Manage users, offices, and account activations from this central hub.</p>
        </div>
        <div class="dashboard-hero-note">
            <span>Pending</span>
            <strong class="text-white" style="color:white"><?= count($pendingUsers ?? []) ?></strong>
            <small>accounts awaiting activation</small>
        </div>
    </section>

    <!-- ── Summary Cards ── -->
    <section class="dashboard-summary">
        <article class="summary-card">
            <span>Total Users</span>
            <strong><?= count($records['users'] ?? []) ?></strong>
        </article>
        <article class="summary-card">
            <span>Pending Activation</span>
            <strong><?= count($pendingUsers ?? []) ?></strong>
        </article>
        <article class="summary-card">
            <span>User Offices</span>
            <strong><?= count($records['user_office_table'] ?? []) ?></strong>
        </article>
    </section>

    <!-- ── Pending Users Section ── -->
    <?php if (! empty($pendingUsers)): ?>
        <div class="section-card settings-section-card pending-section">
            <button type="button" class="section-header settings-section-header active" onclick="toggleSection('section-pending', this)">
                <h2>Pending Applicants (<?= count($pendingUsers) ?>) &#9662;</h2>
            </button>

            <div class="section-body settings-section-body is-open" id="section-pending">
                <table class="data-table" id="table-pending">
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Office</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach ($pendingUsers as $pUser): ?>
                        <tr>
                            <td><?= esc($pUser['username']) ?></td>
                            <td><?= esc($pUser['email'] ?? '') ?></td>
                            <td><?= esc($pUser['role']) ?></td>
                            <td><?= esc($pUser['user_office_name'] ?? 'N/A') ?></td>
                            <td>
                                <a class="action-btn activate-btn" href="#" onclick="activateUser(<?= (int) $pUser['user_id'] ?>); return false;">Activate</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- ── Settings Sections ── -->
    <?php $sectionIndex = 0; ?>
    <?php foreach ($definitions as $type => $definition): ?>
        <?php
        $sectionTitle = match ($type) {
            'users'             => 'Users',
            'user_office_table' => 'User Office',
            'entity_table'      => 'Entities',
            'unit_table'        => 'Units',
            'reference_table'   => 'References',
            'type_of_product'   => 'Product Types',
            'office_table'      => 'Offices',
            default             => ucwords(str_replace('_', ' ', $type)),
        };

        $columns = array_keys($definition['labels']);
        if ($type === 'users') {
            $columns[] = 'user_office_name';
            $columns[] = 'activity_status';
        }
        $isOpen = $sectionIndex === 0 && empty($pendingUsers);
        $showAddButton = ($type !== 'users');
        ?>

        <div class="section-card settings-section-card">
            <button type="button" class="section-header settings-section-header <?= $isOpen ? 'active' : '' ?>" onclick="toggleSection('section-<?= esc($type) ?>', this)">
                <h2 style="color:white;"><?= esc($sectionTitle) ?> &#9662;</h2>
            </button>

            <div class="section-body settings-section-body <?= $isOpen ? 'is-open' : '' ?>" id="section-<?= esc($type) ?>">
                <input type="text" class="search-input settings-search-input" onkeyup="searchTable(this, 'table-<?= esc($type) ?>')" placeholder="Search <?= esc($sectionTitle) ?>">
                <?php if ($showAddButton): ?>
                    <button type="button" class="btn-add" onclick="openModal('<?= esc($type) ?>')">+ Add <?= esc($sectionTitle) ?></button>
                <?php endif; ?>

                <table class="data-table" id="table-<?= esc($type) ?>">
                    <tr>
                        <?php foreach ($columns as $column): ?>
                            <th><?= esc($column === 'activity_status' ? 'Status' : ($column === 'user_office_name' ? 'Office' : ($definition['labels'][$column] ?? ucwords(str_replace('_', ' ', $column))))) ?></th>
                        <?php endforeach; ?>
                        <th>Action</th>
                    </tr>

                    <?php foreach ($records[$type] as $row): ?>
                        <tr>
                            <?php foreach ($columns as $column): ?>
                                <td>
                                    <?php if ($column === 'activity_status'): ?>
                                        <span class="status-badge status-<?= strtolower(esc($row[$column] ?? 'unknown')) ?>"><?= esc((string) ($row[$column] ?? '')) ?></span>
                                    <?php else: ?>
                                        <?= esc((string) ($row[$column] ?? '')) ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                            <td>
                                <?php if ($type === 'users'): ?>
                                    <?php $actId = (int) ($row['user_activity_id'] ?? 1); ?>
                                    <?php if ($actId === 1): ?>
                                        <a class="action-btn deactivate-btn" href="#" onclick="deactivateUser(<?= (int) $row[$definition['pk']] ?>); return false;">Deactivate</a>
                                    <?php elseif ($actId === 2 || $actId === 3): ?>
                                        <a class="action-btn activate-btn" href="#" onclick="activateUser(<?= (int) $row[$definition['pk']] ?>); return false;">Activate</a>
                                    <?php endif; ?>
                                    <a class="action-btn delete-btn" href="#" onclick="deleteRecord('<?= esc($type) ?>', <?= (int) $row[$definition['pk']] ?>); return false;">Delete</a>
                                <?php else: ?>
                                    <a class="action-btn edit-btn" href="#" onclick="openModal('<?= esc($type) ?>', <?= (int) $row[$definition['pk']] ?>); return false;">Edit</a>
                                    <a class="action-btn delete-btn" href="#" onclick="deleteRecord('<?= esc($type) ?>', <?= (int) $row[$definition['pk']] ?>); return false;">Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        <?php $sectionIndex++; ?>
    <?php endforeach; ?>

    <!-- ── Modal ── -->
    <div id="modal" class="modal settings-modal">
        <div class="modal-content settings-modal-content">
            <button type="button" class="close" onclick="closeModal()">&times;</button>
            <h2 id="modalTitle"></h2>
            <form id="modalForm">
                <?= csrf_field() ?>
                <input type="hidden" id="recordId" name="id">
                <input type="hidden" id="recordType" name="type">
                <div id="fields"></div>
                <button type="submit">Save</button>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
