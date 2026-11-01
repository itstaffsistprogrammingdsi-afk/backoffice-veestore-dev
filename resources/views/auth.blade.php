<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
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

    <title>VeeStore Backoffice</title>
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/materialdesignicons.css') }}" />
    @vite(['resources/js/app.ts'])
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
