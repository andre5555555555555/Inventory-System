<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * LevelFilter
 *
 * Enforces a minimum access level on protected routes.
 *
 * Usage in Routes.php:
 *   ['filter' => 'level:2']   → requires level 2 or above
 *   ['filter' => 'level:3']   → requires level 3 or above
 *
 * If the user's level is below the required minimum:
 *   - AJAX requests (X-Requested-With: XMLHttpRequest) → 403 JSON
 *   - Browser requests → redirect to dashboard
 */
class LevelFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = session('user');

        if (! $user) {
            return redirect()->to(site_url('login'));
        }

        $userLevel = (int) ($user['level_id'] ?? 0);
        $required  = (int) ($arguments[0] ?? 1);

        if ($userLevel < $required) {
            // AJAX → JSON 403
            if ($request->hasHeader('X-Requested-With') &&
                strtolower($request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest') {
                return service('response')
                    ->setStatusCode(403)
                    ->setJSON(['message' => 'Access denied. Insufficient permissions.']);
            }

            // Browser → redirect with flash message
            return redirect()->to(site_url('/'))->with(
                'error',
                'You do not have permission to access that page.'
            );
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
