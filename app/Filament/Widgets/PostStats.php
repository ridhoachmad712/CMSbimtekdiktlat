<?php

namespace App\Filament\Widgets;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PostStats extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Artikel';

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Post::class) ?? false;
    }

    protected function getStats(): array
    {
        $list = fn (string $tab) => PostResource::getUrl('index', ['tab' => $tab]);

        return [
            Stat::make('Terbit Bulan Ini', Post::published()->where('published_at', '>=', now()->startOfMonth())->count())
                ->description('Tulis artikel baru →')
                ->color('success')
                ->url(PostResource::getUrl('create')),
            Stat::make('Draf', Post::withStatus(PostStatus::Draft)->count())
                ->description('Belum tampil di situs')
                ->url($list(PostStatus::Draft->value)),
            Stat::make('Terjadwal', Post::withStatus(PostStatus::Scheduled)->count())
                ->description('Terbit otomatis')
                ->color('warning')
                ->url($list(PostStatus::Scheduled->value)),
            Stat::make('Tanpa Kategori', Post::whereNull('category_id')->count())
                ->description('Rapikan kategorinya →')
                ->color('danger')
                ->url($list('uncategorized')),
        ];
    }
}
