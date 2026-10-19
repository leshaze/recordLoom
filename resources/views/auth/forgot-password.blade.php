<x-guest-layout>
    <h1 class="h4 mb-3">{{ __('Passwort vergessen') }}</h1>
    <p class="small text-body-secondary">{{ __('Gib deine E-Mail-Adresse ein. Du bekommst einen Link, mit dem du ein neues Passwort setzen kannst.') }}</p>
    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">{{ __('E-Mail') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                class="form-control @error('email') is-invalid @enderror">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="d-flex align-items-center justify-content-between gap-2">
            <a href="{{ route('login') }}" class="small">{{ __('Zur Anmeldung') }}</a>
            <button type="submit" class="btn btn-primary">{{ __('Link senden') }}</button>
        </div>
    </form>
</x-guest-layout>
