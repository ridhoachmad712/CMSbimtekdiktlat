@include('errors.standalone', [
    'code' => $exception->getStatusCode(),
    'heading' => $exception->getStatusCode() === 503 ? 'Sedang dalam pemeliharaan' : 'Terjadi gangguan pada server',
    'message' => $exception->getStatusCode() === 503
        ? 'Situs sedang diperbarui dan akan segera kembali. Terima kasih atas kesabaran Anda.'
        : 'Maaf, terjadi kesalahan di sisi kami. Silakan coba beberapa saat lagi atau hubungi kami via WhatsApp.',
])
