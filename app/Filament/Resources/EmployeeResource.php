<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\City;
use App\Models\Country;
use App\Models\Department;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Support\Collection;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\State; // Import the missing class
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Employee Management';

    protected static ?string $recordTitleAttribute = 'first_name';

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->first_name . ' ' . $record->last_name;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'first_name',
            'middle_name',
            'last_name',
            'country.name'
        ];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Country' => $record->country->name,
            'State' => $record->state->name,
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with('country', 'state');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Relationships')
                ->schema([
                    Forms\Components\Select::make('country_id')
                        ->relationship(name: 'country', titleAttribute: 'name')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (Set $set) {
                            $set('state_id', null);
                            $set('city_id', null);
                        })
                        ->required(),
                    Forms\Components\Select::make('state_id')
                        ->options(fn(Get $get): Collection => State::query()
                        ->where('country_id', $get('country_id'))
                        ->pluck('name', 'id'))
                        ->live()
                        ->afterStateUpdated(fn(Set $set) => $set('city_id', null))
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('city_id')
                        ->options(fn(Get $get): Collection => City::query()
                        ->where('state_id', $get('state_id'))
                        ->pluck('name', 'id'))
                        ->live()
                        ->searchable()
                        ->required(),
                    Forms\Components\Select::make('department_id')
                        ->relationship(name: 'department', titleAttribute: 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                ])->columns(2),
                Forms\Components\Section::make('User Name')
                ->description('Put the user name details here.')
                ->schema([
                    Forms\Components\TextInput::make('first_name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('middle_name')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('last_name')
                        ->required()
                        ->maxLength(255),
                ])->columns(3),
                Forms\Components\Section::make('Address')
                ->description('Put the address details here.')
                ->schema([Forms\Components\TextInput::make('address')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('zip_code')
                    ->required()
                    ->maxLength(10),
                ])->columns(2),
                Forms\Components\Section::make('Dates')
                ->schema([Forms\Components\DatePicker::make('date_of_birth')
                ->native(false)
                ->displayFormat('d/m/Y')
                ->required(),
            Forms\Components\DatePicker::make('date_hired')
                ->native(false)
                ->displayFormat('d/m/Y')
                ->required(),])->columns(2)
                
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('country.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('state.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('city.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('middle_name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('zip_code')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('date_of_birth')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('date_hired')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                SelectFilter::make('department_id')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Department'),
                // SelectFilter::make('country_id')
                //     ->relationship('country', 'name')
                //     ->searchable()
                //     ->preload()
                //     ->label('Country'),
                // SelectFilter::make('state_id')
                //     ->relationship('state', 'name')
                //     ->searchable()
                //     ->preload()
                //     ->label('State'),
                // SelectFilter::make('city_id')
                //     ->relationship('city', 'name')
                //     ->searchable()
                //     ->label('City'),
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    // ...
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                
                        if ($data['from'] ?? null) {
                            $indicators[] = Indicator::make('Created from ' . Carbon::parse($data['from'])->toFormattedDateString())
                                ->removeField('form');
                        }
                
                        if ($data['until'] ?? null) {
                            $indicators[] = Indicator::make('Created until ' . Carbon::parse($data['until'])->toFormattedDateString())
                                ->removeField('until');
                        }
                
                        return $indicators;
                    })->columnSpan(2)->columns(2)
                ],layout: FiltersLayout::AboveContent)->filtersFormColumns(3)
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()    
                            ->title('Employee deleted')
                            ->body('The employee was deleted successfully.')
                    )
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Relationship')
                ->schema([
                    TextEntry::make('country.name'),
                    TextEntry::make('state.name'),
                    TextEntry::make('city.name'),
                    TextEntry::make('department.name'),
                ])->columns(2),
                Section::make('Name')
                ->schema([
                    TextEntry::make('first_name'),
                    TextEntry::make('middle_name'),
                    TextEntry::make('last_name'),
                ])->columns(3),
                Section::make('Address')
                ->schema([
                    TextEntry::make('address'),
                    TextEntry::make('zip_code'),
                ])->columns(2),

            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            // 'view' => Pages\ViewEmployee::route('/{record}'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
