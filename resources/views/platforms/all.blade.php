<x-app-layout title="{{ __('Anbieter') }}">
    <x-group-list :items="$platforms" :filter="$filter" resource="platforms" :title="__('Anbieter')" :create-label="__('Neuer Anbieter')" :with-url="true"
        :search-placeholder="__('Name, Beschreibung, URL …')"
        :delete-message="fn ($platform) => __('Anbieter „:name“ wirklich löschen?', ['name' => $platform->name])" />
</x-app-layout>
