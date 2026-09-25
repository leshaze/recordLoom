<section>
    <header>
        <h2 class="h5">{{ __('Delete Account') }}</h2>

        <p class="small text-body-secondary">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.destroy') }}"
        onsubmit="return confirm(@js(__('Are you sure you want to delete your account?')))">
        @csrf
        @method('delete')

        <div class="mb-3">
            <x-input-label for="delete_user_password" :value="__('Password')" />
            <x-text-input id="delete_user_password" name="password" type="password" autocomplete="current-password" />
            <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-1" />
        </div>

        <x-danger-button>{{ __('Delete Account') }}</x-danger-button>
    </form>
</section>
