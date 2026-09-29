<?php

namespace App\Http\Controllers;

use App\Models\Artist;

class ArtistController extends GroupController
{
    protected string $model = Artist::class;

    protected string $resource = 'artists';

    protected string $nameField = 'artist_name';

    protected ?string $printRelation = 'label';

    protected function texts(): array
    {
        return [
            'title' => __('Künstler'),
            'new' => __('Neuer Künstler'),
            'created' => __('Künstler „:name“ wurde angelegt.'),
            'exists' => __('Künstler „:name“ ist bereits vorhanden.'),
            'saved' => __('Künstler „:name“ wurde gespeichert.'),
            'deleted' => __('Künstler „:name“ wurde gelöscht.'),
            'in_use' => __('Künstler „:name“ kann nicht gelöscht werden, weil noch Platten zugeordnet sind.'),
            'confirm_delete' => __('Künstler „:name“ wirklich löschen?'),
        ];
    }
}
