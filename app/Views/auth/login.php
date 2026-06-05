<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — CBT Support Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy:   #0b1d35;
            --blue:   #1a56db;
            --accent: #38bdf8;
            --light:  #e8f1ff;
            --card:   #ffffff;
            --text:   #1e293b;
            --muted:  #64748b;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            background: var(--navy);
            overflow: hidden;
        }

        /* ── animated background blobs ── */
        .bg-blobs {
            position: fixed; inset: 0; z-index: 0; overflow: hidden; pointer-events: none;
        }
        .blob {
            position: absolute; border-radius: 50%; filter: blur(80px); opacity: .18;
            animation: drift 12s ease-in-out infinite alternate;
        }
        .blob-1 { width: 520px; height: 520px; background: #2563eb; top: -120px; left: -100px; animation-duration: 14s; }
        .blob-2 { width: 400px; height: 400px; background: #38bdf8; bottom: -80px; left: 20%; animation-duration: 10s; animation-delay: -4s; }
        .blob-3 { width: 300px; height: 300px; background: #6366f1; top: 30%; left: 35%; animation-duration: 16s; animation-delay: -7s; }
        @keyframes drift {
            from { transform: translate(0, 0) scale(1); }
            to   { transform: translate(40px, 30px) scale(1.08); }
        }

        /* ── layout ── */
        .page-wrap {
            position: relative; z-index: 1;
            display: flex; width: 100%; min-height: 100vh;
        }

        /* ── LEFT PANEL ── */
        .left-panel {
            flex: 1.1;
            display: flex; flex-direction: column; justify-content: center;
            padding: 4rem 5rem;
            position: relative;
        }

        .brand-badge {
            display: inline-flex; align-items: center; gap: .55rem;
            background: rgba(56,189,248,.12); border: 1px solid rgba(56,189,248,.3);
            color: var(--accent); border-radius: 50px; padding: .35rem 1rem;
            font-family: 'Sora', sans-serif; font-size: .72rem; font-weight: 600;
            letter-spacing: .08em; text-transform: uppercase; margin-bottom: 2rem;
            width: fit-content;
        }

        .left-panel h1 {
            font-family: 'Sora', sans-serif;
            font-size: clamp(2rem, 3.5vw, 2.9rem);
            font-weight: 800; line-height: 1.18;
            color: #fff; margin-bottom: 1.1rem;
        }
        .left-panel h1 span {
            background: linear-gradient(90deg, var(--accent), #818cf8);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }

        .left-panel .subtitle {
            color: rgba(255,255,255,.55); font-size: .95rem; line-height: 1.7;
            max-width: 430px; margin-bottom: 2.8rem;
        }

        /* feature points */
        .features { display: flex; flex-direction: column; gap: 1rem; }

        .feature-item {
            display: flex; align-items: flex-start; gap: 1rem;
            animation: fadeUp .6s ease both;
        }
        .feature-item:nth-child(1) { animation-delay: .1s; }
        .feature-item:nth-child(2) { animation-delay: .2s; }
        .feature-item:nth-child(3) { animation-delay: .3s; }
        .feature-item:nth-child(4) { animation-delay: .4s; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .feature-icon {
            flex-shrink: 0;
            width: 40px; height: 40px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
        }
        .fi-blue   { background: rgba(37,99,235,.25);  color: #60a5fa; }
        .fi-teal   { background: rgba(20,184,166,.2);  color: #2dd4bf; }
        .fi-indigo { background: rgba(99,102,241,.2);  color: #a5b4fc; }
        .fi-amber  { background: rgba(245,158,11,.18); color: #fcd34d; }

        .feature-text strong {
            display: block; color: #fff; font-weight: 600; font-size: .88rem; margin-bottom: .15rem;
        }
        .feature-text span {
            color: rgba(255,255,255,.45); font-size: .8rem; line-height: 1.5;
        }

        /* divider line */
        .left-divider {
            position: absolute; right: 0; top: 10%; bottom: 10%;
            width: 1px;
            background: linear-gradient(to bottom, transparent, rgba(255,255,255,.1) 30%, rgba(255,255,255,.1) 70%, transparent);
        }

        /* ── RIGHT PANEL ── */
        .right-panel {
            flex: .85;
            display: flex; align-items: center; justify-content: center;
            padding: 3rem 3.5rem;
        }

        .login-card {
            background: rgba(255,255,255,.97);
            border-radius: 20px;
            padding: 2.8rem 2.5rem;
            width: 100%; max-width: 400px;
            box-shadow: 0 32px 80px rgba(0,0,0,.45), 0 0 0 1px rgba(255,255,255,.08);
            animation: fadeUp .5s ease both;
        }

        .card-logo {
            width: 52px; height: 52px;
            background: linear-gradient(135deg, #0b1d35, #1a56db);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; color: #fff;
            margin-bottom: 1.4rem;
            box-shadow: 0 8px 24px rgba(26,86,219,.4);
        }

        .login-card h4 {
            font-family: 'Sora', sans-serif;
            font-size: 1.4rem; font-weight: 700; color: var(--text);
            margin-bottom: .35rem;
        }
        .login-card .card-sub {
            color: var(--muted); font-size: .83rem; margin-bottom: 1.8rem;
        }

        .form-label { font-size: .8rem; font-weight: 600; color: #374151; margin-bottom: .4rem; }

        .input-wrap {
            position: relative; margin-bottom: 1.1rem;
        }
        .input-wrap .ico {
            position: absolute; left: .85rem; top: 50%; transform: translateY(-50%);
            color: #94a3b8; font-size: 1rem; pointer-events: none;
        }
        .input-wrap input {
            width: 100%; padding: .65rem .9rem .65rem 2.5rem;
            border: 1.5px solid #e2e8f0; border-radius: 10px;
            font-family: 'DM Sans', sans-serif; font-size: .88rem; color: var(--text);
            background: #f8fafc; transition: border-color .2s, box-shadow .2s, background .2s;
            outline: none;
        }
        .input-wrap input:focus {
            border-color: var(--blue); background: #fff;
            box-shadow: 0 0 0 3.5px rgba(26,86,219,.1);
        }
        .input-wrap input::placeholder { color: #b0bec8; }

        .btn-login {
            width: 100%; padding: .75rem;
            background: linear-gradient(135deg, #0b1d35 0%, #1a56db 100%);
            border: none; border-radius: 10px;
            font-family: 'Sora', sans-serif; font-weight: 600; font-size: .9rem;
            color: #fff; cursor: pointer; margin-top: .5rem;
            transition: transform .15s, box-shadow .15s, opacity .15s;
            box-shadow: 0 6px 20px rgba(26,86,219,.4);
        }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 28px rgba(26,86,219,.5);
        }
        .btn-login:active { transform: translateY(0); }

        .hint-box {
            background: #f0f9ff; border: 1px solid #bae6fd;
            border-radius: 10px; padding: .8rem 1rem;
            font-size: .76rem; color: #0369a1;
            margin-top: 1.2rem; line-height: 1.6;
        }
        .hint-box i { font-size: .85rem; }

        .footer-note {
            text-align: center; margin-top: 1.5rem;
            font-size: .72rem; color: #cbd5e1;
        }

        /* alerts */
        .alert { font-size: .83rem; border-radius: 10px; padding: .7rem 1rem; margin-bottom: 1.2rem; }

        /* ── responsive ── */
        @media (max-width: 900px) {
            .left-panel { display: none; }
            .right-panel { flex: 1; padding: 2rem 1.5rem; }
            body { background: linear-gradient(135deg, #0b1d35, #1a56db); }
        }
    </style>
</head>
<body>

<!-- animated blobs -->
<div class="bg-blobs">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
</div>

<div class="page-wrap">

    <!-- ══ LEFT PANEL ══ -->
    <div class="left-panel">
        <div class="brand-badge">
            <i class="bi bi-shield-check-fill"></i> CBT Support Hub
        </div>

        <h1>Exam Day Support,<br><span>Resolved Fast.</span></h1>

        <p class="subtitle">
            A centralised helpdesk built for Computer-Based Test operations —
            connecting exam centres, coordinators, and administrators in real time.
        </p>

        <div class="features">
            <div class="feature-item">
                <div class="feature-icon fi-blue">
                    <i class="bi bi-ticket-detailed-fill"></i>
                </div>
                <div class="feature-text">
                    <strong>Instant Ticket Raising</strong>
                    <span>Centers log issues in seconds — technical or operational — with urgency tagging.</span>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon fi-teal">
                    <i class="bi bi-display-fill"></i>
                </div>
                <div class="feature-text">
                    <strong>Remote Access Ready</strong>
                    <span>Share your AnyDesk ID with the ticket so admins can connect and resolve instantly.</span>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon fi-indigo">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <div class="feature-text">
                    <strong>Live Admin Dashboard</strong>
                    <span>Track open tickets, urgency levels, and project-wise resolution status at a glance.</span>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon fi-amber">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <div class="feature-text">
                    <strong>OTP-Based Center Login</strong>
                    <span>Each project gets a unique OTP — no passwords to manage for exam centre staff.</span>
                </div>
            </div>
        </div>

        <div class="left-divider"></div>
    </div>

    <!-- ══ RIGHT PANEL ══ -->
    <div class="right-panel">
        <div class="login-card">
            <div class="card-logo"><i class="bi bi-shield-check"></i></div>
            <h4>Welcome Back</h4>
            <p class="card-sub">Sign in to access your portal</p>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <?= esc(session()->getFlashdata('error')) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    <?= esc(session()->getFlashdata('success')) ?>
                </div>
            <?php endif; ?>

            <form action="<?= site_url('login') ?>" method="POST">
                <?= csrf_field() ?>

                <label class="form-label">Username </label>
                <div class="input-wrap">
                    <i class="bi bi-person ico"></i>
                    <input type="text" name="username"
                           value="<?= old('username') ?>"
                           placeholder="Enter your User name"
                           maxlength="100"
                           required autofocus>
                </div>

                <label class="form-label">Password </label>
                <div class="input-wrap">
                    <i class="bi bi-lock ico"></i>
                    <input type="password" name="password"
                           placeholder="Enter the password"
                           maxlength="100"
                           required>
                </div>

                <button type="submit" class="btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
            </form>

            <div class="hint-box">
                <i class="bi bi-info-circle me-1"></i>
                Center staff: use your <strong>Center Code</strong> as username
                and the <strong>Project OTP</strong> as password.
            </div>

            <p class="footer-note">CBT Support Hub &copy; <?= date('Y') ?></p>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
