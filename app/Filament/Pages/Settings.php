<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Settings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Pengaturan Situs';

    protected static ?string $title = 'Pengaturan Situs';

    protected static ?string $slug = 'settings';

    /** Key Setting yang dikelola halaman ini beserta nilai bawaannya. */
    public const KEYS = [
        'site_name' => '',
        'site_tagline' => '',
        'logo' => '',
        'schedule_pdf' => '',
        'whatsapp_number' => '',
        'phone' => '',
        'email' => '',
        'address' => '',
        'social_links' => ['facebook' => '', 'instagram' => '', 'youtube' => ''],
        'google_site_verification' => '',
        'bing_site_verification' => '',
        'ga4_measurement_id' => '',
    ];

    /** Kolom verifikasi boleh diisi tag meta lengkap; yang disimpan hanya nilai content-nya. */
    public static function verificationCode(?string $value): string
    {
        $value = trim((string) $value);
        if (preg_match('/content\s*=\s*["\']([^"\']+)["\']/i', $value, $m)) {
            $value = $m[1];
        }

        return preg_replace('/[^A-Za-z0-9_\-]/', '', $value);
    }

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasRole(UserRole::SuperAdmin, UserRole::Admin);
    }

    public function mount(): void
    {
        $stored = Setting::query()->whereIn('key', array_keys(self::KEYS))->pluck('value', 'key');

        $this->form->fill(collect(self::KEYS)
            ->map(fn ($default, $key) => is_array($default)
                ? array_merge($default, (array) ($stored[$key] ?? []))
                : ($stored[$key] ?? $default))
            ->all());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Identitas Situs')
                    ->columns(2)
                    ->schema([
                        TextInput::make('site_name')
                            ->label('Nama Situs / Lembaga')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('site_tagline')
                            ->label('Tagline')
                            ->maxLength(255),
                        FileUpload::make('logo')
                            ->label('Logo')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->directory('branding')
                            ->maxSize(1024)
                            ->helperText('PNG/SVG berlatar transparan, tinggi ±72px. Tampil di header dan data Google. Kosongkan untuk logo bawaan.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Jadwal')
                    ->schema([
                        FileUpload::make('schedule_pdf')
                            ->label('PDF Jadwal & Tempat Pelaksanaan')
                            ->acceptedFileTypes(['application/pdf'])
                            ->disk('public')
                            ->visibility('public')
                            ->directory('jadwal')
                            ->maxSize(10240)
                            ->helperText('Ditampilkan sebagai tombol "Unduh jadwal (PDF)" di halaman Jadwal.'),
                    ]),
                Section::make('Kontak')
                    ->columns(2)
                    ->schema([
                        TextInput::make('whatsapp_number')
                            ->label('Nomor WhatsApp')
                            ->tel()
                            ->required()
                            ->regex('/^\+?[0-9\s\-]{9,20}$/')
                            ->helperText('Dipakai tombol WhatsApp di situs, invoice, dan notifikasi WA admin. Contoh: 6281234567890.'),
                        TextInput::make('phone')
                            ->label('Telepon Kantor')
                            ->tel()
                            ->maxLength(30),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255)
                            ->helperText('Ditampilkan di footer dan menerima notifikasi pendaftaran baru.'),
                        Textarea::make('address')
                            ->label('Alamat')
                            ->rows(3)
                            ->maxLength(500),
                    ]),
                Section::make('Media Sosial')
                    ->columns(3)
                    ->schema([
                        TextInput::make('social_links.facebook')
                            ->label('Facebook')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('social_links.instagram')
                            ->label('Instagram')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('social_links.youtube')
                            ->label('YouTube')
                            ->url()
                            ->maxLength(255),
                    ]),
                Section::make('SEO & Google')
                    ->description('Untuk verifikasi kepemilikan situs dan statistik pengunjung. Kosongkan bila tidak dipakai.')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextInput::make('google_site_verification')
                            ->label('Kode Verifikasi Google Search Console')
                            ->placeholder('<meta name="google-site-verification" content="..."> atau kodenya saja')
                            ->maxLength(300)
                            ->helperText('Search Console → Tambahkan properti → Awalan URL → metode "Tag HTML". Tempel tag atau kodenya di sini, simpan, lalu klik Verifikasi di Search Console.')
                            ->columnSpanFull(),
                        TextInput::make('bing_site_verification')
                            ->label('Kode Verifikasi Bing Webmaster (opsional)')
                            ->placeholder('<meta name="msvalidate.01" content="..."> atau kodenya saja')
                            ->maxLength(300),
                        TextInput::make('ga4_measurement_id')
                            ->label('Google Analytics 4 — Measurement ID (opsional)')
                            ->placeholder('G-XXXXXXXXXX')
                            ->regex('/^G-[A-Z0-9]{4,20}$/')
                            ->validationMessages(['regex' => 'Format Measurement ID: G-XXXXXXXXXX'])
                            ->maxLength(30),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Simpan')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $data = $this->form->getState();

        foreach (array_keys(self::KEYS) as $key) {
            $value = $data[$key] ?? self::KEYS[$key];
            if (is_array(self::KEYS[$key])) {
                $value = array_map(fn ($v) => (string) $v, array_merge(self::KEYS[$key], (array) $value));
            }
            if (in_array($key, ['google_site_verification', 'bing_site_verification'], true)) {
                $value = static::verificationCode($value);
            }

            // Lewat model agar event `saved` menghapus cache 'settings'.
            Setting::updateOrCreate(['key' => $key], ['value' => $value ?? '']);
        }

        Notification::make()
            ->title('Pengaturan disimpan')
            ->success()
            ->send();
    }
}
