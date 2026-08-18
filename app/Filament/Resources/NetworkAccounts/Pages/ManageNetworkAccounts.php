<?php

namespace App\Filament\Resources\NetworkAccounts\Pages;

use App\Filament\Resources\NetworkAccounts\NetworkAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageNetworkAccounts extends ManageRecords
{
    protected static string $resource = NetworkAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
