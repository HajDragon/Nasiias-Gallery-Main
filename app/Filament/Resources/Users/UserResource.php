<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'Users';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account details')
                    ->description('Basic information shown on the gallery and in emails.')
                    ->columns(['default' => 1, 'lg' => 2])
                    ->components([
                        TextInput::make('display_name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(['default' => 1, 'lg' => 1]),

                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->columnSpan(['default' => 1, 'lg' => 1]),

                        Select::make('locale')
                            ->label('Language')
                            ->options((array) config('app.available_locales'))
                            ->placeholder('Use the site default')
                            ->nullable(),

                        Toggle::make('notify_on_new_artwork')
                            ->label('New artwork emails')
                            ->helperText('Receive an email whenever a new artwork is published.'),
                    ]),

                Section::make('Role and notifications')
                    ->description('Admins can manage the gallery. Super admins are managed outside this screen.')
                    ->columns(['default' => 1, 'lg' => 2])
                    ->components([
                        Select::make('role')
                            ->label('Role')
                            ->options(fn (?User $record): array => self::roleOptions($record))
                            ->required()
                            ->live()
                            ->default(UserRole::User->value)
                            ->disabled(fn (?User $record): bool => self::cannotChangeRole($record))
                            ->helperText(function (?User $record): string {
                                if ($record?->role === UserRole::SuperAdmin) {
                                    return 'The super admin role cannot be changed here.';
                                }

                                if ($record !== null && $record->is(Auth::user())) {
                                    return 'You cannot change your own role.';
                                }

                                return 'Users can browse and interact with the gallery. Admins can also manage artworks, categories and users with this screen.';
                            }),

                        Toggle::make('notify_on_contact_message')
                            ->label('Contact form emails')
                            ->helperText('Receive the contact form messages sent from the gallery.')
                            ->visible(fn (Get $get): bool => in_array($get('role'), [UserRole::Admin->value, UserRole::SuperAdmin->value], true))
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->imageSize(40),

                TextColumn::make('display_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state): string => $state->label())
                    ->color(fn (UserRole $state): string => $state->color())
                    ->sortable(),

                IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->toggleable(),

                TextColumn::make('likes_count')
                    ->counts('likes')
                    ->label('Likes')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('comments_count')
                    ->counts('comments')
                    ->label('Comments')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->options(self::roleOptions()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    /**
     * Roles the current super admin may assign through the panel.
     *
     * Super admin is only listed for records that already hold it so their
     * existing role still renders in the select. It is never saved from this
     * screen because EditUser keeps the stored role for those records.
     *
     * @return array<string, string>
     */
    public static function roleOptions(?User $record = null): array
    {
        $roles = [UserRole::User, UserRole::Admin];

        if ($record?->role === UserRole::SuperAdmin) {
            $roles[] = UserRole::SuperAdmin;
        }

        return collect($roles)
            ->mapWithKeys(fn (UserRole $role): array => [$role->value => $role->label()])
            ->all();
    }

    public static function cannotChangeRole(?User $record): bool
    {
        if ($record === null) {
            return false;
        }

        return $record->role === UserRole::SuperAdmin || $record->is(Auth::user());
    }
}
