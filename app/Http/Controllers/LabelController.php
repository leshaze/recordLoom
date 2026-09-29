<?php

namespace App\Http\Controllers;

use App\Models\Label;

class LabelController extends GroupController
{
    protected string $model = Label::class;

    protected string $resource = 'labels';

    protected string $nameField = 'label_name';

    protected ?string $printRelation = 'artist';

    protected function texts(): array
    {
        return [
            'title' => __('Labels'),
            'new' => __('Neues Label'),
            'created' => __('Label „:name“ wurde angelegt.'),
            'exists' => __('Label „:name“ ist bereits vorhanden.'),
            'saved' => __('Label „:name“ wurde gespeichert.'),
            'deleted' => __('Label „:name“ wurde gelöscht.'),
            'in_use' => __('Label „:name“ kann nicht gelöscht werden, weil noch Platten zugeordnet sind.'),
            'confirm_delete' => __('Label „:name“ wirklich löschen?'),
        ];
    }
}
