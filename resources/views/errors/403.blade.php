<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <title>Akses Ditolak - VeeStore</title>
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('veestore.theme');
                var theme = stored === 'light' || stored === 'dark'
                    ? stored
                    : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.dataset.theme = theme;
            } catch (error) {
                document.documentElement.dataset.theme = 'light';
            }
        })();
    </script>
    <style>
        :root {
            --bg: #f7f8fc;
            --surface: #ffffff;
            --ink: #171b26;
            --muted: #5f6675;
            --border: #e5e7f0;
            --accent: #3b4cf0;
            --accent-ink: #ffffff;
            --danger: #b91c1c;
            --danger-soft: #fee2e2;
        }

        html[data-theme="dark"] {
            --bg: #0d1322;
            --surface: #151c30;
            --ink: #f5f7ff;
            --muted: #b7bed3;
            --border: #2a3455;
            --accent: #8fa2ff;
            --accent-ink: #111633;
            --danger: #fca5a5;
            --danger-soft: #3a1519;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 1.5rem;
            background: var(--bg);
            color: var(--ink);
            font-family: Inter, system-ui, -apple-system, "Segoe UI", sans-serif;
        }

        .error-card {
            width: min(100%, 520px);
            padding: clamp(1.5rem, 5vw, 2.5rem);
            border: 1px solid var(--border);
            border-radius: 1rem;
            background: var(--surface);
            text-align: center;
        }

        .error-icon {
            display: grid;
            place-items: center;
            width: 64px;
            height: 64px;
            margin: 0 auto 1rem;
            border-radius: 50%;
            background: var(--danger-soft);
            color: var(--danger);
        }

        .error-code {
            margin: 0;
            font-size: clamp(2.5rem, 12vw, 4rem);
            font-weight: 800;
            line-height: 1;
        }

        .error-title {
            margin: 0.75rem 0 0.4rem;
            font-size: clamp(1.15rem, 4vw, 1.4rem);
        }

        .error-description {
            margin: 0;
            color: var(--muted);
            font-size: 0.95rem;
            line-height: 1.55;
        }

        .error-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.6rem;
            margin-top: 1.5rem;
        }

        .error-actions a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 0.65rem 1rem;
            border: 1px solid var(--border);
            border-radius: 0.55rem;
            color: var(--ink);
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
        }

        .error-actions a:hover,
        .error-actions a:focus-visible {
            border-color: var(--accent);
        }

        .error-actions .primary {
            border-color: var(--accent);
            background: var(--accent);
            color: var(--accent-ink);
        }

        .error-actions a:focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
        }

        @media (max-width: 420px) {
            .error-actions a {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <main class="error-card">
        <div class="error-icon" aria-hidden="true">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="4" y="10" width="16" height="10" rx="2"></rect>
                <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
            </svg>
        </div>
        <p class="error-code">403</p>
        <h1 class="error-title">Akses Ditolak</h1>
        <p class="error-description">
            {{ $message ?? 'Anda tidak memiliki izin untuk mengakses halaman ini.' }}<br>
            Silakan hubungi admin untuk meminta akses.
        </p>
        <div class="error-actions">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}">Kembali</a>
            <a href="{{ route('dashboard') }}" class="primary">Ke Dashboard</a>
        </div>
    </main>
</body>
</html>
