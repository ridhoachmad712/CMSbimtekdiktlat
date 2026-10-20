<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tulis Artikel'),
        ];
    }

    /** Tab status + "Tanpa kategori" dengan jumlah, untuk pekerjaan harian admin. */
    public function getTabs(): array
    {
        $tabs = ['all' => Tab::make('Semua')->badge(Post::count())];

        foreach (PostStatus::cases() as $status) {
            $tabs[$status->value] = Tab::make($status->getLabel())
                ->icon($status->getIcon())
                ->badge(Post::withStatus($status)->count())
                ->badgeColor($status->getColor())
                ->modifyQueryUsing(fn (Builder $query) => $query->withStatus($status));
        }

        $tabs['uncategorized'] = Tab::make('Tanpa Kategori')
            ->badge(Post::whereNull('category_id')->count())
            ->badgeColor('danger')
            ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('category_id'));

        return $tabs;
    }
}
