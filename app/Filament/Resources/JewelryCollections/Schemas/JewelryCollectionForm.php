<?php

namespace App\Filament\Resources\JewelryCollections\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class JewelryCollectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Français')->schema([
                TextInput::make('name_fr')
                    ->label('Nom')
                    ->required()
                    ->maxLength(120)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                        if (blank($get('slug')) && filled($state)) {
                            $set('slug', Str::slug($state));
                        }
                    }),
                Textarea::make('intro_fr')->label('Présentation')->rows(3),
            ]),
            Section::make('English')->schema([
                TextInput::make('name_en')->label('Name')->required()->maxLength(120),
                Textarea::make('intro_en')->label('Introduction')->rows(3),
            ]),
            Section::make('Réglages')->columns(2)->schema([
                FileUpload::make('cover_image')
                    ->label('Image de couverture')
                    ->helperText('Facultatif : sinon la photo de la première pièce est utilisée.')
                    ->image()
                    ->disk('public')
                    ->directory('collections')
                    ->visibility('public')
                    ->maxSize(15360)
                    ->columnSpanFull(),
                TextInput::make('slug')
                    ->label('Adresse de la page')
                    ->prefix('/collections/')
                    ->required()
                    ->alphaDash()
                    ->unique(ignoreRecord: true),
                TextInput::make('position')->label('Ordre')->numeric()->default(0),
                Toggle::make('is_published')->label('Visible sur le site')->default(true),
            ]),
        ]);
    }
}
