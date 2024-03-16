<?php

namespace Althinect\FilamentSpatieRolesPermissions\Resources\RoleResource\RelationManager;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Actions\DetachAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UserRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $recordTitleAttribute = 'name';

    /*
     * Support changing tab title in RelationManager.
     */
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('filament-spatie-roles-permissions::filament-spatie.section.users') ?? (string) str(static::getRelationshipName())
            ->kebab()
            ->replace('-', ' ')
            ->headline();
    }

    protected static function getModelLabel(): string
    {
        return __('filament-spatie-roles-permissions::filament-spatie.section.users');
    }

    protected static function getPluralModelLabel(): string
    {
        return __('filament-spatie-roles-permissions::filament-spatie.section.users');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make(config('filament-spatie-roles-permissions.user_name_column'))
                    ->label(__('filament-spatie-roles-permissions::filament-spatie.field.name')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            // Support changing table heading by translations.
            ->heading(__('filament-spatie-roles-permissions::filament-spatie.section.users'))
            ->columns([
                TextColumn::make(config('filament-spatie-roles-permissions.user_name_column'))
                    ->label(__('filament-spatie-roles-permissions::filament-spatie.field.name'))
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable()
                    ->icon('heroicon-m-envelope')
                    ->iconColor('primary'),
                IconColumn::make('verified')
                    ->boolean(),
                TextColumn::make('roles.name')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Super Admin' => 'danger',
                        'Admin' => 'warning',
                        'Manager' => 'success',
                        'Editor' => 'gray',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'Super Admin' => 'heroicon-o-shield-check',
                        'Admin' => 'heroicon-o-cog',
                        'Manager' => 'heroicon-o-circle-stack',
                        'Editor' => 'heroicon-o-pencil-square',
                    }),
            ])
            ->filters([

            ])->headerActions([
                AttachAction::make(),
            ])->actions([
                DetachAction::make(),
            ])->bulkActions([
                //
            ]);
    }
}
