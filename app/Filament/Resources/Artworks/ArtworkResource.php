<?php

namespace App\Filament\Resources\Artworks;

use App\Filament\Resources\Artworks\Pages\CreateArtwork;
use App\Filament\Resources\Artworks\Pages\EditArtwork;
use App\Filament\Resources\Artworks\Pages\ListArtworks;
use App\Filament\Resources\Artworks\RelationManagers\CommentsRelationManager;
use App\Models\Artwork;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class ArtworkResource extends Resource
{
    protected static ?string $model = Artwork::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $navigationLabel = 'Artworks';

    protected static ?string $modelLabel = 'Artwork';

    protected static ?string $pluralModelLabel = 'Artworks';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 3])
            ->components([
                Section::make('Artwork Details')
                    ->description('Upload artwork details and image file.')
                    ->columnSpan(['default' => 1, 'lg' => 2])
                    ->components([
                        TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Vermilion Weather'),

                        Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->editOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                            ]),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(4)
                            ->placeholder('Describe the artwork piece...'),

                        TextInput::make('alt_text')
                            ->label('Alt Text')
                            ->maxLength(255)
                            ->helperText('Defaults to title if left blank.'),

                        FileUpload::make('file')
                            ->label(fn (string $operation): string => $operation === 'edit' ? 'Replace Artwork File' : 'Artwork File')
                            ->image()
                            ->disk('public')
                            ->directory('artworks')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->helperText(fn (string $operation): string => $operation === 'edit'
                                ? 'Upload a new image only if you want to replace the current artwork image.'
                                : 'Upload the high-resolution artwork image.'),
                    ]),

                Section::make('Artwork Showcase')
                    ->description('Published gallery presentation preview.')
                    ->columnSpan(['default' => 1, 'lg' => 1])
                    ->components([
                        Placeholder::make('showcase')
                            ->hiddenLabel()
                            ->content(function (?Artwork $record): HtmlString {
                                $media = $record?->getFirstMedia('images');
                                $rawUrl = $media?->getUrl() ?? $record?->image_url;

                                if (blank($rawUrl)) {
                                    return new HtmlString(<<<'HTML'
                                        <div class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gray-300 dark:border-gray-700 p-8 text-center bg-[#f0f7ff]/50 dark:bg-gray-900/50">
                                            <div class="h-16 w-16 mb-3 rounded-full bg-[#98c1d9]/20 flex items-center justify-center text-[#293241] dark:text-[#98c1d9]">
                                                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <h4 class="font-display text-base font-semibold text-[#293241] dark:text-[#f0f7ff]">Artwork Showcase</h4>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 max-w-[220px]">Upload an image file on the left to preview the artwork showcase here.</p>
                                        </div>
                                    HTML);
                                }

                                /** @var Artwork $record */
                                $imageUrl = $rawUrl;
                                if (str_starts_with($imageUrl, '/')) {
                                    $imageUrl = request()->getSchemeAndHttpHost().$imageUrl;
                                } elseif (str_contains($imageUrl, 'localhost') && request()->getHost() !== 'localhost') {
                                    $imageUrl = str_replace('localhost', request()->getHost(), $imageUrl);
                                }

                                $title = e($record->title ?? $record->alt_text ?? 'Untitled Artwork');
                                $categoryName = e($record->category->name ?? 'Gallery Piece');
                                $description = e($record->description ?? '');
                                $viewCount = number_format((float) $record->view_count);
                                $likeCount = number_format((float) $record->like_count);
                                $size = e($media !== null ? $media->human_readable_size : '');
                                $src = e($imageUrl);

                                $descBlock = $description !== ''
                                    ? "<p style=\"margin: 0.5rem 0 0 0; font-size: 0.8rem; line-height: 1.45; color: rgba(240,247,255,0.75);\">{$description}</p>"
                                    : '';

                                $sizeBlock = $size !== ''
                                    ? "<span style=\"font-size: 0.7rem; color: rgba(240,247,255,0.5);\">{$size}</span>"
                                    : '';

                                return new HtmlString(<<<HTML
                                    <div style="background-color: #293241; border-radius: 1rem; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(41,50,65,0.45); border: 1px solid rgba(240,247,255,0.15);">
                                        <div style="background-color: #1a202c; padding: 0.75rem; display: flex; align-items: center; justify-content: center; min-height: 260px; max-height: 480px; overflow: hidden;">
                                            <img src="{$src}" alt="{$title}" style="max-height: 440px; width: 100%; object-fit: contain; border-radius: 0.5rem;" />
                                        </div>
                                        <div style="padding: 1.25rem; color: #f0f7ff;">
                                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.5rem;">
                                                <span style="display: inline-block; padding: 0.25rem 0.65rem; border-radius: 9999px; background-color: rgba(152,193,217,0.2); color: #98c1d9; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em;">
                                                    {$categoryName}
                                                </span>
                                                <div style="display: flex; align-items: center; gap: 0.75rem; font-size: 0.75rem; color: rgba(240,247,255,0.7);">
                                                    <span>&#128065; {$viewCount}</span>
                                                    <span style="color: #e63946; font-weight: 600;">&hearts; {$likeCount}</span>
                                                </div>
                                            </div>
                                            <h3 style="font-family: 'Magilio', Georgia, serif; font-size: 1.35rem; color: #f0f7ff; margin: 0; line-height: 1.25;">
                                                {$title}
                                            </h3>
                                            {$descBlock}
                                            <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid rgba(240,247,255,0.15); display: flex; align-items: center; justify-content: space-between;">
                                                <a href="{$src}" target="_blank" rel="noopener noreferrer" style="color: #98c1d9; font-size: 0.75rem; text-decoration: underline; font-weight: 600;">
                                                    Open full resolution &nearr;
                                                </a>
                                                {$sizeBlock}
                                            </div>
                                        </div>
                                    </div>
                                HTML);
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_url')
                    ->label('Artwork')
                    ->state(function (Artwork $record): ?string {
                        $url = $record->getFirstMediaUrl('images', 'thumbnail')
                            ?: $record->getFirstMediaUrl('images')
                            ?: $record->image_url;

                        if (blank($url)) {
                            return null;
                        }

                        if (str_starts_with($url, '/')) {
                            return request()->getSchemeAndHttpHost().$url;
                        }

                        if (str_contains($url, 'localhost') && request()->getHost() !== 'localhost') {
                            return str_replace('localhost', request()->getHost(), $url);
                        }

                        return $url;
                    })
                    ->square(),

                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->default(fn (Artwork $record): string => $record->alt_text),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('view_count')
                    ->label('Views')
                    ->sortable(),

                TextColumn::make('like_count')
                    ->label('Likes')
                    ->sortable(),

                TextColumn::make('comments_count')
                    ->counts('comments')
                    ->label('Comments')
                    ->badge()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            CommentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArtworks::route('/'),
            'create' => CreateArtwork::route('/create'),
            'edit' => EditArtwork::route('/{record}/edit'),
        ];
    }
}
