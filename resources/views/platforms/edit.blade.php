<x-app-layout>
    <div class="container">
        <div class="wrapper flex">
            <div class="card">
                <div class="card-header">{{ __('Edit Platform') }}</div>
                <form action="{{ route('platforms.update', ['platform' => $platform->id]) }}" method="post" class="Label" enctype="multipart/form-data">
                    @method('PUT')
                    @csrf
                    <div class="row p-2">
                        <div class="col-sm-3">
                            <label for="floatingInput">Platform</label>
                            <input type="text" class="form-control form-control-sm @error('name') border border-danger @enderror" name="platform_name" id="platform_name" placeholder="Platform" value="{{ $platform->name }}" required>
                            <input type="hidden" name="id" id="id" value="{{ $platform->id }}">
                        </div>
                    </div>
                    <div class="row p-2">
                        <div class="col-sm-3">
                            <label for="floatingInput">URL</label>
                            <input type="text" class="form-control form-control-sm @error('url') border border-danger @enderror" name="url" id="url" placeholder="URL" value="{{ $platform->url }}">
                        </div>
                    </div>
                    <div class="row p-2">
                        <div class="col-sm-4">
                            <label for="floatingTextarea2">Beschreibung</label>
                            <textarea class="form-control form-control-sm" placeholder="Beschreibung" name="description" id="description" style="height: 100px">{{ $platform->description }}</textarea>
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