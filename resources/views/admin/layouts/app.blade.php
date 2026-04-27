<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Bolt Sync Admin')</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --sidebar-width: 280px;
            --sidebar-bg: #111827;
            --sidebar-text: #d1d5db;
            --sidebar-active: #2563eb;
            --body-bg: #f3f4f6;
            --card-bg: #ffffff;
            --text-dark: #111827;
            --text-muted: #6b7280;
            --border-color: #e5e7eb;
            --success-bg: #dcfce7;
            --success-text: #166534;
            --error-bg: #fee2e2;
            --error-text: #991b1b;
            --warning-bg: #fef3c7;
            --warning-text: #92400e;
            --shadow: 0 10px 30px rgba(17, 24, 39, 0.08);
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: var(--body-bg);
            color: var(--text-dark);
        }

        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .admin-sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .admin-main {
            flex: 1;
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .admin-topbar {
            background: #fff;
            border-bottom: 1px solid var(--border-color);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .menu-toggle {
            border: none;
            background: #111827;
            color: #fff;
            width: 42px;
            height: 42px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 18px;
            display: none;
        }

        .page-heading h1 {
            font-size: 22px;
            margin-bottom: 4px;
            color: var(--text-dark);
        }

        .page-heading p {
            font-size: 13px;
            color: var(--text-muted);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-chip {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            padding: 10px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
        }

        .admin-content {
            padding: 24px;
            flex: 1;
        }

        .content-card {
            background: var(--card-bg);
            border-radius: 18px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
            padding: 22px;
        }

        .flash-wrapper {
            display: grid;
            gap: 12px;
            margin-bottom: 20px;
        }

        .alert {
            border-radius: 14px;
            padding: 14px 16px;
            font-size: 14px;
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

        .alert-warning {
            background: var(--warning-bg);
            color: var(--warning-text);
            border-color: #fcd34d;
        }

        .default-sidebar {
            padding: 22px 16px;
        }

        .brand-box {
            padding: 18px 16px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.06);
            margin-bottom: 20px;
        }

        .brand-box h2 {
            color: #fff;
            font-size: 20px;
            margin-bottom: 6px;
        }

        .brand-box p {
            font-size: 13px;
            color: #9ca3af;
            line-height: 1.5;
        }

        .nav-title {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #9ca3af;
            margin: 18px 10px 10px;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-menu li {
            margin-bottom: 8px;
        }

        .nav-menu a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            text-decoration: none;
            color: var(--sidebar-text);
            padding: 12px 14px;
            border-radius: 12px;
            transition: all 0.2s ease;
            font-size: 14px;
            font-weight: 600;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            background: var(--sidebar-active);
            color: #fff;
        }

        .logout-form {
            margin-top: 18px;
        }

        .logout-btn {
            width: 100%;
            border: none;
            background: #dc2626;
            color: #fff;
            padding: 12px 14px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
        }

        .logout-btn:hover {
            background: #b91c1c;
        }

        @media (max-width: 991px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }

            .admin-sidebar.open {
                transform: translateX(0);
            }

            .admin-main {
                margin-left: 0;
            }

            .menu-toggle {
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
        }
    </style>

    @stack('styles')
</head>

<body>
    <div class="admin-wrapper">
        <aside class="admin-sidebar" id="adminSidebar">
            @includeIf('admin.partials.sidebar')

            @if (!View::exists('admin.partials.sidebar'))
                <div class="default-sidebar">
                    <div class="brand-box">
                        <h2>Bolt Sync Admin</h2>
                        <p>Manage connections, schema sync, backups, health checks, replication, and database switching
                            from one panel.</p>
                    </div>

                    <div class="nav-title">Navigation</div>

                    <ul class="nav-menu">
                        @if (Route::has('admin.dashboard'))
                            <li>
                                <a href="{{ route('admin.dashboard') }}"
                                    class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                                    <span>Dashboard</span>
                                </a>
                            </li>
                        @endif

                        @if (Route::has('admin.connections.index'))
                            <li>
                                <a href="{{ route('admin.connections.index') }}"
                                    class="{{ request()->routeIs('admin.connections.*') ? 'active' : '' }}">
                                    <span>Connections</span>
                                </a>
                            </li>
                        @endif

                        @if (Route::has('admin.database-connections.index'))
                            <li>
                                <a href="{{ route('admin.database-connections.index') }}"
                                    class="{{ request()->routeIs('admin.database-connections.*') ? 'active' : '' }}">
                                    <span>Database Connections</span>
                                </a>
                            </li>
                        @endif

                        @if (Route::has('admin.schema.index'))
                            <li>
                                <a href="{{ route('admin.schema.index') }}"
                                    class="{{ request()->routeIs('admin.schema.*') ? 'active' : '' }}">
                                    <span>Schema Diff</span>
                                </a>
                            </li>
                        @endif

                        @if (Route::has('admin.replication.index'))
                            <li>
                                <a href="{{ route('admin.replication.index') }}"
                                    class="{{ request()->routeIs('admin.replication.*') ? 'active' : '' }}">
                                    <span>Replication</span>
                                </a>
                            </li>
                        @endif

                        @if (Route::has('admin.sync-jobs.index'))
                            <li>
                                <a href="{{ route('admin.sync-jobs.index') }}"
                                    class="{{ request()->routeIs('admin.sync-jobs.*') ? 'active' : '' }}">
                                    <span>Manual Sync</span>
                                </a>
                            </li>
                        @endif

                        @if (Route::has('admin.data-compare.index'))
                            <li>
                                <a href="{{ route('admin.data-compare.index') }}"
                                    class="{{ request()->routeIs('admin.data-compare.*') ? 'active' : '' }}">
                                    <span>Data Compare</span>
                                </a>
                            </li>
                        @endif

                        @if (Route::has('admin.backup.index'))
                            <li>
                                <a href="{{ route('admin.backup.index') }}"
                                    class="{{ request()->routeIs('admin.backup.*') ? 'active' : '' }}">
                                    <span>Backups</span>
                                </a>
                            </li>
                        @endif

                        @if (Route::has('admin.health.index'))
                            <li>
                                <a href="{{ route('admin.health.index') }}"
                                    class="{{ request()->routeIs('admin.health.*') ? 'active' : '' }}">
                                    <span>Health</span>
                                </a>
                            </li>
                        @endif

                        @if (Route::has('admin.audit.index'))
                            <li>
                                <a href="{{ route('admin.audit.index') }}"
                                    class="{{ request()->routeIs('admin.audit.*') ? 'active' : '' }}">
                                    <span>Audit Logs</span>
                                </a>
                            </li>
                        @endif

                        @if (Route::has('admin.database-connections.index'))
                            <li>
                                <a href="{{ route('admin.database-connections.index') }}"
                                    class="{{ request()->routeIs('admin.database-connections.*') ? 'active' : '' }}">
                                    <span>Database Connections</span>
                                </a>
                            </li>
                        @endif

                        @if (Route::has('admin.settings.index'))
                            <li>
                                <a href="{{ route('admin.settings.index') }}"
                                    class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                                    <span>Settings</span>
                                </a>
                            </li>
                        @endif
                    </ul>

                    <form method="POST" action="{{ route('admin.logout') }}" class="logout-form">
                        @csrf
                        <button type="submit" class="logout-btn">Logout</button>
                    </form>
                </div>
            @endif
        </aside>

        <div class="admin-main">
            <header class="admin-topbar">
                @includeIf('admin.partials.header')

                @if (!View::exists('admin.partials.header'))
                    <div class="topbar-left">
                        <button class="menu-toggle" id="menuToggle">☰</button>

                        <div class="page-heading">
                            <h1>@yield('page_title', 'Bolt Sync Admin')</h1>
                            <p>@yield('page_subtitle', 'Database sync, replication, backup and health monitoring')</p>
                        </div>
                    </div>

                    <div class="topbar-right">
                        <div class="user-chip">
                            {{ session('admin_name', 'Admin User') }}
                        </div>
                    </div>
                @endif
            </header>

            <main class="admin-content">
                <div class="flash-wrapper">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <strong>Validation Error:</strong>
                            <ul style="margin-top: 8px; padding-left: 18px;">
                                @foreach ($errors->all() as $error)
                                    <li style="margin-bottom: 4px;">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                @yield('content')
            </main>
        </div>
    </div>

    <script>
        const menuToggle = document.getElementById('menuToggle');
        const adminSidebar = document.getElementById('adminSidebar');

        if (menuToggle && adminSidebar) {
            menuToggle.addEventListener('click', function() {
                adminSidebar.classList.toggle('open');
            });
        }
    </script>

    @stack('scripts')
</body>

</html>
