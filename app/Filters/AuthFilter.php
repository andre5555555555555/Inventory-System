<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AuthFilter
 *
 * - Redirects unauthenticated visitors to /login
 * - Enforces a 24-hour session timeout (from login_time)
 * - Sets security response headers on every authenticated response
 */
class AuthFilter implements FilterInterface
{
    /** Session lifetime in seconds (24 hours) */
    private const SESSION_TTL = 86400;

    public function before(RequestInterface $request, $arguments = null)
    {
        // ── 1. Must be logged in ──────────────────────────────────────────
        if (! session()->has('user')) {
            return redirect()->to(site_url('login'));
        }

        // ── 2. 24-hour absolute session timeout ───────────────────────────
        $loginTime = (int) (session('login_time') ?? 0);

        if ($loginTime === 0 || (time() - $loginTime) > self::SESSION_TTL) {
            // Expired — destroy and redirect
            session()->destroy();
            return redirect()->to(site_url('login'))->with(
                'error',
                'Your session has expired. Please log in again.'
            );
        }

        // ── 3. Verify the session user still exists and is active ─────────
        $userId = (int) (session('user')['id'] ?? 0);
        if ($userId > 0) {
            $row = db_connect()
                ->table('user_table')
                ->select('user_activity_id')
                ->where('user_id', $userId)
                ->get()
                ->getRowArray();

            // Deactivated or deleted → kick out immediately
            if (! $row || (int) $row['user_activity_id'] !== 1) {
                session()->destroy();
                return redirect()->to(site_url('login'))->with(
                    'error',
                    'Your account has been deactivated. Please contact an administrator.'
                );
            }
        }

        // ── 4. Force password change if flagged ──────────────────────────
        // Use CI4's current URI and strip any index.php prefix
        $rawPath    = trim($request->getUri()->getPath(), '/');
        $currentPath = preg_replace('#^index\.php/?#', '', $rawPath);
        $currentPath = trim($currentPath, '/');

        if (session('must_change_password')) {
            $allowed = ['change-password', 'logout'];
            if (! in_array($currentPath, $allowed, true)) {
                return redirect()->to(site_url('change-password'));
            }
        }

        // ── 5. Force SMTP setup if flagged ───────────────────────────────
        if (session('must_setup_smtp')) {
            $allowed = ['setup-smtp', 'logout'];
            if (! in_array($currentPath, $allowed, true)) {
                return redirect()->to(site_url('setup-smtp'));
            }
        }

        // ── 6. Force recovery-email setup if flagged ─────────────────────────
        if (session('must_setup_recovery_email')) {
            $allowed = ['setup-recovery-email', 'logout'];
            if (! in_array($currentPath, $allowed, true)) {
                return redirect()->to(site_url('setup-recovery-email'));
            }
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // ── Security headers on every authenticated response ───────────────
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->setHeader('X-XSS-Protection', '1; mode=block');
        $response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->setHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        return $response;
    }
}
