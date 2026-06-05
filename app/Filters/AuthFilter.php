<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AuthFilter — Authentication, role enforcement, and security headers.
 *
 * Fixes applied:
 *  H1 — Added Content-Security-Policy header in after().
 *  L2 — Project validity re-checked from DB only every 5 minutes (session-cached).
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if (! $session->get('logged_in')) {
            if ($request->isAJAX()) {
                return service('response')->setStatusCode(401)->setJSON(['error' => 'Unauthenticated']);
            }
            return redirect()->to(site_url('login'))->with('error', 'Please log in to continue.');
        }

        // Idle timeout — destroy session after 30 minutes of inactivity
        $idleLimit    = 1800; // 30 minutes
        $lastActivity = (int) $session->get('last_activity');
        if ($lastActivity && (time() - $lastActivity) > $idleLimit) {
            $session->destroy();
            if ($request->isAJAX()) {
                return service('response')->setStatusCode(401)->setJSON(['error' => 'Session expired due to inactivity.']);
            }
            return redirect()->to(site_url('login'))->with('error', 'Your session expired due to inactivity. Please log in again.');
        }

        // Silent ping routes used by auto-refresh must NOT reset last_activity,
        // otherwise the idle timer would never expire while the tab is open.
        $isPingRoute = str_ends_with(trim($request->getUri()->getPath(), '/'), '/ping')
                    || $request->getUri()->getPath() === 'center/ping'
                    || $request->getUri()->getPath() === 'admin/ping';
        if (! $isPingRoute) {
            $session->set('last_activity', time());
        }

        if ($arguments) {
            $required = $arguments[0];
            $role     = $session->get('role'); // 'admin' or 'center'
            if ($role !== $required) {
                if ($request->isAJAX()) {
                    return service('response')->setStatusCode(403)->setJSON(['error' => 'Access denied']);
                }
                return redirect()->to(site_url('login'))->with('error', 'Access denied.');
            }
        }

        // For center users: verify the project is still active/within date range.
        // FIX (L2): Cache result in session for 5 minutes to avoid a DB query on
        // every single page load (dashboard, raise ticket, view, AJAX polls).
        if ($session->get('role') === 'center') {
            $projectId = (int) $session->get('project_id');
            if ($projectId) {
                $now            = time();
                $lastChecked    = (int) $session->get('project_checked_at');
                $cacheSeconds   = 300; // 5 minutes

                if ($now - $lastChecked >= $cacheSeconds) {
                    $db    = \Config\Database::connect();
                    $today = date('Y-m-d');

                    $project = $db->table('projects')
                        ->select('id, is_active, manually_disabled, end_date')
                        ->where('id', $projectId)
                        ->get()->getRowArray();

                    $isExpired  = ! $project || $project['end_date'] < $today;
                    $isDisabled = $project && ($project['manually_disabled'] || ! $project['is_active']);

                    if ($isExpired || $isDisabled) {
                        $session->destroy();
                        if ($request->isAJAX()) {
                            return service('response')->setStatusCode(401)->setJSON([
                                'error' => 'Your project session has ended. Please log in again.',
                            ]);
                        }
                        $msg = $isExpired
                            ? 'Your project has ended. Please contact the administrator.'
                            : 'This project has been disabled. Please contact the administrator.';
                        return redirect()->to(site_url('login'))->with('error', $msg);
                    }

                    // Refresh the cache timestamp
                    $session->set('project_checked_at', $now);
                }
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Security headers set on every response.
        $response->setHeader('X-Frame-Options', 'DENY');
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        // HIGH (H1): Content-Security-Policy.
        // Allows Bootstrap/fonts from jsdelivr and Google Fonts.
        // Tighten by removing 'unsafe-inline' once scripts are in external files.
        $response->setHeader(
            'Content-Security-Policy',
            "default-src 'self'; " .
            "script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; " .
            "style-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com 'unsafe-inline'; " .
            "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; " .
            "img-src 'self' data:; " .
            "connect-src 'self'; " .
            "frame-ancestors 'none';"
        );
    }
}
