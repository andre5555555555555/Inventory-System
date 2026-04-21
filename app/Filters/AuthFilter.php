<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->has('user')) {
            return redirect()->to(site_url('login'));
        }

        // Force password change if flagged (allow access to change-password and logout only)
        $currentPath = trim((string) uri_string(), '/');
        $allowedPaths = ['change-password', 'logout'];

        if (
            (int) (session('user')['must_change_password'] ?? 0) === 1
            && ! in_array($currentPath, $allowedPaths, true)
        ) {
            return redirect()->to(site_url('change-password'));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
