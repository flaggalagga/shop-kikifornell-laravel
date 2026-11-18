<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label('Voir sur le site')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn () => route('pages.show', ['locale' => 'fr', 'page' => $this->record]), shouldOpenInNewTab: true),
            DeleteAction::make()
                ->hidden(fn () => in_array($this->record->slug, PageResource::PROTECTED_SLUGS, true)),
        ];
    }
}
