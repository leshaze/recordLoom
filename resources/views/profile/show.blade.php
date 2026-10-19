<x-app-layout :title="__('Profil')">
    <div class="container" style="max-width: 50rem;">
        <h1 class="h3 mb-3"><i class="bi bi-person-circle"></i> {{ $user->name }}</h1>

        <div class="card mb-3">
            <div class="card-header">{{ __('Übersicht') }}</div>
            <div class="card-body"><dl class="row mb-0">
                <dt class="col-5 col-sm-4 fw-normal text-body-secondary">{{ __('Rolle') }}</dt>
                <dd class="col-7 col-sm-8"><span class="badge text-bg-secondary">{{ $user->role->label() }}</span></dd>
                <dt class="col-5 col-sm-4 fw-normal text-body-secondary">{{ __('E-Mail') }}</dt>
                <dd class="col-7 col-sm-8 text-break">{{ $user->email }}</dd>
                <dt class="col-5 col-sm-4 fw-normal text-body-secondary">{{ __('Dabei seit') }}</dt>
                <dd class="col-7 col-sm-8">{{ \App\Support\Format::date($user->created_at) }}</dd>
                @if ($user->isSeller())
                    <dt class="col-5 col-sm-4 fw-normal text-body-secondary">{{ __('Dir zugewiesen') }}</dt>
                    <dd class="col-7 col-sm-8"><a href="{{ route('records.index') }}">{{ trans_choice(':count Platte|:count Platten', $assignedCount) }}</a></dd>
                    @if ($user->sees_all_records)
                        <dt class="col-5 col-sm-4 fw-normal text-body-secondary">{{ __('Sichtbar') }}</dt>
                        <dd class="col-7 col-sm-8">{{ __('Du siehst alle Platten, die noch nicht verkauft sind.') }}</dd>
                    @endif
                    <dt class="col-5 col-sm-4 fw-normal text-body-secondary">{{ __('An dich verkauft') }}</dt>
                    <dd class="col-7 col-sm-8">{{ trans_choice(':count Platte|:count Platten', $boughtCount) }}</dd>
                @else
                    <dt class="col-5 col-sm-4 fw-normal text-body-secondary">{{ __('Eigene Sammlung') }}</dt>
                    <dd class="col-7 col-sm-8"><a href="{{ route('records.index') }}">{{ trans_choice(':count Platte|:count Platten', $recordCount) }}</a></dd>
                @endif
                <dt class="col-5 col-sm-4 fw-normal text-body-secondary">{{ __('Benachrichtigungen') }}</dt>
                <dd class="col-7 col-sm-8"><a href="{{ route('notifications.index') }}">{{ __(':count ungelesen', ['count' => $unreadCount]) }}</a></dd>
            </dl></div>
        </div>

        <div class="card mb-3">
            <div class="card-header">{{ __('Benachrichtigungen') }}</div>
            <form method="POST" action="{{ route('profile.notifications') }}" class="card-body">
                @csrf
                @method('PUT')
                <input type="hidden" name="mail_notifications" value="0">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="mail_notifications" value="1" id="mail_notifications"
                        @checked($user->mail_notifications) data-auto-submit>
                    <label class="form-check-label" for="mail_notifications">
                        {{ __('Benachrichtigungen auch per E-Mail') }}
                        <span class="small text-body-secondary d-block">{{ $user->isSeller()
                            ? __('Wenn eine Platte, für die du Interesse angemeldet hast, verkauft oder nicht mehr verkauft wird.')
                            : __('Wenn ein Verkäufer Interesse an einer deiner Platten anmeldet, zurücknimmt oder seinen Preisvorschlag ändert.') }}
                            {{ __('In der App erscheinen die Benachrichtigungen immer.') }}</span>
                    </label>
                </div>
                <noscript><button type="submit" class="btn btn-sm btn-primary mt-2">{{ __('Speichern') }}</button></noscript>
            </form>
        </div>

        <div class="card mb-3">
            <div class="card-header">{{ __('Name und E-Mail') }}</div>
            <form method="POST" action="{{ route('profile.update') }}" class="card-body">
                @csrf
                @method('PATCH')
                <div class="mb-3">
                    <label for="name" class="form-label">{{ __('Name') }}</label>
                    <input type="text" name="name" id="name" required maxlength="255" autocomplete="name"
                        class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">{{ __('E-Mail') }}</label>
                    <input type="email" name="email" id="email" required maxlength="255" autocomplete="username"
                        class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <button type="submit" class="btn btn-primary">{{ __('Speichern') }}</button>
            </form>
        </div>

        <div class="card mb-3">
            <div class="card-header">{{ __('Passwort ändern') }}</div>
            <form method="POST" action="{{ route('password.update') }}" class="card-body">
                @csrf
                @method('PUT')
                @foreach ([['current_password', __('Aktuelles Passwort'), 'current-password'], ['password', __('Neues Passwort'), 'new-password'], ['password_confirmation', __('Passwort wiederholen'), 'new-password']] as [$field, $label, $autocomplete])
                    <div class="mb-3">
                        <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                        <input type="password" name="{{ $field }}" id="{{ $field }}" autocomplete="{{ $autocomplete }}"
                            @class(['form-control', 'is-invalid' => $errors->updatePassword->has($field)])>
                        @if ($errors->updatePassword->has($field))
                            <div class="invalid-feedback">{{ $errors->updatePassword->first($field) }}</div>
                        @endif
                    </div>
                @endforeach
                <button type="submit" class="btn btn-primary">{{ __('Passwort ändern') }}</button>
            </form>
        </div>
    </div>
</x-app-layout>
