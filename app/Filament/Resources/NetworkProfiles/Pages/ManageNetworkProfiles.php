<?php

namespace App\Filament\Resources\NetworkProfiles\Pages;

use App\Filament\Resources\NetworkProfiles\NetworkProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageNetworkProfiles extends ManageRecords
{
    protected static string $resource = NetworkProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
