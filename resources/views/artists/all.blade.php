<x-app-layout title="{{ __('Künstler') }}">
    <x-group-list :items="$artists" :filter="$filter" resource="artists" :title="__('Künstler')" :create-label="__('Neuer Künstler')"
        :delete-message="fn ($artist) => __('Künstler „:name“ wirklich löschen?', ['name' => $artist->name])" />
</x-app-layout>
