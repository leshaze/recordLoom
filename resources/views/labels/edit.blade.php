<x-app-layout>
    <div class="container">
        <div class="wrapper flex">
            <div class="card">
                <div class="card-header">{{ __('Edit Label') }}</div>
                <form action="{{ route('labels.update', ['label' => $label->id]) }}" method="post" class="Label" enctype="multipart/form-data">
                    @method('PUT')
                    @csrf
                    <div class="row p-2">
                        <div class="col-sm-3">
                            <label for="floatingInput">Label</label>
                            <input type="text" class="form-control form-control-sm @error('name') border border-danger @enderror" name="label_name" id="label_name" placeholder="Label" value="{{ $label->name }}" required>
                            <input type="hidden" name="id" id="id" value="{{ $label->id }}">
                        </div>
                    </div>
                    <div class="row p-2">
                        <div class="col-sm-4">
                            <label for="floatingTextarea2">Beschreibung</label>
                            <textarea class="form-control form-control-sm" placeholder="Beschreibung" name="description" id="description" style="height: 100px">{{ $label->description }}</textarea>
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