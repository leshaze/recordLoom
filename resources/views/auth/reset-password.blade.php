<x-guest-layout>
    <h1 class="h4 mb-3">{{ __('Passwort festlegen') }}</h1>
    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div class="mb-3">
            <label for="email" class="form-label">{{ __('E-Mail') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autocomplete="username"
                class="form-control @error('email') is-invalid @enderror">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">{{ __('Neues Passwort') }}</label>
            <input id="password" type="password" name="password" required autofocus autocomplete="new-password"
                class="form-control @error('password') is-invalid @enderror">
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">{{ __('Mindestens 8 Zeichen.') }}</div>
        </div>
        <div class="mb-3">
            <label for="password_confirmation" class="form-label">{{ __('Passwort wiederholen') }}</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary w-100">{{ __('Passwort speichern') }}</button>
    </form>
</x-guest-layout>
