<x-guest-layout>
    <h1 class="h4 mb-3">{{ __('Anmelden') }}</h1>
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">{{ __('E-Mail') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                class="form-control @error('email') is-invalid @enderror">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">{{ __('Passwort') }}</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                class="form-control @error('password') is-invalid @enderror">
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="form-check mb-3">
            <input id="remember" type="checkbox" name="remember" class="form-check-input">
            <label for="remember" class="form-check-label">{{ __('Angemeldet bleiben') }}</label>
        </div>
        <div class="d-flex align-items-center justify-content-between gap-2">
            <a href="{{ route('password.request') }}" class="small">{{ __('Passwort vergessen?') }}</a>
            <button type="submit" class="btn btn-primary"><i class="bi bi-box-arrow-in-right"></i> {{ __('Anmelden') }}</button>
        </div>
    </form>
</x-guest-layout>
