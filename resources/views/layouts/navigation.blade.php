<nav class="navbar navbar-expand-lg bg-black sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="{{ route('dashboard') }}">
            RecordLoom <i class="bi bi-vinyl-fill"></i>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation"
            aria-controls="mainNavigation" aria-expanded="false" aria-label="{{ __('Navigation umschalten') }}">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavigation">
            <ul class="navbar-nav me-auto">
                <li class="nav-item dropdown">
                    <a href="#" @class(['nav-link dropdown-toggle', 'active' => request()->routeIs('records.*', 'editions.*')]) data-bs-toggle="dropdown">{{ __('Platten') }}</a>
                    <div class="dropdown-menu">
                        <a href="{{ route('records.index') }}" class="dropdown-item">{{ __('Alle Platten') }}</a>
                        <a href="{{ route('records.index', ['status' => 'selling']) }}" class="dropdown-item">{{ __('Zum Verkauf vorgemerkt') }}</a>
                        <a href="{{ route('records.create') }}" class="dropdown-item"><i class="bi bi-plus-lg"></i> {{ __('Neue Platte') }}</a>
                        <div class="dropdown-divider"></div>
                        <a href="{{ route('records.import') }}" class="dropdown-item"><i class="bi bi-upload"></i> {{ __('CSV-Import') }}</a>
                        <a href="{{ route('records.export') }}" class="dropdown-item"><i class="bi bi-download"></i> {{ __('CSV-Export (alle)') }}</a>
                        <div class="dropdown-divider"></div>
                        <a href="{{ route('editions.index') }}" class="dropdown-item"><i class="bi bi-tags"></i> {{ __('Zusatzinfos verwalten') }}</a>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a href="#" @class(['nav-link dropdown-toggle', 'active' => request()->routeIs('artists.*')]) data-bs-toggle="dropdown">{{ __('Künstler (Menü)') }}</a>
                    <div class="dropdown-menu">
                        <a href="{{ route('artists.index') }}" class="dropdown-item">{{ __('Alle Künstler') }}</a>
                        <a href="{{ route('artists.create') }}" class="dropdown-item"><i class="bi bi-plus-lg"></i> {{ __('Neuer Künstler') }}</a>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a href="#" @class(['nav-link dropdown-toggle', 'active' => request()->routeIs('labels.*')]) data-bs-toggle="dropdown">{{ __('Labels') }}</a>
                    <div class="dropdown-menu">
                        <a href="{{ route('labels.index') }}" class="dropdown-item">{{ __('Alle Labels') }}</a>
                        <a href="{{ route('labels.create') }}" class="dropdown-item"><i class="bi bi-plus-lg"></i> {{ __('Neues Label') }}</a>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a href="#" @class(['nav-link dropdown-toggle', 'active' => request()->routeIs('platforms.*')]) data-bs-toggle="dropdown">{{ __('Anbieter (Menü)') }}</a>
                    <div class="dropdown-menu">
                        <a href="{{ route('platforms.index') }}" class="dropdown-item">{{ __('Alle Anbieter') }}</a>
                        <a href="{{ route('platforms.create') }}" class="dropdown-item"><i class="bi bi-plus-lg"></i> {{ __('Neuer Anbieter') }}</a>
                    </div>
                </li>
            </ul>
            <div class="btn-group btn-group-sm me-lg-3 my-2 my-lg-0" role="group" aria-label="{{ __('Sprache') }}">
                @foreach (\App\Http\Middleware\SetLocale::LOCALES as $locale)
                    <a href="{{ route('language', $locale) }}" hreflang="{{ $locale }}" lang="{{ $locale }}"
                        @class(['btn', 'btn-secondary' => app()->getLocale() === $locale, 'btn-outline-secondary' => app()->getLocale() !== $locale])
                        @if (app()->getLocale() === $locale) aria-current="true" @endif>{{ strtoupper($locale) }}</a>
                @endforeach
            </div>
            <form class="d-flex" role="search" action="{{ route('records.index') }}" method="GET">
                <input class="form-control" type="search" id="search" name="q" placeholder="{{ __('Suchen …') }}" aria-label="{{ __('Suchen') }}">
            </form>
        </div>
    </div>
</nav>
