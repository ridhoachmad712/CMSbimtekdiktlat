{{-- Halaman error mandiri: tanpa query DB dan tanpa Vite agar tetap tampil saat keduanya bermasalah. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $code }} — {{ $heading }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f8fafc; color: #1e293b; font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; }
        main { max-width: 32rem; padding: 2rem 1.25rem; text-align: center; }
        .code { font-size: .875rem; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: #840b2f; }
        h1 { margin: .75rem 0 0; font-size: 1.875rem; color: #0f172a; }
        p { margin: 1rem 0 0; line-height: 1.6; color: #475569; }
        a { display: inline-block; margin-top: 2rem; padding: .7rem 1.25rem; border-radius: .5rem; background: #840b2f; color: #fff; font-weight: 600; text-decoration: none; }
        a:hover { background: #6c0a28; }
    </style>
</head>
<body>
    <main>
        <div class="code">Error {{ $code }}</div>
        <h1>{{ $heading }}</h1>
        <p>{{ $message }}</p>
        <a href="{{ url('/') }}">Kembali ke Beranda</a>
    </main>
</body>
</html>
