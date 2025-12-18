<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Dev Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    {{-- CSS/JS compilados con Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="{{ route('entries.index') }}">Daily Dev Tracker</a>
        <div class="ms-auto">
            <button id="themeToggle" class="btn btn-sm btn-outline-light" type="button" aria-label="Cambiar tema">
                <span class="d-inline" data-light>🌙</span>
                <span class="d-none" data-dark>☀️</span>
            </button>
        </div>
    </div>

    </nav>
<main class="container">
    @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
// Toggle de tema usando Bootstrap 5.3 data-bs-theme con persistencia en localStorage
(function() {
  const root = document.documentElement;
  const stored = localStorage.getItem('theme');
  const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  const initial = stored || (prefersDark ? 'dark' : 'light');
  root.setAttribute('data-bs-theme', initial);

  function updateButton(theme) {
    const btn = document.getElementById('themeToggle');
    if (!btn) return;
    const lightSpan = btn.querySelector('[data-light]');
    const darkSpan = btn.querySelector('[data-dark]');
    if (theme === 'dark') {
      lightSpan.classList.add('d-none');
      darkSpan.classList.remove('d-none');
    } else {
      lightSpan.classList.remove('d-none');
      darkSpan.classList.add('d-none');
    }
  }

  updateButton(initial);

  document.getElementById('themeToggle')?.addEventListener('click', function() {
    const current = root.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-bs-theme', next);
    localStorage.setItem('theme', next);
    updateButton(next);
  });
})();
</script>
</body>
</html>
