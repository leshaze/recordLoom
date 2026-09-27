<x-app-layout title="{{ __('Labels') }}">
    <x-group-list :items="$labels" :filter="$filter" resource="labels" :title="__('Labels')" :create-label="__('Neues Label')"
        :delete-message="fn ($label) => __('Label „:name“ wirklich löschen?', ['name' => $label->name])" />
</x-app-layout>
