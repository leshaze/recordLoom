{{-- Invite a new user or edit one. $user is null when inviting. --}}
<x-app-layout :title="$user ? __(':name bearbeiten', ['name' => $user->name]) : __('Benutzer einladen')">
    <div class="container" style="max-width: 40rem;">
        <h1 class="h3 mb-3">
            @if ($user) {{ $user->name }} <small class="text-body-secondary">{{ __('bearbeiten') }}</small>
            @else {{ __('Benutzer einladen') }} @endif
        </h1>
        <form method="POST" action="{{ $user ? route('admin.users.update', $user) : route('admin.users.store') }}" class="card card-body">
            @csrf
            @if ($user) @method('PUT') @endif
            <div class="mb-3">
                <label for="name" class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" required maxlength="255" autofocus
                    class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user?->name) }}">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">{{ __('E-Mail') }} <span class="text-danger">*</span></label>
                <input type="email" name="email" id="email" required maxlength="255"
                    class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user?->email) }}">
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <fieldset class="mb-3">
                <legend class="form-label fs-6">{{ __('Rolle') }} <span class="text-danger">*</span></legend>
                @foreach ([
                    [\App\Enums\Role::User, __('Hat eine eigene Sammlung und kann Platten Verkäufern zuweisen und an sie verkaufen.')],
                    [\App\Enums\Role::Seller, __('Sieht zugewiesene und zum Verkauf vorgemerkte Platten (ohne Kaufpreis und Notiz) und kann Interesse anmelden.')],
                    [\App\Enums\Role::Admin, __('Sieht alle Platten und verwaltet Benutzer und Zusatzinfos.')],
                ] as [$role, $description])
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="radio" name="role" id="role-{{ $role->value }}" value="{{ $role->value }}"
                            @checked(old('role', $user?->role->value ?? \App\Enums\Role::User->value) === $role->value)>
                        <label class="form-check-label" for="role-{{ $role->value }}">
                            {{ $role->label() }} <span class="small text-body-secondary d-block">{{ $description }}</span>
                        </label>
                    </div>
                @endforeach
                @error('role') <div class="text-danger small">{{ $message }}</div> @enderror
            </fieldset>
            <div class="form-check mb-3">
                <input type="hidden" name="sees_all_records" value="0">
                <input class="form-check-input" type="checkbox" name="sees_all_records" value="1" id="sees_all_records"
                    @checked(old('sees_all_records', $user?->sees_all_records))>
                <label class="form-check-label" for="sees_all_records">
                    {{ __('Alle Platten anzeigen') }}
                    <span class="small text-body-secondary d-block">{{ __('Nur für Verkäufer: sieht alle Platten, die nicht verkauft sind, auch ohne Zuweisung und ohne „Zum Verkauf vormerken“, und kann Interesse anmelden.') }}</span>
                </label>
            </div>
            @unless ($user)
                <p class="small text-body-secondary">{{ __('Der Benutzer bekommt eine E-Mail mit einem Link, über den er sein Passwort festlegt.') }}</p>
            @endunless
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    @if ($user) <i class="bi bi-check-lg"></i> {{ __('Speichern') }} @else <i class="bi bi-envelope"></i> {{ __('Einladen') }} @endif
                </button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">{{ __('Abbrechen') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
