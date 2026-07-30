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
    'currentUserId' => (int) (session('user')['id'] ?? 0),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
  
<div id="settingsPage" class="page-shell settings-page">
    <div class="page-header">
        <div>
            <p class="page-eyebrow">Settings</p>
            <h1>Others Management</h1>
            <p class="page-subtitle">Maintain references, units, item types, offices, roles, and related records in a flatter management view.</p>
        </div>
    </div>

    <?php if ($levelId >= 2 && $levelId <= 3): ?>
    <!-- ── Backup Section ── -->
    <div class="section-card settings-section-card backup-section" id="backup-section-card">
        <button type="button" class="section-header settings-section-header active" onclick="toggleSection('section-backup', this)">
            <h2> Data Backup &amp; Restore &#9662;</h2>
        </button>
        <div class="section-body settings-section-body is-open" id="section-backup">

            <!-- Backup settings (level 3 only) -->
            <?php if ($levelId >= 3): ?>
            <div class="backup-dir-row" id="backup-dir-row">
                <label class="backup-dir-label">Backup Settings</label>

                <div class="backup-dir-controls" style="flex-wrap:wrap; gap:12px;">
                    <!-- Drive 1 directory -->
                    <div style="display:flex;flex-direction:column;gap:4px;flex:2;min-width:200px;">
                        <label for="backupDirInput" style="font-size:11px;font-weight:600;color:#64748b;letter-spacing:.06em;">
                            Storage Directory — Drive 1
                        </label>
                        <input type="text" id="backupDirInput" class="search-input backup-dir-input"
                               placeholder="e.g. writable/backups/" value="" autocomplete="off">
                    </div>

                    <!-- Drive 2 directory (optional mirror) -->
                    <div style="display:flex;flex-direction:column;gap:4px;flex:2;min-width:200px;">
                        <label for="backupDirInput2" style="font-size:11px;font-weight:600;color:#16a34a;letter-spacing:.06em;">
                            Storage Directory — Drive 2 <span style="font-weight:400;color:#94a3b8;">(optional mirror)</span>
                        </label>
                        <input type="text" id="backupDirInput2" class="search-input backup-dir-input"
                               placeholder="e.g. D:\Backups\BSU\ (leave blank to skip)" value="" autocomplete="off">
                    </div>

                    <!-- Auto-backup interval -->
                    <div style="display:flex;flex-direction:column;gap:4px;flex:1;min-width:160px;">
                        <label for="backupIntervalSelect" style="font-size:11px;font-weight:600;color:#64748b;letter-spacing:.06em;">Auto-Backup Every</label>
                        <select id="backupIntervalSelect" class="search-input" style="height:44px;padding:0 12px;">
                            <option value="1">Every 1 hour</option>
                            <option value="2">Every 2 hours</option>
                            <option value="4">Every 4 hours</option>
                            <option value="6">Every 6 hours</option>
                            <option value="8">Every 8 hours</option>
                            <option value="12">Every 12 hours</option>
                            <option value="24" selected>Once a day (24 h)</option>
                            <option value="48">Every 2 days</option>
                            <option value="72">Every 3 days</option>
                            <option value="168">Once a week</option>
                            <option value="720">Once a month</option>
                            <option value="0">Manual only</option>
                        </select>
                    </div>

                    <!-- Auto-backup time of day -->
                    <div style="display:flex;flex-direction:column;gap:4px;min-width:130px;">
                        <label for="backupTimeInput" style="font-size:11px;font-weight:600;color:#64748b;letter-spacing:.06em;">At Time</label>
                        <input type="time" id="backupTimeInput" class="search-input"
                               value="00:00"
                               style="height:44px;padding:0 12px;min-width:110px;"
                               title="Time of day to run the auto-backup (24-hour format)">
                    </div>

                    <div style="display:flex;align-items:flex-end;">
                        <button type="button" class="btn-add" onclick="saveBackupSettings()">Save Settings</button>
                    </div>
                </div>

                <p class="backup-dir-hint">Auto-backup runs silently in the background when you log in (at most once per interval). Set to <em>Manual only</em> to disable auto-backup.</p>
            </div>
            <?php endif; ?>

            <!-- Action bar -->
            <div class="backup-action-bar">
                <button type="button" class="btn-primary backup-now-btn" id="backupNowBtn" onclick="triggerBackup()">
                    ⬆ Backup Now
                </button>
                <span class="backup-status-text" id="backupStatusText"></span>

                <!-- Restore from file -->
                <div class="backup-restore-file">
                    <label class="backup-restore-label">Restore from File</label>
                    <input type="file" id="restoreFileInput" accept=".sql" style="display:none" onchange="restoreFromFile(this)">
                    <button type="button" class="btn-secondary" onclick="document.getElementById('restoreFileInput').click()">
                        📂 Choose .sql File
                    </button>
                </div>
            </div>

            <!-- Backup list -->
            <div class="backup-list-wrap" style="overflow-x:auto; margin-top:14px;">
                <table class="data-table" id="backup-list-table">
                    <tr>
                        <th>Slot</th>
                        <th>Filename</th>
                        <th>Date Created</th>
                        <th>Size</th>
                        <th>Office</th>
                        <th>Created By</th>
                        <th>Action</th>
                    </tr>
                    <tr id="backup-loading-row">
                        <td colspan="7" style="text-align:center;padding:24px;color:#94a3b8;">Loading backups…</td>
                    </tr>
                </table>
            </div>

        </div>
    </div>
    <?php endif; ?>

    <!-- ── Pending Users Section (Level 3+ only) ── -->
    <?php if ($levelId >= 3 && ! empty($pendingUsers)): ?>
        <div class="section-card settings-section-card pending-section">
            <button type="button" class="section-header settings-section-header active" onclick="toggleSection('section-pending', this)">
                <h2>Pending Applicants (<?= count($pendingUsers) ?>) &#9662;</h2>
            </button>

            <div class="section-body settings-section-body is-open" id="section-pending">
                <table class="data-table" id="table-pending">
                    <tr>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Office</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach ($pendingUsers as $pUser): ?>
                        <tr>
                            <td><?= esc($pUser['name'] ?? '') ?></td>
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
                        // Skip manager's own account row
                        if ($type === 'users' && $levelId < 4) {
                            $currentUserId = (int) (session('user')['id'] ?? 0);
                            if ($currentUserId > 0 && (int) ($row[$definition['pk']] ?? 0) === $currentUserId) {
                                continue;
                            }
                        }
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
