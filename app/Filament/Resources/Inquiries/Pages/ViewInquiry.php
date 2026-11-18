<?php

namespace App\Filament\Resources\Inquiries\Pages;

use App\Filament\Resources\Inquiries\InquiriesActions;
use App\Filament\Resources\Inquiries\InquiryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewInquiry extends ViewRecord
{
    protected static string $resource = InquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            InquiriesActions::reply(),
            InquiriesActions::markHandled(),
            DeleteAction::make(),
        ];
    }
}
