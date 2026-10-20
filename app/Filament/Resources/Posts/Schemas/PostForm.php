<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\PostStatus;
use App\Models\Post;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        Group::make()
                            ->columnSpan(['lg' => 2])
                            ->schema([
                                Section::make('Isi Artikel')
                                    ->schema([
                                        TextInput::make('title')
                                            ->label('Judul')
                                            ->required()
                                            ->maxLength(250)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (string $operation, ?string $old, ?string $state, Get $get, Set $set) {
                                                // Slug mengikuti judul selama belum diubah manual (hanya saat membuat).
                                                if ($operation === 'create' && (blank($get('slug')) || $get('slug') === Str::slug((string) $old))) {
                                                    $set('slug', Str::slug((string) $state));
                                                }
                                            }),
                                        TextInput::make('slug')
                                            ->label('Alamat (URL)')
                                            ->prefix(fn () => Str::after(route('posts.index'), '://').'/')
                                            ->unique(ignoreRecord: true)
                                            ->alphaDash()
                                            ->helperText('Dibuat otomatis dari judul. Hindari mengubahnya setelah artikel terbit.'),
                                        Textarea::make('excerpt')
                                            ->label('Ringkasan')
                                            ->rows(3)
                                            ->maxLength(300)
                                            ->live(debounce: 600)
                                            ->hint(fn (?string $state) => mb_strlen((string) $state).'/300')
                                            ->helperText('Tampil di daftar artikel dan sebagai deskripsi di Google bila Meta Description kosong.'),
                                        RichEditor::make('content')
                                            ->label('Isi')
                                            ->fileAttachmentsDisk('public')
                                            ->fileAttachmentsVisibility('public')
                                            ->fileAttachmentsDirectory('editor')
                                            ->extraInputAttributes(['style' => 'min-height: 28rem'])
                                            ->helperText('Gunakan "Heading 2" untuk subjudul: otomatis menjadi Daftar Isi di halaman artikel.'),
                                    ]),
                                Section::make('SEO (Google)')
                                    ->description('Opsional — bila kosong, judul dan ringkasan yang dipakai.')
                                    ->collapsible()
                                    ->collapsed()
                                    ->schema([
                                        TextInput::make('meta_title')
                                            ->label('Meta Title')
                                            ->maxLength(70)
                                            ->live(debounce: 600)
                                            ->hint(fn (?string $state) => mb_strlen((string) $state).'/60')
                                            ->hintColor(fn (?string $state) => mb_strlen((string) $state) > 60 ? 'danger' : 'gray'),
                                        Textarea::make('meta_description')
                                            ->label('Meta Description')
                                            ->rows(2)
                                            ->maxLength(170)
                                            ->live(debounce: 600)
                                            ->hint(fn (?string $state) => mb_strlen((string) $state).'/160')
                                            ->hintColor(fn (?string $state) => mb_strlen((string) $state) > 160 ? 'danger' : 'gray'),
                                        Html::make(fn (Get $get) => static::googlePreview($get)),
                                    ]),
                            ]),
                        Group::make()
                            ->columnSpan(['lg' => 1])
                            ->schema([
                                Section::make('Publikasi')
                                    ->schema([
                                        ToggleButtons::make('status')
                                            ->label('Status')
                                            ->options(PostStatus::class)
                                            ->inline()
                                            ->default(PostStatus::Draft)
                                            ->dehydrated(false)
                                            ->live()
                                            ->afterStateHydrated(function (ToggleButtons $component, ?Post $record) {
                                                if ($record) {
                                                    $component->state($record->status);
                                                }
                                            })
                                            ->afterStateUpdated(function (mixed $state, Get $get, Set $set) {
                                                $date = filled($get('published_at')) ? now()->parse($get('published_at')) : null;
                                                $set('published_at', match (static::status($state)) {
                                                    PostStatus::Published => ($date && $date->isPast()) ? $date->toDateTimeString() : now()->toDateTimeString(),
                                                    PostStatus::Scheduled => ($date && $date->isFuture()) ? $date->toDateTimeString() : now()->addDay()->setTime(8, 0)->toDateTimeString(),
                                                    default => null,
                                                });
                                            }),
                                        DateTimePicker::make('published_at')
                                            ->label(fn (Get $get) => static::status($get('status')) === PostStatus::Scheduled ? 'Terbit pada' : 'Tanggal terbit')
                                            ->native(false)
                                            ->seconds(false)
                                            ->displayFormat('d M Y H:i')
                                            ->visible(fn (Get $get) => static::status($get('status')) !== PostStatus::Draft)
                                            ->required(fn (Get $get) => static::status($get('status')) !== PostStatus::Draft)
                                            ->rules(fn (Get $get) => static::status($get('status')) === PostStatus::Scheduled ? ['after:now'] : [])
                                            ->validationMessages(['after' => 'Tanggal terjadwal harus di masa depan.'])
                                            // Tetap disimpan (null) saat disembunyikan karena status Draf.
                                            ->dehydratedWhenHidden(),
                                        Select::make('user_id')
                                            ->label('Penulis')
                                            ->relationship('author', 'name')
                                            ->default(fn () => auth()->id()),
                                    ]),
                                Section::make('Kategori & Gambar')
                                    ->schema([
                                        Select::make('category_id')
                                            ->label('Kategori')
                                            ->relationship('category', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->createOptionForm([
                                                TextInput::make('name')->label('Nama kategori baru')->required(),
                                            ])
                                            ->helperText('Artikel tanpa kategori tidak muncul di arsip kategori.'),
                                        FileUpload::make('image')
                                            ->label('Gambar Utama')
                                            ->image()
                                            ->imageEditor()
                                            ->imageEditorAspectRatioOptions(['16:9', '4:3', '1:1'])
                                            ->maxSize(4096)
                                            ->disk('public')
                                            ->visibility('public')
                                            ->directory('posts')
                                            ->helperText('Ideal 1200×675 piksel (16:9), maks. 4 MB. Juga dipakai saat artikel dibagikan ke WhatsApp/Facebook.'),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    /** Status dari ToggleButtons bisa berupa enum atau string. */
    protected static function status(mixed $state): PostStatus
    {
        return $state instanceof PostStatus ? $state : (PostStatus::tryFrom((string) $state) ?? PostStatus::Draft);
    }

    /** Pratinjau sederhana tampilan hasil pencarian Google. */
    protected static function googlePreview(Get $get): HtmlString
    {
        $title = $get('meta_title') ?: $get('title') ?: 'Judul artikel';
        $description = $get('meta_description') ?: $get('excerpt') ?: 'Ringkasan artikel akan tampil di sini.';
        $url = route('posts.index').'/'.($get('slug') ?: 'alamat-artikel');

        return new HtmlString('<div style="border:1px solid rgba(127,127,127,.3);border-radius:.75rem;padding:.75rem 1rem;font-family:Arial,sans-serif">'
            .'<div style="font-size:.75rem;opacity:.7">'.e(Str::after($url, '://')).'</div>'
            .'<div style="color:#1a0dab;font-size:1.05rem;margin:.15rem 0">'.e(Str::limit($title, 60)).'</div>'
            .'<div style="font-size:.85rem;opacity:.85">'.e(Str::limit($description, 160)).'</div>'
            .'</div>');
    }
}
