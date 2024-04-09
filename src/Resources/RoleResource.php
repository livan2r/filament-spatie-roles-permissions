<?php

namespace Althinect\FilamentSpatieRolesPermissions\Resources;

use Althinect\FilamentSpatieRolesPermissions\Resources\RoleResource\Pages\CreateRole;
use Althinect\FilamentSpatieRolesPermissions\Resources\RoleResource\Pages\EditRole;
use Althinect\FilamentSpatieRolesPermissions\Resources\RoleResource\Pages\ListRoles;
use Althinect\FilamentSpatieRolesPermissions\Resources\RoleResource\Pages\ViewRole;
use Althinect\FilamentSpatieRolesPermissions\Resources\RoleResource\RelationManager\PermissionRelationManager;
use Althinect\FilamentSpatieRolesPermissions\Resources\RoleResource\RelationManager\UserRelationManager;
use Filament\Facades\Filament;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RoleResource extends Resource
{

    protected static ?string $recordTitleAttribute = 'name';

    public static function isScopedToTenant(): bool
    {
        return config('filament-spatie-roles-permissions.scope_to_tenant', true);
    }

    public static function getNavigationIcon(): ?string
    {
        return  config('filament-spatie-roles-permissions.icons.role_navigation');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return config('filament-spatie-roles-permissions.should_register_on_navigation.roles', true);
    }

    public static function getModel(): string
    {
        return config('permission.models.role', Role::class);
    }

    public static function getLabel(): string
    {
        return __('filament-spatie-roles-permissions::filament-spatie.section.role');
    }

    public static function getNavigationGroup(): ?string
    {
        return Str::upper(__(config('filament-spatie-roles-permissions.navigation_section_group', 'filament-spatie-roles-permissions::filament-spatie.section.roles_and_permissions')));
    }

    public static function getNavigationSort(): ?int
    {
        return  config('filament-spatie-roles-permissions.sort.role_navigation');
    }

    public static function getPluralLabel(): string
    {
        return __('filament-spatie-roles-permissions::filament-spatie.section.roles');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make()
                            ->schema(static::getDetailsFormSchema())
                            ->columns(2),
                    ])
                    ->columnSpan(['lg' => fn (?Role $record) => $record === null ? 3 : 2]),

                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Placeholder::make('created_at')
                            ->label('Created at')
                            ->content(fn (Role $record): ?string => $record->created_at?->diffForHumans()),

                        Forms\Components\Placeholder::make('updated_at')
                            ->label('Last modified at')
                            ->content(fn (Role $record): ?string => $record->updated_at?->diffForHumans()),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn (?Role $record) => $record === null),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),

                TextColumn::make('name')
                    ->label(__('filament-spatie-roles-permissions::filament-spatie.field.name'))
                    ->searchable()
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

                TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label(__('filament-spatie-roles-permissions::filament-spatie.field.permissions_count'))
                    ->toggleable(isToggledHiddenByDefault: config('filament-spatie-roles-permissions.toggleable_guard_names.roles.isToggledHiddenByDefault', true)),

                TextColumn::make('guard_name')
                    ->toggleable(isToggledHiddenByDefault: config('filament-spatie-roles-permissions.toggleable_guard_names.roles.isToggledHiddenByDefault', true))
                    ->label(__('filament-spatie-roles-permissions::filament-spatie.field.guard_name'))
                    ->searchable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'web' => 'success',
                        'api' => 'warning',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'web' => 'heroicon-o-globe-americas',
                        'api' => 'heroicon-o-bolt',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([

            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PermissionRelationManager::class,
            UserRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
            'view' => ViewRole::route('/{record}'),
        ];
    }

    public static function getDetailsFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->label(__('filament-spatie-roles-permissions::filament-spatie.field.name'))
                ->required()
                ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule) {
                    // If using teams and Tenancy, ensure uniqueness against current tenant
                    if(config('permission.teams', false) && Filament::hasTenancy()) {
                        // Check uniqueness against current user/team
                        $rule->where(config('permission.column_names.team_foreign_key', 'team_id'), Filament::getTenant()->id);
                    }
                    return $rule;
                }),

            Select::make('guard_name')
                ->label(__('filament-spatie-roles-permissions::filament-spatie.field.guard_name'))
                ->options(config('filament-spatie-roles-permissions.guard_names'))
                ->default(config('filament-spatie-roles-permissions.default_guard_name'))
                ->required(),

            Select::make('permissions')
                ->columnSpanFull()
                ->multiple()
                ->label(__('filament-spatie-roles-permissions::filament-spatie.field.permissions'))
                ->relationship(
                    name: 'permissions',
                    modifyQueryUsing: fn (Builder $query) => $query->orderBy('name'),
                )
                ->getOptionLabelFromRecordUsing(fn (Model $record) => "{$record->name} ({$record->guard_name})")
                ->searchable(['name', 'guard_name']) // searchable on both name and guard_name
                ->preload(config('filament-spatie-roles-permissions.preload_permissions')),

            Select::make(config('permission.column_names.team_foreign_key', 'team_id'))
                ->label(__('filament-spatie-roles-permissions::filament-spatie.field.team'))
                ->hidden(fn () => ! config('permission.teams', false) || Filament::hasTenancy())
                ->options(
                    fn () => config('filament-spatie-roles-permissions.team_model', \App\Models\Team::class)::pluck('name', 'id')
                )
                ->dehydrated(fn ($state) => (int) $state > 0)
                ->placeholder(__('filament-spatie-roles-permissions::filament-spatie.select-team'))
                ->hint(__('filament-spatie-roles-permissions::filament-spatie.select-team-hint')),
        ];
    }
}
