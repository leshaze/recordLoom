<x-app-layout>
    <div class="container" style="max-width: 40rem;">
        <h1 class="h4 mb-3">{{ __('Profile') }}</h1>

        <div class="card p-4 mb-3">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="card p-4 mb-3">
            @include('profile.partials.update-password-form')
        </div>

        <div class="card p-4 mb-3">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>
