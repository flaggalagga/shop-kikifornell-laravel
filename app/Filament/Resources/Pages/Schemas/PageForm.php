<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Filament\Resources\Pages\PageResource;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        $toolbar = ['heading', 'bold', 'italic', 'bulletList', 'orderedList', 'link', 'undo', 'redo'];

        return $schema->columns(3)->components([
            Tabs::make('Textes')->columnSpan(2)->tabs([
                Tab::make('Français')->schema([
                    TextInput::make('title_fr')->label('Titre')->required()->maxLength(150),
                    MarkdownEditor::make('body_fr')->label('Texte')->toolbarButtons($toolbar),
                ]),
                Tab::make('English')->schema([
                    TextInput::make('title_en')->label('Title')->required()->maxLength(150),
                    MarkdownEditor::make('body_en')->label('Text')->toolbarButtons($toolbar),
                ]),
            ]),
            Section::make('Réglages')->columnSpan(1)->schema([
                TextInput::make('slug')
                    ->label('Adresse de la page')
                    ->prefix('/info/')
                    ->required()
                    ->alphaDash()
                    ->unique(ignoreRecord: true)
                    ->disabled(fn ($record) => $record && in_array($record->slug, PageResource::PROTECTED_SLUGS, true)),
                Toggle::make('show_in_footer')->label('Lien dans le pied de page')->default(true),
                TextInput::make('position')->label('Ordre')->numeric()->default(0),
            ]),
        ]);
    }
}
