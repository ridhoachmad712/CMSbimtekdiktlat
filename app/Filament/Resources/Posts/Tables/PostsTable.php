<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['category', 'author']))
            // Draf (tanpa tanggal) di atas, lalu terbaru.
            ->defaultSort(fn (Builder $query) => $query->orderByRaw('published_at is not null')->orderByDesc('published_at')->orderByDesc('id'))
            ->extraAttributes(['class' => 'fi-ta-compact'])
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([25, 50, 100])
            ->searchPlaceholder('Cari judul atau ringkasan…')
            ->recordUrl(fn (Post $record) => PostResource::getUrl('edit', ['record' => $record]))
            ->columns([
                // Daftar ringkas (satu baris per artikel, tanpa gambar) agar lebih banyak artikel terlihat.
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable(['title', 'excerpt'])
                    ->sortable()
                    ->weight('medium')
                    ->limit(90)
                    ->tooltip(fn (Post $record) => mb_strlen($record->title) > 90 ? $record->title : null),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->state(fn (Post $record) => $record->category?->name ?? 'Tanpa kategori')
                    ->color(fn (Post $record) => $record->category_id ? 'gray' : 'danger')
                    ->icon(fn (Post $record) => $record->category_id ? null : Heroicon::OutlinedExclamationTriangle)
                    ->size('sm')
                    ->limit(28)
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Post $record) => $record->status),
                TextColumn::make('published_at')
                    ->label('Terbit')
                    ->dateTime('d M Y')
                    ->size('sm')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('author.name')
                    ->label('Penulis')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                Filter::make('uncategorized')
                    ->label('Tanpa kategori')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->whereNull('category_id')),
                Filter::make('no_image')
                    ->label('Tanpa gambar utama')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->whereNull('image')),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Lihat')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->iconButton()
                    ->tooltip(fn (Post $record) => $record->status === PostStatus::Published ? 'Lihat di situs' : 'Pratinjau')
                    ->url(fn (Post $record) => $record->publicUrl(), shouldOpenInNewTab: true),
                EditAction::make()->iconButton(),
                ActionGroup::make([
                    Action::make('publish')
                        ->label('Terbitkan sekarang')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->visible(fn (Post $record) => $record->status !== PostStatus::Published)
                        ->authorize('update')
                        ->action(fn (Post $record) => $record->update(['published_at' => now()])),
                    Action::make('unpublish')
                        ->label('Jadikan draf')
                        ->icon(Heroicon::OutlinedPencilSquare)
                        ->visible(fn (Post $record) => $record->status !== PostStatus::Draft)
                        ->authorize('update')
                        ->requiresConfirmation()
                        ->modalDescription('Artikel tidak akan tampil di situs sampai diterbitkan lagi.')
                        ->action(fn (Post $record) => $record->update(['published_at' => null])),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('setCategory')
                        ->label('Ubah kategori')
                        ->icon(Heroicon::OutlinedTag)
                        ->authorize('update')
                        ->schema([
                            Select::make('category_id')
                                ->label('Kategori')
                                ->relationship('category', 'name')
                                ->searchable()
                                ->preload()
                                ->placeholder('— Tanpa kategori —'),
                        ])
                        ->action(function (Collection $records, array $data) {
                            Post::whereKey($records->modelKeys())->update(['category_id' => $data['category_id'] ?? null]);
                            Notification::make()->title($records->count().' artikel diperbarui')->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('publish')
                        ->label('Terbitkan sekarang')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->authorize('update')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $drafts = $records->filter(fn (Post $post) => $post->status !== PostStatus::Published);
                            Post::whereKey($drafts->modelKeys())->update(['published_at' => now()]);
                            Notification::make()->title($drafts->count().' artikel diterbitkan')->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('unpublish')
                        ->label('Jadikan draf')
                        ->icon(Heroicon::OutlinedPencilSquare)
                        ->authorize('update')
                        ->requiresConfirmation()
                        ->modalDescription('Artikel terpilih tidak akan tampil di situs sampai diterbitkan lagi.')
                        ->action(function (Collection $records) {
                            Post::whereKey($records->modelKeys())->update(['published_at' => null]);
                            Notification::make()->title($records->count().' artikel dijadikan draf')->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
