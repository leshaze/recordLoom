<x-app-layout :title="__('Benutzer')">
    <div class="container-fluid px-lg-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h1 class="h3 mb-0">{{ __('Benutzer') }} <small class="text-body-secondary fs-6">{{ trans_choice(':count Eintrag|:count Einträge', $users->count()) }}</small></h1>
            <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-person-plus"></i> {{ __('Benutzer einladen') }}</a>
        </div>

        @if (session('invitation_link'))
            <div class="alert alert-warning">
                {{ __('Gib :name diesen Link, damit er sein Passwort festlegen kann:', ['name' => session('invitation_link')['name']]) }}
                <input type="text" readonly class="form-control form-control-sm mt-2" value="{{ session('invitation_link')['url'] }}" onclick="this.select()">
            </div>
        @endif

        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle small mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('E-Mail') }}</th>
                            <th>{{ __('Rolle') }}</th>
                            <th class="text-end">{{ __('Eigene Platten') }}</th>
                            <th class="text-end">{{ __('Zugewiesen') }}</th>
                            <th>{{ __('Dabei seit') }}</th>
                            <th class="text-end">{{ __('Aktionen') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td class="fw-semibold">{{ $user->name }} @if ($user->is(auth()->user())) <span class="badge text-bg-secondary fw-normal">{{ __('Du') }}</span> @endif</td>
                                <td class="text-break">{{ $user->email }}</td>
                                <td>
                                    <span @class(['badge', 'text-bg-danger' => $user->isAdmin(), 'text-bg-warning' => $user->isSeller(), 'text-bg-primary' => ! $user->isAdmin() && ! $user->isSeller()])>{{ $user->role->label() }}</span>
                                    @if ($user->seesAllRecords())
                                        <span class="badge text-bg-light border">{{ __('sieht alle Platten') }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($user->records_count)
                                        <a href="{{ route('records.index', ['owner' => $user->id]) }}">{{ $user->records_count }}</a>
                                    @endif
                                </td>
                                <td class="text-end">{{ $user->assigned_records_count ?: '' }}</td>
                                <td>{{ \App\Support\Format::date($user->created_at) }}</td>
                                <td class="text-end text-nowrap">
                                    <form method="POST" action="{{ route('admin.users.invite', $user) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="{{ __('Einladung erneut senden') }}" aria-label="{{ __('Einladung erneut senden') }}"><i class="bi bi-envelope"></i></button>
                                    </form>
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Bearbeiten') }}" aria-label="{{ __('Bearbeiten') }}"><i class="bi bi-pencil-square"></i></a>
                                    @unless ($user->is(auth()->user()))
                                        <x-delete-button :action="route('admin.users.destroy', $user)" :message="__('Benutzer „:name“ wirklich löschen?', ['name' => $user->name])" />
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
