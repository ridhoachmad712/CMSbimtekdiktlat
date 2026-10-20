@include('errors.standalone', [
    'code' => $exception->getStatusCode(),
    'heading' => match ($exception->getStatusCode()) {
        403 => 'Akses ditolak',
        419 => 'Sesi telah berakhir',
        429 => 'Terlalu banyak permintaan',
        default => 'Permintaan tidak dapat diproses',
    },
    'message' => match ($exception->getStatusCode()) {
        403 => 'Anda tidak memiliki izin untuk membuka halaman ini.',
        419 => 'Halaman terlalu lama dibuka. Muat ulang halaman lalu coba lagi.',
        429 => 'Mohon tunggu beberapa saat sebelum mencoba lagi.',
        default => 'Terjadi kesalahan pada permintaan Anda.',
    },
])
