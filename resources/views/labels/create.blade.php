<x-app-layout>
    <div class="container">
        <div class="wrapper flex">
            <div class="card">
                <div class="card-header">{{ __('Create Label') }}</div>
                <form action="{{ route('labels.store') }}" method="post" class="Label" enctype="multipart/form-data">
                    @csrf
                    <div class="row p-2">
                        <div class="col-sm-2">
                            <label for="floatingInput">Label</label>
                            <input type="text" class="form-control form-control-sm @error('name') border border-danger @enderror" name="label_name" id="label_name" placeholder="Label" value="{{ old('label_name') }}">
                            @error('label_name')
                            <span class="text-danger small">
                                {{ $message }}
                            </span>
                            @enderror
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
            </div>
            </form>
        </div>
    </div>
    </div>
</x-app-layout>