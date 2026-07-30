<?php

namespace App\Controllers;

use App\Models\SettingsModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class SettingsController extends BaseController
{
    public function __construct(
        private readonly SettingsModel $settingsModel = new SettingsModel(),
    ) {
    }

    private function userOfficeId(): int
    {
        return (int) (session('user')['user_office_id'] ?? 0);
    }

    private function levelId(): int
    {
        return (int) (session('user')['level_id'] ?? 0);
    }

    public function index()
    {
        $levelId = $this->levelId();

        // Level 1 has no access to settings
        if ($levelId < 2) {
            return redirect()->to(site_url('/'));
        }

        return view('settings/index_fixed', $this->settingsModel->indexData($this->userOfficeId(), $levelId));
    }

    public function fetch(string $type, int $id): ResponseInterface
    {
        // Whitelist: only allow types the settings model actually handles.
        // This prevents URL-crafted table-name injection even if the route is
        // somehow accessed directly.
        $allowed = [
            'units', 'types', 'entities', 'references',
            'offices', 'users', 'user_office_table',
        ];
        if (! in_array($type, $allowed, true)) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Unknown resource type.']);
        }

        $this->settingsModel->definition($type, $this->levelId());
        return $this->response->setJSON($this->settingsModel->fetchRecord($type, $id));
    }

    public function save(string $type): ResponseInterface
    {
        $levelId      = $this->levelId();
        $id           = (int) ($this->request->getPost('id') ?? 0);
        $userOfficeId = $this->userOfficeId();

        $allowed = [
            'units', 'types', 'entities', 'references',
            'offices', 'users', 'user_office_table',
        ];
        if (! in_array($type, $allowed, true)) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Unknown resource type.']);
        }

        $definition = $this->settingsModel->definition($type, $levelId);

        if ($type === 'users') {
            if ($id === 0) {
                return $this->response->setStatusCode(403)->setJSON(['message' => 'Users must create their own accounts via registration.']);
            }
            return $this->saveUser($id, $this->requestPayload($definition['fields']), $userOfficeId);
        }

        if ($type === 'user_office_table') {
            if ($levelId < 4) {
                return $this->response->setStatusCode(403)->setJSON(['message' => 'Access denied.']);
            }
            return $this->saveUserOffice($id, $this->requestPayload($definition['fields']));
        }

        $payload = $this->sanitizePayload($this->requestPayload($definition['fields']));

        if ($this->hasEmptyRequiredText($payload)) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Please fill in the required fields.']);
        }

        $this->settingsModel->saveRecord($type, $id, $payload, $userOfficeId);

        return $this->response->setJSON([
            'message' => $id > 0 ? 'Record updated successfully.' : 'Record created successfully.',
        ]);
    }

    public function delete(string $type, int $id): ResponseInterface
    {
        $levelId     = $this->levelId();
        $sessionUser = session('user');

        $allowed = [
            'units', 'types', 'entities', 'references',
            'offices', 'users', 'user_office_table',
        ];
        if (! in_array($type, $allowed, true)) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Unknown resource type.']);
        }

        $this->settingsModel->definition($type, $levelId);

        if ($type === 'users' && $sessionUser && (int) $sessionUser['id'] === $id) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'You cannot delete the currently logged-in user.']);
        }

        if ($type === 'users' && $levelId === 3) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Use deactivate instead of delete for users.']);
        }

        if ($type === 'user_office_table' && $levelId < 4) {
            return $this->response->setStatusCode(403)->setJSON(['message' => 'Access denied.']);
        }

        try {
            $this->settingsModel->deleteRecord($type, $id);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'Delete failed: ' . $e->getMessage(),
            ]);
        }

        return $this->response->setJSON(['message' => 'Record deleted successfully.']);
    }

    public function activate(int $id): ResponseInterface
    {
        if ($this->levelId() < 3) {
            return $this->response->setStatusCode(403)->setJSON(['message' => 'Access denied.']);
        }
        $this->settingsModel->activateUser($id);
        return $this->response->setJSON(['message' => 'User activated successfully.']);
    }

    public function deactivate(int $id): ResponseInterface
    {
        $levelId     = $this->levelId();
        if ($levelId < 3) {
            return $this->response->setStatusCode(403)->setJSON(['message' => 'Access denied.']);
        }

        $sessionUser = session('user');
        if ($sessionUser && (int) $sessionUser['id'] === $id) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'You cannot deactivate the currently logged-in user.']);
        }

        $this->settingsModel->deactivateUser($id);
        return $this->response->setJSON(['message' => 'User deactivated successfully.']);
    }

    private function requestPayload(array $fields): array
    {
        $payload = [];
        foreach ($fields as $field) {
            $payload[$field] = $this->request->getPost($field);
        }
        return $payload;
    }

    private function saveUser(int $id, array $payload, int $userOfficeId): ResponseInterface
    {
        $payload['name']             = trim((string) ($payload['name'] ?? ''));
        $payload['username']         = trim((string) ($payload['username'] ?? ''));
        $payload['email']            = trim((string) ($payload['email'] ?? ''));
        $payload['lvl_of_access_id'] = (int) ($payload['lvl_of_access_id'] ?? 0);
        $payload['user_office_id']   = (int) ($payload['user_office_id'] ?? $userOfficeId);

        if ($payload['username'] === '' || $payload['lvl_of_access_id'] <= 0 || $payload['user_office_id'] <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Please fill in the required user fields.']);
        }

        if (($payload['password'] ?? '') !== '') {
            $payload['password'] = password_hash((string) $payload['password'], PASSWORD_DEFAULT);
        } else {
            unset($payload['password']);
        }

        $userModel = new UserModel();

        if ($id > 0) {
            $userModel->update($id, $payload);
            return $this->response->setJSON(['message' => 'User updated successfully.']);
        }

        if (! isset($payload['password'])) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Password is required for new users.']);
        }

        $payload['user_activity_id'] = 1; // Active
        $userModel->insert($payload);

        return $this->response->setJSON(['message' => 'User created successfully.']);
    }

    private function saveUserOffice(int $id, array $payload): ResponseInterface
    {
        $payload['user_office_name'] = trim((string) ($payload['user_office_name'] ?? ''));

        if ($payload['user_office_name'] === '') {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'User Office name is required.']);
        }

        if ($id > 0) {
            db_connect()->table('user_office_table')->where('user_office_id', $id)->update($payload);
            return $this->response->setJSON(['message' => 'User Office updated successfully.']);
        }

        db_connect()->table('user_office_table')->insert($payload);
        return $this->response->setJSON(['message' => 'User Office created successfully.']);
    }

    private function sanitizePayload(array $payload): array
    {
        foreach ($payload as $field => $value) {
            $payload[$field] = str_ends_with($field, '_id')
                ? ($value === '' || $value === null ? null : (int) $value)
                : trim((string) $value);
        }
        return $payload;
    }

    private function hasEmptyRequiredText(array $payload): bool
    {
        $requiredTextValues = array_filter(
            $payload,
            static fn ($key) => ! str_ends_with((string) $key, '_id'),
            ARRAY_FILTER_USE_KEY,
        );
        return in_array('', $requiredTextValues, true);
    }
}
