<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <script>
        (function () {
            try {
                var queryTheme = new URLSearchParams(window.location.search).get('theme');
                if (queryTheme === 'light' || queryTheme === 'dark') {
                    localStorage.setItem('veestore.theme', queryTheme);
                }
                var stored = localStorage.getItem('veestore.theme');
                var theme = stored === 'light' || stored === 'dark'
                    ? stored
                    : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.dataset.theme = theme;
                document.documentElement.classList.toggle('dark', theme === 'dark');
            } catch (error) {
                document.documentElement.dataset.theme = 'light';
            }
        })();
    </script>

    <title inertia>VeeStore Backoffice</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo.png') }}" />
    <link rel="apple-touch-icon" href="{{ asset('assets/img/logo.png') }}" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    @vite(['resources/js/app.ts'])
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
