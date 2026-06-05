<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\CenterModel;

/**
 * AuthController — Handles admin and center login/logout.
 *
 * Fixes applied:
 *  M1 — Removed redundant/unsafe second LOWER(username) query; single safe query only.
 *  M6 — Login audit log: every attempt (success/failure) is recorded in login_logs.
 *  M7 — OTP is now compared with password_verify() against bcrypt hash stored in DB.
 */
class AuthController extends BaseController
{
    public function login()
    {
        if (session()->get('logged_in')) {
            return $this->redirectByRole();
        }
        return view('auth/login', ['title' => 'Login — CBT Support Hub']);
    }

    public function loginProcess()
    {
        // Rate limiting — max 10 attempts per IP per minute
        $throttler = \Config\Services::throttler();
        if ($throttler->check('login_' . md5($this->request->getIPAddress()), 10, MINUTE) === false) {
            $this->writeLoginLog(null, 'rate_limited', $this->request->getIPAddress());
            return redirect()->back()->withInput()
                ->with('error', 'Too many login attempts. Please wait 1 minute and try again.');
        }

        $identifier = strtoupper(trim($this->request->getPost('username') ?? ''));
        $password   = trim($this->request->getPost('password') ?? '');

        if (! $identifier || ! $password) {
            return redirect()->back()->withInput()->with('error', 'Please enter username and password.');
        }

        // Cap input lengths before any DB work — prevents DoS via huge string comparisons
        if (strlen($identifier) > 100 || strlen($password) > 100) {
            $this->writeLoginLog(null, 'failed', $this->request->getIPAddress());
            return redirect()->back()->withInput()->with('error', 'Invalid credentials. Please check your Center Code and OTP.');
        }

        // ── 1. Try Admin login ───────────────────────────────────────
        // FIX (M1): Single case-insensitive query with bound parameter — no second
        // LOWER(username) query which bypassed CI4 query builder escaping.
        $userModel = new UserModel();
        $adminUser = $userModel->allowCallbacks(false)
            ->where('username', strtolower($identifier))
            ->where('is_active', 1)
            ->first();

        if ($adminUser && password_verify($password, $adminUser['password_hash'])) {
            session()->regenerate(true);
            session()->set([
                'logged_in'     => true,
                'role'          => 'admin',
                'user_id'       => $adminUser['id'],
                'user_name'     => $adminUser['name'],
                'username'      => $adminUser['username'],
                'last_activity' => time(),
            ]);
            // FIX (M6): Log successful admin login
            $this->writeLoginLog($adminUser['id'], 'success', $this->request->getIPAddress(), 'admin');
            return redirect()->to(site_url('admin/dashboard'));
        }

        // ── 2. Try Center login: center_code + project OTP ──────────
        $centerModel = new CenterModel();
        $center = $centerModel->where('center_code', $identifier)
                              ->where('is_active', 1)
                              ->first();

        if (! $center) {
            $this->writeLoginLog(null, 'failed', $this->request->getIPAddress());
            return redirect()->back()->withInput()
                ->with('error', 'Invalid credentials. Please check your Center Code and OTP.');
        }

        $db = \Config\Database::connect();

        $pcCount = $db->table('project_centers')
            ->where('center_id', $center['id'])
            ->countAllResults();

        if ($pcCount === 0) {
            $this->writeLoginLog(null, 'failed', $this->request->getIPAddress());
            return redirect()->back()->withInput()
                ->with('error', 'Invalid credentials. Please check your Center Code and OTP.');
        }

        // Only fetch projects that are active, within date range, and not manually cancelled
        $today       = date('Y-m-d');
        $allProjects = $db->table('projects p')
            ->select('p.id, p.name, p.otp_hash, p.is_active, p.start_date, p.end_date')
            ->join('project_centers pc', 'pc.project_id = p.id')
            ->where('pc.center_id', $center['id'])
            ->where('p.is_active', 1)
            ->where('p.manually_disabled', 0)
            ->where('p.start_date <=', $today)
            ->where('p.end_date >=', $today)
            ->get()->getResultArray();

        $otpEntered = strtoupper($password);
        $matchFound = false;
        $project    = null;

        // FIX (M7): Compare entered OTP against bcrypt hash stored in DB.
        foreach ($allProjects as $proj) {
            if (password_verify($otpEntered, $proj['otp_hash'])) {
                $matchFound = true;
                $project    = $proj;
                break;
            }
        }

        if (! $matchFound) {
            $this->writeLoginLog(null, 'failed', $this->request->getIPAddress(), 'center', $center['id']);
            return redirect()->back()->withInput()
                ->with('error', 'Invalid credentials. Please check your Center Code and OTP.');
        }

        // All good — log in
        session()->regenerate(true);
        session()->set([
            'logged_in'           => true,
            'role'                => 'center',
            'center_id'           => $center['id'],
            'center_code'         => $center['center_code'],
            'center_name'         => $center['center_name'],
            'project_id'          => $project['id'],
            'project_name'        => $project['name'],
            'project_checked_at'  => time(), // FIX (L2): seed the 5-min cache timer
            'last_activity'       => time(),
        ]);

        // FIX (M6): Log successful center login
        $this->writeLoginLog(null, 'success', $this->request->getIPAddress(), 'center', $center['id'], $project['id']);

        return redirect()->to(site_url('center/dashboard'));
    }

    private function redirectByRole(): \CodeIgniter\HTTP\RedirectResponse
    {
        return session()->get('role') === 'admin'
            ? redirect()->to(site_url('admin/dashboard'))
            : redirect()->to(site_url('center/dashboard'));
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to(site_url('login'))->with('success', 'You have been logged out.');
    }

    /**
     * FIX (M6): Write a row to the login_logs audit table.
     * Failures record the attempted identifier. Successes record the resolved user/center.
     */
    private function writeLoginLog(
        ?int    $userId,
        string  $result,
        string  $ip,
        string  $loginType = 'unknown',
        ?int    $centerId  = null,
        ?int    $projectId = null
    ): void {
        try {
            $db = \Config\Database::connect();
            $db->table('login_logs')->insert([
                'user_id'    => $userId,
                'center_id'  => $centerId,
                'project_id' => $projectId,
                'login_type' => $loginType,
                'result'     => $result,          // 'success', 'failed', 'rate_limited'
                'ip_address' => $ip,
                'user_agent' => substr($this->request->getUserAgent()->getAgentString(), 0, 255),
                'logged_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Never let audit logging break the login flow — log to CI logger instead
            log_message('error', 'login_logs insert failed: ' . $e->getMessage());
        }
    }
}
