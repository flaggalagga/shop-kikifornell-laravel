<?php

namespace App\Filament\Resources\Inquiries;

use App\Models\Inquiry;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class InquiriesActions
{
    public static function reply(): Action
    {
        return Action::make('reply')
            ->label('Répondre par e-mail')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->url(fn (Inquiry $record) => 'mailto:'.$record->email.'?subject='.rawurlencode(
                'Re: '.($record->piece ? $record->piece->reference.' — ' : '').config('shop.name')
            ));
    }

    public static function markHandled(): Action
    {
        return Action::make('handled')
            ->label('Marquer comme traité')
            ->icon(Heroicon::OutlinedCheck)
            ->color('success')
            ->visible(fn (Inquiry $record) => $record->handled_at === null)
            ->action(fn (Inquiry $record) => $record->update(['handled_at' => now()]));
    }
}
