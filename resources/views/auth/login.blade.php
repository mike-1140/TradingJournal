<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trading Journal — Anmelden</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">

<div class="card shadow-sm" style="width: 100%; max-width: 380px;">
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <i class="bi bi-graph-up-arrow text-primary" style="font-size: 2rem;"></i>
            <h5 class="mt-2 mb-0">Trading Journal</h5>
            <small class="text-muted">Bitte anmelden</small>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger py-2 small mb-3">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label">E-Mail</label>
                <input type="email"
                       class="form-control @error('email') is-invalid @enderror"
                       id="email" name="email"
                       value="{{ old('email') }}"
                       autofocus required autocomplete="email">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Passwort</label>
                <input type="password"
                       class="form-control"
                       id="password" name="password"
                       required autocomplete="current-password">
            </div>
            <div class="mb-4 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label small" for="remember">Angemeldet bleiben</label>
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-box-arrow-in-right me-1"></i> Anmelden
            </button>
        </form>
    </div>
</div>

</body>
</html>
