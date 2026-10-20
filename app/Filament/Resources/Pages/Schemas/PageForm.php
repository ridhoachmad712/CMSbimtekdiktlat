<?php

namespace App\Filament\Resources\Pages\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Judul')
                    ->required(),
                TextInput::make('slug')
                    ->unique(ignoreRecord: true)
                    ->helperText('Kosongkan untuk dibuat otomatis dari judul/nama.'),
                RichEditor::make('content')
                    ->label('Isi Halaman')
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsVisibility('public')
                    ->fileAttachmentsDirectory('editor')
                    ->columnSpanFull(),
                Toggle::make('is_published')
                    ->label('Terbit')
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
