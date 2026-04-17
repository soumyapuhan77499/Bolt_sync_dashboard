<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Bolt Sync Admin</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --bg: #f3f4f6;
            --card: #ffffff;
            --text: #111827;
            --muted: #6b7280;
            --border: #e5e7eb;
            --success-bg: #dcfce7;
            --success-text: #166534;
            --error-bg: #fee2e2;
            --error-text: #991b1b;
            --shadow: 0 20px 60px rgba(17, 24, 39, 0.10);
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, 0.16), transparent 30%),
                radial-gradient(circle at bottom left, rgba(59, 130, 246, 0.12), transparent 30%),
                var(--bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: var(--text);
        }

        .login-wrapper {
            width: 100%;
            max-width: 1080px;
            display: grid;
            grid-template-columns: 1fr 460px;
            background: var(--card);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        .login-left {
            background: linear-gradient(135deg, #0f172a, #1e3a8a);
            color: #fff;
            padding: 56px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .brand-badge {
            display: inline-flex;
            width: fit-content;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,0.12);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .login-left h1 {
            font-size: 42px;
            line-height: 1.15;
            margin-bottom: 16px;
        }

        .login-left p {
            font-size: 16px;
            line-height: 1.8;
            color: rgba(255,255,255,0.82);
            max-width: 520px;
        }

        .feature-list {
            margin-top: 28px;
            display: grid;
            gap: 14px;
        }

        .feature-item {
            padding: 14px 16px;
            border-radius: 14px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.10);
        }

        .feature-item strong {
            display: block;
            margin-bottom: 6px;
            font-size: 15px;
        }

        .feature-item span {
            font-size: 13px;
            color: rgba(255,255,255,0.78);
        }

        .login-right {
            padding: 44px 34px;
            display: flex;
            align-items: center;
        }

        .login-card {
            width: 100%;
        }

        .login-card h2 {
            font-size: 30px;
            margin-bottom: 8px;
        }

        .login-card .subtext {
            font-size: 14px;
            color: var(--muted);
            margin-bottom: 26px;
        }

        .alert {
            border-radius: 14px;
            padding: 14px 16px;
            font-size: 14px;
            margin-bottom: 16px;
            border: 1px solid transparent;
        }

        .alert-success {
            background: var(--success-bg);
            color: var(--success-text);
            border-color: #86efac;
        }

        .alert-danger {
            background: var(--error-bg);
            color: var(--error-text);
            border-color: #fca5a5;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            height: 50px;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 0 15px;
            font-size: 14px;
            outline: none;
            transition: all 0.2s ease;
            background: #fff;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }

        .submit-btn {
            width: 100%;
            border: none;
            background: var(--primary);
            color: #fff;
            height: 52px;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s ease;
            margin-top: 8px;
        }

        .submit-btn:hover {
            background: var(--primary-dark);
        }

        .login-footer {
            margin-top: 18px;
            font-size: 13px;
            color: var(--muted);
            text-align: center;
        }

        .error-list {
            margin-top: 8px;
            padding-left: 18px;
        }

        .error-list li {
            margin-bottom: 4px;
        }

        @media (max-width: 991px) {
            .login-wrapper {
                grid-template-columns: 1fr;
            }

            .login-left {
                padding: 36px 28px;
            }

            .login-left h1 {
                font-size: 32px;
            }

            .login-right {
                padding: 28px 22px;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-left">
            <div class="brand-badge">Bolt Sync Admin</div>

            <h1>Control database sync, schema diff, backups and replication from one dashboard.</h1>

            <p>
                This admin panel is designed to manage Source Supabase and Destination Database
                connections with a clean operational workflow.
            </p>

            <div class="feature-list">
                <div class="feature-item">
                    <strong>Connection Monitoring</strong>
                    <span>Test source and destination database health from the dashboard.</span>
                </div>

                <div class="feature-item">
                    <strong>Schema Compare</strong>
                    <span>Inspect table and column changes before applying structure updates.</span>
                </div>

                <div class="feature-item">
                    <strong>Replication & Backup</strong>
                    <span>Manage logical sync strategy, audit logs, and backup tracking.</span>
                </div>
            </div>
        </div>

        <div class="login-right">
            <div class="login-card">
                <h2>Welcome back</h2>
                <p class="subtext">Login with your admin account to continue.</p>

                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        <strong>Please fix the following:</strong>
                        <ul class="error-list">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login.submit') }}">
                    @csrf

                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            value="{{ old('email') }}"
                            placeholder="Enter admin email"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Enter password"
                            required
                        >
                    </div>

                    <button type="submit" class="submit-btn">Login to Dashboard</button>
                </form>

                <div class="login-footer">
                    Secure access for database operations and sync management.
                </div>
            </div>
        </div>
    </div>
</body>
</html>