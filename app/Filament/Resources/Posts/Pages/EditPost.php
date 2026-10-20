<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\PostResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label(fn () => $this->getRecord()->status === PostStatus::Published ? 'Lihat di situs' : 'Pratinjau')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn () => $this->getRecord()->publicUrl(), shouldOpenInNewTab: true),
            DeleteAction::make(),
        ];
    }

    /** Tetap di halaman edit setelah menyimpan, agar bisa lanjut menyunting. */
    protected function getRedirectUrl(): ?string
    {
        return null;
    }
}
