<?php

namespace App\Filament\Resources\Artworks;

use App\Filament\Resources\Artworks\Pages\CreateArtwork;
use App\Filament\Resources\Artworks\Pages\EditArtwork;
use App\Filament\Resources\Artworks\Pages\ListArtworks;
use App\Filament\Resources\Artworks\RelationManagers\CommentsRelationManager;
use App\Models\Artwork;
use App\Models\Category;
use App\Rules\ValidArtworkImage;
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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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
                            ->placeholder('e.g., Vermilion Weather')
                            ->live(onBlur: true),

                        Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(Category::class, 'name'),
                            ])
                            ->editOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(Category::class, 'name', ignoreRecord: true),
                            ]),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(4)
                            ->placeholder('Describe the artwork piece...')
                            ->live(onBlur: true),

                        TextInput::make('alt_text')
                            ->label('Alt Text')
                            ->maxLength(255)
                            ->helperText('Defaults to title if left blank.')
                            ->live(onBlur: true),

                        FileUpload::make('file')
                            ->label(fn (string $operation): string => $operation === 'edit' ? 'Replace Artwork File' : 'Artwork File')
                            ->acceptedFileTypes(array_keys(ValidArtworkImage::ACCEPTED_MIME_TYPES))
                            ->rules([new ValidArtworkImage])
                            ->maxSize(ValidArtworkImage::MAX_SIZE_KB)
                            ->preventFilePathTampering()
                            ->disk('public')
                            ->directory('artworks')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->live()
                            ->helperText(function (string $operation): string {
                                $formats = strtoupper(implode(', ', ValidArtworkImage::ACCEPTED_EXTENSIONS));

                                return ($operation === 'edit'
                                        ? 'Upload a new image only if you want to replace the current artwork image. '
                                        : 'Upload the high-resolution artwork image. ')
                                    ."Accepted formats: {$formats}. Maximum size: "
                                    .(ValidArtworkImage::MAX_SIZE_KB / 1024).' MB.';
                            }),
                    ]),

                Section::make('Artwork Showcase')
                    ->description('Published gallery presentation preview.')
                    ->columnSpan(['default' => 1, 'lg' => 1])
                    ->components([
                        Placeholder::make('showcase')
                            ->hiddenLabel()
                            ->content(function (Get $get, ?Artwork $record): HtmlString {
                                $uploadedFile = $get('file');
                                $rawUrl = null;
                                $fileSize = null;

                                if (! empty($uploadedFile)) {
                                    $firstFile = is_array($uploadedFile) ? reset($uploadedFile) : $uploadedFile;

                                    if ($firstFile instanceof TemporaryUploadedFile) {
                                        try {
                                            $rawUrl = $firstFile->temporaryUrl();
                                            $fileSize = $firstFile->getSize();
                                        } catch (\Throwable) {
                                            $rawUrl = null;
                                        }
                                    } elseif (is_string($firstFile)) {
                                        $rawUrl = Storage::disk('public')->url($firstFile);
                                        try {
                                            $fileSize = Storage::disk('public')->size($firstFile);
                                        } catch (\Throwable) {
                                            $fileSize = null;
                                        }
                                    }
                                }

                                if (blank($rawUrl)) {
                                    $media = $record?->getFirstMedia('images');
                                    $rawUrl = $media?->getUrl() ?? $record?->image_url;
                                    if ($media !== null) {
                                        $fileSize = $media->size;
                                    }
                                }

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

                                $imageUrl = $rawUrl;
                                if (str_starts_with($imageUrl, '/')) {
                                    $imageUrl = request()->getSchemeAndHttpHost().$imageUrl;
                                } elseif (str_contains($imageUrl, 'localhost') && request()->getHost() !== 'localhost') {
                                    $imageUrl = str_replace('localhost', request()->getHost(), $imageUrl);
                                }

                                $titleValue = $get('title') ?? ($record !== null ? $record->title : null) ?? $get('alt_text') ?? ($record !== null ? $record->alt_text : null) ?? 'Untitled Artwork';
                                $categoryId = $get('category_id') ?? ($record !== null ? $record->category_id : null);
                                $categoryName = 'Gallery Piece';
                                if ($categoryId) {
                                    $category = Category::query()->find($categoryId);
                                    if ($category instanceof Category) {
                                        $categoryName = $category->name;
                                    }
                                }
                                $descValue = $get('description') ?? ($record !== null ? $record->description : null) ?? '';
                                $viewCount = number_format((float) ($record !== null ? $record->view_count : 0));
                                $likeCount = number_format((float) ($record !== null ? $record->like_count : 0));

                                $formattedSize = '';
                                if ($fileSize) {
                                    $formattedSize = $fileSize >= 1048576
                                        ? number_format($fileSize / 1048576, 2).' MB'
                                        : number_format($fileSize / 1024, 2).' KB';
                                }

                                $title = e($titleValue);
                                $categoryName = e($categoryName);
                                $description = e($descValue);
                                $size = e($formattedSize);
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
