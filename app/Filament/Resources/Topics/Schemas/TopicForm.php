<?php

namespace App\Filament\Resources\Topics\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TopicForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('title')
                    ->label('Judul Materi')
                    ->required(),
                TextInput::make('slug')
                    ->unique(ignoreRecord: true)
                    ->helperText('Kosongkan untuk dibuat otomatis dari judul/nama.'),
                Textarea::make('summary')
                    ->label('Ringkasan')
                    ->columnSpanFull(),
                RichEditor::make('content')
                    ->label('Uraian Materi')
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsVisibility('public')
                    ->fileAttachmentsDirectory('editor')
                    ->columnSpanFull(),
                Textarea::make('legal_basis')
                    ->label('Dasar Hukum')
                    ->helperText('Satu regulasi per baris.')
                    ->columnSpanFull(),
                Textarea::make('target_participants')
                    ->label('Sasaran Peserta')
                    ->columnSpanFull(),
                Toggle::make('is_featured')
                    ->label('Unggulan')
                    ->required(),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->required(),
                TextInput::make('meta_title')
                    ->label('Meta Title (SEO)')
                    ->maxLength(70),
                TextInput::make('meta_description')
                    ->label('Meta Description (SEO)')
                    ->maxLength(160),
            ]);
    }
}
