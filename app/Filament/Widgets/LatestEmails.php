<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\ContactMessage;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestEmails extends TableWidget
{
    protected static ?int $sort = 3;

    protected static ?bool $isLazyLoaded = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(ContactMessage::query()->latest())
            ->heading('Latest emails')
            ->description('Messages received through the contact form.')
            ->columns([
                TextColumn::make('sender_name')
                    ->label('From')
                    ->description(fn (ContactMessage $record): string => $record->sender_email)
                    ->searchable(['sender_name', 'sender_email'])
                    ->sortable(),

                TextColumn::make('message')
                    ->label('Message')
                    ->limit(80)
                    ->tooltip(fn (ContactMessage $record): string => $record->message)
                    ->wrap(),

                TextColumn::make('created_at')
                    ->label('Received')
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->modalHeading(fn (ContactMessage $record): string => 'Message from '.$record->sender_name)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->schema([
                        TextEntry::make('sender_name')
                            ->label('Name'),

                        TextEntry::make('sender_email')
                            ->label('Email'),

                        TextEntry::make('created_at')
                            ->label('Received')
                            ->dateTime(),

                        TextEntry::make('message')
                            ->label('Message')
                            ->prose(),
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->poll('60s')
            ->emptyStateIcon(Heroicon::OutlinedEnvelope)
            ->emptyStateHeading('No messages yet')
            ->emptyStateDescription('Contact form messages will appear here as soon as a visitor writes to the gallery.');
    }
}
