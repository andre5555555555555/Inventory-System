<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php $levelId = (int) ($levelId ?? session('user')['level_id'] ?? 0); ?>

<script type="application/json" id="settingsConfigJson"><?= json_encode([
    'definitions' => $definitions,
    'roles' => $roles ?? [],
    'offices' => $offices ?? [],
    'userOffices' => $userOffices ?? [],
    'levels' => $levels ?? [],
    'fetchBase' => site_url('settings/fetch'),
    'saveBase' => site_url('settings/save'),
    'deleteBase' => site_url('settings/delete'),
    'activateBase' => site_url('settings/activate'),
    'deactivateBase' => site_url('settings/deactivate'),
    'levelId' => $levelId,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
  
<div id="settingsPage" class="page-shell settings-page">
    <div class="page-header">
        <div>
            <p class="page-eyebrow">Settings</p>
            <h1>Others Management</h1>
            <p class="page-subtitle">Maintain references, units, item types, offices, roles, and related records in a flatter management view.</p>
        </div>
    </div>

    <!-- ── Pending Users Section (Level 3+ only) ── -->
    <?php if ($levelId >= 3 && ! empty($pendingUsers)): ?>
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

    <?php $sectionIndex = 0; ?>
    <?php foreach ($definitions as $type => $definition): ?>
        <?php
        $sectionTitle = match ($type) {
            'users' => 'Users',
            'entity' => 'Entity',
            'reference' => 'Reference',
            'unit' => 'Unit',
            'office' => 'Office',
            'item_type' => 'Item Type',
            'item_category' => 'Category',
            'roles' => 'Roles',
            'user_office' => 'User Office',
            default => ucwords(str_replace('_', ' ', $type)),
        };

        $columns = array_keys($definition['labels']);
        if ($type === 'users') {
            $columns[] = 'user_office_name';
            $columns[] = 'activity_status';
        }
        if ($type === 'roles') {
            $columns[] = 'access_level_name';
        }
        $isOpen = $sectionIndex === 0 && empty($pendingUsers);

        // Determine if add button should be shown
        $showAddButton = true;
        if ($type === 'users') {
            $showAddButton = false; // All users must register themselves
        }
        if ($type === 'roles' && $levelId < 3) {
            $showAddButton = false; // Only manager can add roles
        }
        ?>
  
        <div class="section-card settings-section-card">
            <button type="button" class="section-header settings-section-header <?= $isOpen ? 'active' : '' ?>" onclick="toggleSection('section-<?= esc($type) ?>', this)">
                <h2><?= esc($sectionTitle) ?> &#9662;</h2>
            </button>

            <div class="section-body settings-section-body <?= $isOpen ? 'is-open' : '' ?>" id="section-<?= esc($type) ?>">
                <input type="text" class="search-input settings-search-input" onkeyup="searchTable(this, 'table-<?= esc($type) ?>')" placeholder="Search <?= esc($sectionTitle) ?>">
                <?php if ($showAddButton): ?>
                    <button type="button" class="btn-add" onclick="openModal('<?= esc($type) ?>')">+ Add <?= esc($sectionTitle) ?></button>
                <?php endif; ?>

                <table class="data-table" id="table-<?= esc($type) ?>">
                    <tr>
                        <?php foreach ($columns as $column): ?>
                            <th><?= esc($column === 'office' ? 'Office' : ($column === 'activity_status' ? 'Status' : ($column === 'user_office_name' ? 'Office' : ($column === 'access_level_name' ? 'Level' : ($definition['labels'][$column] ?? ucwords(str_replace('_', ' ', $column))))))) ?></th>
                        <?php endforeach; ?>
                        <th>Action</th>
                    </tr>

                    <?php foreach ($records[$type] as $row): ?>
                        <?php
                            // Hide Technical Staff role (level_id=4) from roles list
                            if ($type === 'roles' && (int) ($row['level_id'] ?? 0) === 4) {
                                continue;
                            }
                        ?>
                        <tr>
                            <?php foreach ($columns as $column): ?>
                                <td>
                                    <?php if ($column === 'activity_status'): ?>
                                        <span class="status-badge status-<?= strtolower(esc($row[$column] ?? 'unknown')) ?>"><?= esc((string) ($row[$column] ?? '')) ?></span>
                                    <?php elseif ($column === 'level_id'): ?>
                                        Level <?= (int) ($row['level_id'] ?? 0) ?>
                                    <?php elseif ($column === 'access_level_name'): ?>
                                        Level <?= (int) ($row['level_id'] ?? 0) ?>
                                    <?php else: ?>
                                        <?= esc((string) ($row[$column] ?? '')) ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                            <td>
                                <?php if ($type === 'roles'): ?>
                                    <?php if ($levelId >= 3): ?>
                                        <a class="action-btn edit-btn" href="#" onclick="openModal('<?= esc($type) ?>', <?= (int) $row[$definition['pk']] ?>); return false;">Edit</a>
                                        <a class="action-btn delete-btn" href="#" onclick="deleteRecord('<?= esc($type) ?>', <?= (int) $row[$definition['pk']] ?>); return false;">Delete</a>
                                    <?php endif; ?>
                                <?php elseif ($type === 'users'): ?>
                                    <?php if ($levelId >= 3): ?>
                                        <?php $actId = (int) ($row['user_activity_id'] ?? 1); ?>
                                        <?php if ($actId === 1): ?>
                                            <a class="action-btn deactivate-btn" href="#" onclick="deactivateUser(<?= (int) $row[$definition['pk']] ?>); return false;">Deactivate</a>
                                        <?php elseif ($actId === 2 || $actId === 3): ?>
                                            <a class="action-btn activate-btn" href="#" onclick="activateUser(<?= (int) $row[$definition['pk']] ?>); return false;">Activate</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ($levelId >= 4): ?>
                                        <a class="action-btn delete-btn" href="#" onclick="deleteRecord('<?= esc($type) ?>', <?= (int) $row[$definition['pk']] ?>); return false;">Delete</a>
                                    <?php endif; ?>
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

    <div id="modal" class="modal settings-modal">
        <div class="modal-content settings-modal-content">
            <button style="color: #0f3d3e;" type="button" class="close" onclick="closeModal()">&times;</button>
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
