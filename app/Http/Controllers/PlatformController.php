<?php

namespace App\Http\Controllers;

use App\Models\Platform;

class PlatformController extends GroupController
{
    protected string $model = Platform::class;

    protected string $resource = 'platforms';

    protected string $nameField = 'platform_name';

    protected bool $withUrl = true;

    protected function texts(): array
    {
        return [
            'title' => __('Anbieter'),
            'new' => __('Neuer Anbieter'),
            'created' => __('Anbieter „:name“ wurde angelegt.'),
            'exists' => __('Anbieter „:name“ ist bereits vorhanden.'),
            'saved' => __('Anbieter „:name“ wurde gespeichert.'),
            'deleted' => __('Anbieter „:name“ wurde gelöscht.'),
            'in_use' => __('Anbieter „:name“ kann nicht gelöscht werden, weil noch Platten zugeordnet sind.'),
            'confirm_delete' => __('Anbieter „:name“ wirklich löschen?'),
        ];
    }
}
