<?php

namespace App\Controllers;

use App\Models\BackupModel;
use CodeIgniter\HTTP\ResponseInterface;

class BackupController extends BaseController
{
    private function officeId(): int
    {
        return (int) (session('user')['user_office_id'] ?? 0);
    }

    private function userId(): int
    {
        return (int) (session('user')['id'] ?? 0);
    }

    private function levelId(): int
    {
        return (int) (session('user')['level_id'] ?? 0);
    }

    private function username(): string
    {
        return (string) (session('user')['username'] ?? '');
    }

    private function officeName(): string
    {
        $officeId = $this->officeId();
        if ($officeId <= 0) return 'Global';
        $row = db_connect()
            ->table('user_office_table')
            ->where('user_office_id', $officeId)
            ->get()
            ->getRowArray();
        return $row ? $row['user_office_name'] : 'Office #' . $officeId;
    }

    // ─────────────────────────────────────────────
    //  GET  settings/backup/list
    // ─────────────────────────────────────────────

    public function index(): ResponseInterface
    {
        $model    = new BackupModel();
        $officeId = $this->officeId();
        $config   = $model->getConfig();

        return $this->response->setJSON([
            'backups'                => $model->getBackups($officeId),
            'backup_dir'             => $config['backup_dir'] ?? '',
            'backup_dir_2'           => $config['backup_dir_2'] ?? '',
            'backup_interval_hours'  => (int) ($config['backup_interval_hours'] ?? 24),
            'backup_time'            => (string) ($config['backup_time'] ?? '00:00'),
        ]);
    }

    // ─────────────────────────────────────────────
    //  POST settings/backup/run
    // ─────────────────────────────────────────────

    public function run(): ResponseInterface
    {
        $levelId = $this->levelId();
        // Level 2 (custodian) and level 3 (manager) can backup
        if ($levelId < 2 || $levelId > 3) {
            return $this->response->setStatusCode(403)->setJSON(['message' => 'Access denied.']);
        }

        $model    = new BackupModel();
        $officeId = $this->officeId();

        $result = $model->createBackup(
            $officeId,
            $this->userId(),
            $this->officeName(),
            $this->username()
        );

        $status = $result['ok'] ? 200 : 500;
        return $this->response->setStatusCode($status)->setJSON($result);
    }

    // ─────────────────────────────────────────────
    //  POST settings/backup/auto  (called on login, AJAX)
    // ─────────────────────────────────────────────

    public function autoBackup(): ResponseInterface
    {
        $levelId = $this->levelId();
        if ($levelId < 2 || $levelId > 3) {
            return $this->response->setStatusCode(204)->setJSON(['skipped' => true]);
        }

        $model    = new BackupModel();
        $officeId = $this->officeId();

        if (! $model->needsAutoBackup($officeId)) {
            return $this->response->setStatusCode(204)->setJSON(['skipped' => true, 'message' => 'Backup already done today.']);
        }

        $result = $model->createBackup(
            $officeId,
            $this->userId(),
            $this->officeName(),
            $this->username()
        );

        return $this->response->setJSON($result);
    }

    // ─────────────────────────────────────────────
    //  GET  settings/backup/download/(:num)
    // ─────────────────────────────────────────────

    public function download(int $id): ResponseInterface
    {
        if ($this->levelId() < 2) {
            return $this->response->setStatusCode(403)->setJSON(['message' => 'Access denied.']);
        }

        $model  = new BackupModel();
        $backup = $model->getById($id);

        if (! $backup) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Backup not found.']);
        }

        // Ensure the requesting user's office matches the backup's office
        if ((int) $backup['user_office_id'] !== $this->officeId() && $this->levelId() < 4) {
            return $this->response->setStatusCode(403)->setJSON(['message' => 'Access denied.']);
        }

        $path = $backup['backup_filepath'];
        if (! is_file($path)) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'File not found on server.']);
        }

        return $this->response
            ->setHeader('Content-Type', 'application/octet-stream')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $backup['backup_filename'] . '"')
            ->setHeader('Content-Length', (string) filesize($path))
            ->setBody(file_get_contents($path));
    }

    // ─────────────────────────────────────────────
    //  POST settings/backup/restore
    // ─────────────────────────────────────────────

    public function restore(): ResponseInterface
    {
        $levelId = $this->levelId();
        if ($levelId < 2 || $levelId > 3) {
            return $this->response->setStatusCode(403)->setJSON(['message' => 'Access denied.']);
        }

        $model    = new BackupModel();
        $officeId = $this->officeId();

        // Option A: restore from an existing backup ID
        $backupId = (int) ($this->request->getPost('backup_id') ?? 0);
        if ($backupId > 0) {
            $backup = $model->getById($backupId);
            if (! $backup) {
                return $this->response->setStatusCode(404)->setJSON(['message' => 'Backup not found.']);
            }
            if ((int) $backup['user_office_id'] !== $officeId && $levelId < 4) {
                return $this->response->setStatusCode(403)->setJSON(['message' => 'Office mismatch – cannot restore another office\'s backup.']);
            }
            $result = $model->restoreFromFile($backup['backup_filepath']);
            return $this->response->setJSON($result);
        }

        // Option B: restore from an uploaded file
        $file = $this->request->getFile('sql_file');
        if (! $file || ! $file->isValid()) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'No valid SQL file uploaded.']);
        }
        if (strtolower($file->getExtension()) !== 'sql') {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Only .sql files are accepted.']);
        }

        $sql    = file_get_contents($file->getTempName());
        $result = $model->restoreFromSqlString($sql);
        return $this->response->setJSON($result);
    }

    // ─────────────────────────────────────────────
    //  POST settings/backup/config
    // ─────────────────────────────────────────────

    public function saveConfig(): ResponseInterface
    {
        if ($this->levelId() < 3) {
            return $this->response->setStatusCode(403)->setJSON(['message' => 'Access denied.']);
        }

        $dir             = trim((string) ($this->request->getPost('backup_dir') ?? ''));
        $dir2            = trim((string) ($this->request->getPost('backup_dir_2') ?? ''));
        $intervalHours   = (int) ($this->request->getPost('backup_interval_hours') ?? 24);
        $backupTime      = trim((string) ($this->request->getPost('backup_time') ?? '00:00'));

        if ($dir === '') {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Backup directory (Drive 1) cannot be empty.']);
        }

        if ($intervalHours < 0 || $intervalHours > 720) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Interval must be between 0 and 720 hours.']);
        }

        // Validate HH:MM format
        if (! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $backupTime)) {
            $backupTime = '00:00';
        }

        // ── Resolve Drive 1 ──
        if (! str_contains($dir, ':') && ! str_starts_with($dir, '/')) {
            $dir = ROOTPATH . ltrim($dir, '/\\');
        }
        $dir = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR;
        if (! is_dir($dir) && ! mkdir($dir, 0775, true)) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Cannot create directory (Drive 1): ' . $dir]);
        }

        // ── Resolve Drive 2 (optional) ──
        if ($dir2 !== '') {
            if (! str_contains($dir2, ':') && ! str_starts_with($dir2, '/')) {
                $dir2 = ROOTPATH . ltrim($dir2, '/\\');
            }
            $dir2 = rtrim($dir2, '/\\') . DIRECTORY_SEPARATOR;
            if (! is_dir($dir2) && ! mkdir($dir2, 0775, true)) {
                // Non-fatal: save as empty so backup still works on drive 1
                $dir2 = '';
            }
        }

        $model = new BackupModel();
        $model->saveConfig([
            'backup_dir'            => $dir,
            'backup_dir_2'          => $dir2,
            'backup_interval_hours' => $intervalHours,
            'backup_time'           => $backupTime,
        ]);

        return $this->response->setJSON([
            'message'               => 'Backup settings updated.',
            'backup_dir'            => $dir,
            'backup_dir_2'          => $dir2,
            'backup_interval_hours' => $intervalHours,
            'backup_time'           => $backupTime,
        ]);
    }
}
