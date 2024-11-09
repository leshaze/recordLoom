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
                        <a href="{{ route('records.create') }}" class="dropdown-item">Add new Record</a>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Artists</a>
                    <div class="dropdown-menu">
                        <a href="{{ route('artists.index') }}" class="dropdown-item">All Artists</a>
                        <a href="{{ route('artists.create') }}" class="dropdown-item">Add new Artist</a>
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
            @if (\Session::has('info'))
            <div class="alert alert-success"> {!! \Session::get('info') !!} </div>
            @endif
            @if (\Session::has('warning'))
            <div class="alert alert-warning"> {!! \Session::get('warning') !!} </div>
            @endif
            @if (\Session::has('error'))
            <div class="alert alert-danger"> {!! \Session::get('error') !!} </div>
            @endif
        </div>

            <!-- Right Side Of Navbar -->
            <ul class="navbar-nav ms-auto">
                <!-- Authentication Links -->

                {{-- @if (Route::has('login.index'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login.index') }}">{{ __('Login') }}</a>
                </li>
                @endif

                @if (Route::has('register.index'))
                <li class="nav-item">
                    <a class="nav-link"
                        href="{{ route('register.index') }}">{{ __('Register') }}</a>
                </li>
                @endif --}}
                <!--  Searchbar -->
                <li>
                    <input class="form-control" type="text" id="search" placeholder="Search">
                </li>
                {{--
                                <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    {{ Auth::user()->name }} - {{ AUTH::user()->role->name }}
                </a>

                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                    <a class="dropdown-item" href="#"
                        onclick="event.preventDefault();
                                        document.getElementById('logout-form').submit();">
                        {{ __('Logout') }}
                    </a>

                    <form id="logout-form" action="{{ route('logout.index') }}" method="POST"
                        class="d-none">
                        @csrf
                    </form>
                </div>
                </li> --}}
            </ul>
        </div>
    </div>
</nav>