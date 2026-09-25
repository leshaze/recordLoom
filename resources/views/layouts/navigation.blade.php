<nav class="navbar navbar-expand-md bg-black fixed-top shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="{{ url('/') }}">
            {{ config('app.name', 'recordsArchive') }} <i class="bi bi-file-music-fill"></i>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
            aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <!-- Left Side Of Navbar -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Records</a>
                    <div class="dropdown-menu">
                        <a href="{{ route('records.index') }}" class="dropdown-item">All Records</a>
                        <a href="{{ route('records.selling') }}" class="dropdown-item">For Selling</a>
                        <a href="{{ route('records.create') }}" class="dropdown-item">Add new Record</a>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Artists</a>
                    <div class="dropdown-menu">
                        <a href="{{ route('artists.index') }}" class="dropdown-item">All Artists</a>
                        <a href="{{ route('artists.create') }}" class="dropdown-item">Add new Artist</a>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Labels</a>
                    <div class="dropdown-menu">
                        <a href="{{ route('labels.index') }}" class="dropdown-item">All Label</a>
                        <a href="{{ route('labels.create') }}" class="dropdown-item">Add new Label</a>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Platform</a>
                    <div class="dropdown-menu">
                        <a href="{{ route('platforms.index') }}" class="dropdown-item">All Platforms</a>
                        <a href="{{ route('platforms.create') }}" class="dropdown-item">Add new Platform</a>
                    </div>
                </li>
            </ul>
            <div class="mx-auto" style="max-height:5px;">
            @if (session()->has('info'))
            <div class="alert alert-success"> {{ session('info') }} </div>
            @endif
            @if (session()->has('warning'))
            <div class="alert alert-warning"> {{ session('warning') }} </div>
            @endif
            @if (session()->has('error'))
            <div class="alert alert-danger"> {{ session('error') }} </div>
            @endif
        </div>

            <!-- Right Side Of Navbar -->
            <ul class="navbar-nav ms-auto align-items-md-center gap-2">
                <li class="nav-item">
                    <input class="form-control" type="text" id="search" placeholder="Search">
                </li>
                @auth
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> {{ Auth::user()->name }}
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="{{ route('profile.edit') }}" class="dropdown-item">{{ __('Profile') }}</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">{{ __('Log Out') }}</button>
                            </form>
                        </div>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>