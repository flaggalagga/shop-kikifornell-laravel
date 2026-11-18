<?php

namespace App\Filament\Resources\Pieces\Schemas;

use App\Enums\PieceStatus;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PieceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    Section::make('Photos')
                        ->description('La première photo est la photo principale. Glissez pour changer l’ordre. JPEG, PNG ou WebP, 15 Mo maximum.')
                        ->schema([
                            Repeater::make('photos')
                                ->hiddenLabel()
                                ->relationship()
                                ->orderColumn('position')
                                ->reorderable()
                                ->collapsible()
                                ->itemLabel(fn (array $state) => $state['alt_fr'] ?? null)
                                ->addActionLabel('Ajouter une photo')
                                ->defaultItems(0)
                                ->columns(2)
                                ->schema([
                                    FileUpload::make('path')
                                        ->label('Photo')
                                        ->image()
                                        ->disk('public')
                                        ->directory('pieces')
                                        ->visibility('public')
                                        ->maxSize(15360)
                                        ->required()
                                        ->columnSpanFull(),
                                    TextInput::make('alt_fr')
                                        ->label('Description de la photo (FR)')
                                        ->helperText('Pour les malvoyants et Google, ex. « Boucle d’oreille portée, vue de profil ».')
                                        ->maxLength(200),
                                    TextInput::make('alt_en')
                                        ->label('Description de la photo (EN)')
                                        ->maxLength(200),
                                ]),
                        ]),

                    Tabs::make('Textes')->tabs([
                        Tab::make('Français')->schema(self::texts('fr', required: true)),
                        Tab::make('English')->schema(self::texts('en', required: true)),
                    ]),
                ])->columnSpan(2),

                Group::make([
                    Section::make('Vente')->schema([
                        TextInput::make('reference')
                            ->label('Référence')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set, ?string $state) => self::suggestSlug($get, $set)),
                        TextInput::make('price_cents')
                            ->label('Prix')
                            ->prefix('€')
                            ->numeric()
                            ->minValue(1)
                            ->step('0.01')
                            ->required()
                            ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                            ->dehydrateStateUsing(fn ($state) => (int) round(((float) str_replace(',', '.', (string) $state)) * 100)),
                        Select::make('status')
                            ->label('Statut')
                            ->options(PieceStatus::class)
                            ->default(PieceStatus::Draft)
                            ->required()
                            ->helperText('« Réservé » est géré automatiquement pendant un paiement.'),
                        Select::make('collection_id')
                            ->label('Collection')
                            ->relationship('collection', 'name_fr')
                            ->preload(),
                        Toggle::make('is_featured')
                            ->label('Mettre en avant sur l’accueil'),
                    ]),

                    Section::make('Détails')->schema([
                        TextInput::make('dimensions')
                            ->label('Dimensions')
                            ->placeholder('ex. 4,5 × 2 cm')
                            ->maxLength(100),
                        TextInput::make('weight_grams')
                            ->label('Poids')
                            ->suffix('g')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('slug')
                            ->label('Adresse de la page')
                            ->prefix('/creations/')
                            ->required()
                            ->maxLength(120)
                            ->alphaDash()
                            ->unique(ignoreRecord: true),
                        TextInput::make('position')
                            ->label('Ordre d’affichage')
                            ->numeric()
                            ->default(0),
                    ]),
                ])->columnSpan(1),
            ]);
    }

    /** @return array<int, Component> */
    private static function texts(string $locale, bool $required): array
    {
        $isFr = $locale === 'fr';

        return [
            TextInput::make("title_{$locale}")
                ->label($isFr ? 'Titre' : 'Title')
                ->required($required)
                ->maxLength(150)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Get $get, Set $set) => self::suggestSlug($get, $set)),
            TextInput::make("materials_{$locale}")
                ->label($isFr ? 'Matières' : 'Materials')
                ->placeholder($isFr ? 'Émail grand feu, argent 925, or' : 'Grand feu enamel, sterling silver, gold')
                ->maxLength(200),
            MarkdownEditor::make("description_{$locale}")
                ->label($isFr ? 'Description' : 'Description')
                ->toolbarButtons(['bold', 'italic', 'bulletList', 'link', 'undo', 'redo']),
            TextInput::make("meta_description_{$locale}")
                ->label($isFr ? 'Résumé pour Google (facultatif)' : 'Summary for Google (optional)')
                ->maxLength(160),
        ];
    }

    private static function suggestSlug(Get $get, Set $set): void
    {
        if (filled($get('slug'))) {
            return;
        }

        $title = $get('title_en') ?: $get('title_fr');

        if (filled($get('reference')) && filled($title)) {
            $set('slug', Str::slug($get('reference').' '.$title));
        }
    }
}
