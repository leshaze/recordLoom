<x-app-layout>
    <div class="container">
        <div class="card table-responsive">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span>
                    {{ __('Interessen') }}
                    @if ($selectedUser)
                        von {{ $selectedUser->name }}
                    @endif
                    ({{ $records->count() }} {{ $records->count() === 1 ? 'Platte' : 'Platten' }})
                </span>
                <form method="GET" action="{{ route('interests.overview') }}" class="d-flex gap-2">
                    <select name="user" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="User">
                        <option value="">Alle User</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected($selectedUser?->id === $user->id)>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                    <noscript><button type="submit" class="btn btn-sm btn-primary">Filtern</button></noscript>
                </form>
            </div>
            @if ($records->isNotEmpty())
                <table class="table table-sm small mb-0">
                    <tr>
                        <th>Kind</th>
                        <th>Künstler</th>
                        <th>Title</th>
                        <th>Label</th>
                        <th>Aktueller Preis</th>
                        <th>Interessierte User</th>
                    </tr>
                    @foreach ($records as $record)
                        <tr @if ($record->sold) style="text-decoration: line-through;" @endif>
                            <td>{{ $record->kind }}</td>
                            <td><a href="{{ route('artists.show', $record->artist_id) }}">{{ $record->artist->name }}</a></td>
                            <td><a href="{{ route('records.show', $record) }}">{{ $record->title }}</a></td>
                            <td><a href="{{ route('labels.show', $record->label_id) }}">{{ $record->label->name }}</a></td>
                            <td>@if ($record->current_price) {{ $record->current_price }} € @endif</td>
                            <td>
                                @foreach ($record->interestedUsers as $user)
                                    <a href="{{ route('interests.overview', ['user' => $user->id]) }}"
                                        @class(['fw-bold' => $selectedUser?->id === $user->id])>{{ $user->name }}</a>@if (! $loop->last), @endif
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                </table>
            @else
                <div class="card-body">Es wurde noch kein Interesse angemeldet.</div>
            @endif
        </div>
    </div>
</x-app-layout>
