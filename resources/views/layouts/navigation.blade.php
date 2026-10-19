<nav class="navbar navbar-expand-lg bg-black sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="{{ route('dashboard') }}">
            RecordLoom <i class="bi bi-vinyl-fill"></i>
        </a>
        {{-- On phones the search is in the collapsed menu, the scanner stays reachable next to it. --}}
        <button type="button" class="btn btn-outline-secondary ms-auto me-2 d-lg-none" data-barcode-scan="collection"
            title="{{ __('Barcode scannen und in der Sammlung suchen') }}" aria-label="{{ __('Barcode scannen und in der Sammlung suchen') }}">
            <i class="bi bi-upc-scan"></i>
        </button>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation"
            aria-controls="mainNavigation" aria-expanded="false" aria-label="{{ __('Navigation umschalten') }}">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavigation">
            @php $user = auth()->user(); @endphp
            <ul class="navbar-nav me-auto">
                @if ($user->isSeller())
                    <li class="nav-item">
                        <a href="{{ route('records.index') }}" @class(['nav-link', 'active' => request()->routeIs('records.*')])>{{ __('Meine Platten (Verkäufer)') }}</a>
                    </li>
                @else
                <li class="nav-item dropdown">
                    <a href="#" @class(['nav-link dropdown-toggle', 'active' => request()->routeIs('records.*', 'discogs.*')]) data-bs-toggle="dropdown">{{ __('Platten') }}</a>
                    <div class="dropdown-menu">
                        <a href="{{ route('records.index') }}" class="dropdown-item">{{ __('Alle Platten') }}</a>
                        <a href="{{ route('records.index', ['status' => 'selling']) }}" class="dropdown-item">{{ __('Zum Verkauf vorgemerkt') }}</a>
                        <a href="{{ route('records.create') }}" class="dropdown-item"><i class="bi bi-plus-lg"></i> {{ __('Neue Platte') }}</a>
                        <div class="dropdown-divider"></div>
                        <a href="{{ route('records.print') }}" target="_blank" class="dropdown-item"><i class="bi bi-filetype-pdf"></i> {{ __('Verkaufsliste (PDF)') }}</a>
                        <a href="{{ route('records.import') }}" class="dropdown-item"><i class="bi bi-upload"></i> {{ __('CSV-Import') }}</a>
                        <a href="{{ route('records.export') }}" class="dropdown-item"><i class="bi bi-download"></i> {{ __('CSV-Export (alle)') }}</a>
                        <div class="dropdown-divider"></div>
                        <a href="{{ route('discogs.match') }}" class="dropdown-item"><i class="bi bi-link-45deg"></i> {{ __('Mit Discogs abgleichen') }}</a>
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
                @endif
                @if ($user->isAdmin())
                    <li class="nav-item dropdown">
                        <a href="#" @class(['nav-link dropdown-toggle', 'active' => request()->routeIs('admin.*', 'editions.*')]) data-bs-toggle="dropdown">{{ __('Verwaltung') }}</a>
                        <div class="dropdown-menu">
                            <a href="{{ route('admin.users.index') }}" class="dropdown-item"><i class="bi bi-people"></i> {{ __('Benutzer') }}</a>
                            <a href="{{ route('admin.users.create') }}" class="dropdown-item"><i class="bi bi-person-plus"></i> {{ __('Benutzer einladen') }}</a>
                            <div class="dropdown-divider"></div>
                            <a href="{{ route('editions.index') }}" class="dropdown-item"><i class="bi bi-tags"></i> {{ __('Zusatzinfos verwalten') }}</a>
                        </div>
                    </li>
                @endif
            </ul>
            <div class="btn-group btn-group-sm me-lg-3 my-2 my-lg-0" role="group" aria-label="{{ __('Sprache') }}">
                @foreach (\App\Http\Middleware\SetLocale::LOCALES as $locale)
                    <a href="{{ route('language', $locale) }}" hreflang="{{ $locale }}" lang="{{ $locale }}"
                        @class(['btn', 'btn-secondary' => app()->getLocale() === $locale, 'btn-outline-secondary' => app()->getLocale() !== $locale])
                        @if (app()->getLocale() === $locale) aria-current="true" @endif>{{ strtoupper($locale) }}</a>
                @endforeach
            </div>
            <form class="d-flex" role="search" action="{{ route('records.index') }}" method="GET">
                <div class="input-group">
                    <input class="form-control" type="search" id="search" name="q" placeholder="{{ __('Suchen …') }}" aria-label="{{ __('Suchen') }}">
                    <button type="button" class="btn btn-outline-secondary" data-barcode-scan="collection"
                        title="{{ __('Barcode scannen und in der Sammlung suchen') }}" aria-label="{{ __('Barcode scannen und in der Sammlung suchen') }}">
                        <i class="bi bi-upc-scan"></i>
                    </button>
                </div>
            </form>
            @php $unread = $user->unreadNotifications()->count(); @endphp
            <ul class="navbar-nav ms-lg-3">
                <li class="nav-item">
                    <a href="{{ route('notifications.index') }}" class="nav-link position-relative" title="{{ __('Benachrichtigungen') }}">
                        <i class="bi bi-bell{{ $unread ? '-fill' : '' }}"></i>
                        <span class="d-lg-none ms-1">{{ __('Benachrichtigungen') }}</span>
                        @if ($unread)
                            <span class="badge rounded-pill text-bg-danger">{{ $unread }}<span class="visually-hidden"> {{ __('ungelesen') }}</span></span>
                        @endif
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a href="#" @class(['nav-link dropdown-toggle', 'active' => request()->routeIs('profile.*')]) data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <span class="d-lg-none d-xl-inline">{{ $user->name }}</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <span class="dropdown-item-text small text-body-secondary">{{ $user->role->label() }}</span>
                        <a href="{{ route('profile.show') }}" class="dropdown-item"><i class="bi bi-person"></i> {{ __('Profil') }}</a>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right"></i> {{ __('Abmelden') }}</button>
                        </form>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>
