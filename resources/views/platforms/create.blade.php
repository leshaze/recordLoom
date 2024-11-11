<x-app-layout>
    <div class="container">
        <div class="wrapper flex">
            <div class="card">
                <div class="card-header">{{ __('Create Platform') }}</div>
                <form action="{{ route('platforms.store') }}" method="post" class="Label" enctype="multipart/form-data">
                    @csrf
                    <div class="row p-2">
                        <div class="col-sm-2">
                            <label for="floatingInput">Platform</label>
                            <input type="text" class="form-control form-control-sm @error('platform_name') border border-danger @enderror" name="platform_name" id="platform_name" placeholder="Platform" value="{{ old('platform_name') }}">
                            @error('platform_name')
                            <span class="text-danger small">
                                {{ $message }}
                            </span>
                            @enderror
                        </div>
                        <div class="col-sm-2">
                            <label for="floatingInput">URL</label>
                            <input type="text" class="form-control form-control-sm @error('url') border border-danger @enderror" name="url" id="url" placeholder="URL" value="{{ old('url') }}">
                        </div>
                    </div>
                    <div class="row p-2">
                        <div class="col-sm-4">
                            <label for="floatingTextarea2">Beschreibung</label>
                            <textarea class="form-control form-control-sm" placeholder="Beschreibung" name="description" id="description" style="height: 100px">{{ old('description') }}</textarea>
                        </div>
                    </div>
                    <div class="p-2">
                        <button style="min-width:70px; max-width:90px" type="submit" class="btn btn-success btn-sm">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>