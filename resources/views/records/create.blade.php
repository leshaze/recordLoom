<x-app-layout>
    <div class="container">
        <div class="wrapper flex">
            <div class="card" id="recordCreate">
                <div class="card-header">{{ __('Create Record') }}</div>
                <form action="{{ route('records.store') }}" method="post" class="Record" enctype="multipart/form-data">
                    @csrf
                    <div class="row p-2">
                        <div class="col-sm-1">
                            <input class="form-check-input" type="radio" name="kind" id="Radios1" value="LP"
                                @if (old('kind') !='CD' ) checked @endif>
                            <label class="form-check-label" for="Radios1">
                                LP
                            </label><br>
                            <input class="form-check-input" type="radio" name="kind" id="Radios2" value="CD"
                                @if (old('kind')=='CD' ) checked @endif>
                            <label class="form-check-label" for="Radios2">
                                CD
                            </label>
                        </div>
                    </div>
                    <div class="row p-2">
                        <div class="col-sm-2">
                            <label for="floatingInput">Künstler</label>
                            <input type="text" name="artist_name" id="artist_name"
                                class="form-control form-control-sm @error('artist_name') border border-danger @enderror" placeholder="Künstler"
                                autofocus="" value="{{ old('artist_name') }}">
                            @error('artist_name')
                            <span class="text-danger small">
                                {{ $message }}
                            </span>
                            @enderror
                            <input type="hidden" name="artist_id" id="artist_id" value="{{ old('artist_id') }}">
                        </div>
                        <div class="col-sm-2">
                            <label for="title">Titel</label>
                            <input type="text" name="title" id="title"
                                class="form-control form-control-sm @error('title') border border-danger @enderror" placeholder="Titel"
                                autofocus="" value="{{ old('title') }}">
                                @error('title')
                            <span class="text-danger small">
                                {{ $message }}
                            </span>
                            @enderror
                            <input type="hidden">
                        </div>
                        <div class="col-sm-2">
                            <label for="label_name">Label</label>
                            <input type="text" name="label_name" id="label_name"
                                class="form-control form-control-sm @error('label_name') border border-danger @enderror" placeholder="Label"
                                autofocus="" value="{{ old('label_name') }}">
                                @error('label_name')
                            <span class="text-danger small">
                                {{ $message }}
                            </span>
                            @enderror
                            <input type="hidden" name="label_id" id="label_id" value="{{ old('label_id') }}">
                        </div>
                    </div>
                    <div class="row p-2">
                        <div class="col-sm-2">
                            <label for="barcode">Barcode</label>
                            <input type="text" name="barcode" id="barcode" class="form-control form-control-sm" placeholder="Barcode"
                                autofocus="" value="{{ old('barcode') }}">
                            <input type="hidden">
                        </div>
                        <div class="col-sm-2">
                            <label for="catalog_number">Katalog-Nr.</label>
                            <input type="text" name="catalog_number" id="catalog_number" class="form-control form-control-sm"
                                placeholder="Katalog-Nr." autofocus="" value="{{ old('catalog_number') }}">
                            <input type="hidden">
                        </div>
                        <div class="col-sm-2">
                            <label for="matrix_number">Matrix-Nr.</label>
                            <input type="text" name="matrix_number" id="matrix_number" class="form-control form-control-sm"
                                placeholder="Matrix-Nr." autofocus="" value="{{ old('matrix_number') }}">
                            <input type="hidden">
                        </div>
                        <div class="col-sm-2">
                            <label for="archive_number">Archive-Nummer</label>
                            <input type="text" name="archive_number" id="archive_number" class="form-control form-control-sm"
                                placeholder="Archive-Nummer" autofocus="" value="{{ old('archive_number') }}">
                            <input type="hidden">
                        </div>
                    </div>
                    <div class="row p-2">
                        <div class="col-sm-2">
                            <label for="country">Herkunftsland</label>
                            <input type="text" name="country_name" id="country_name" class="form-control form-control-sm"
                                placeholder="Herkunftsland" autofocus="" value="{{ old('country_name') }}">
                            <input type="hidden" name="country_id" id="country_id" value="{{ old('country_id') }}">
                        </div>
                        <div class="col-sm-2">
                            <label for="release_date">Veröffentlichungsdat.</label>
                            <input type="text" name="release_date" id="release_date" class="form-control form-control-sm"
                                placeholder="Veröffentlichungsdat." autofocus="" value="{{ old('release_date') }}">
                            <input type="hidden">
                        </div>
                        <div class="col-sm-2">
                            <label for="reissue_date">Datum Neuauflage</label>
                            <input type="text" name="reissue_date" id="reissue_date" class="form-control form-control-sm"
                                placeholder="Datum Neuauflage" autofocus="" value="{{ old('reissue_date') }}">
                            <input type="hidden">
                        </div>
                        <div class="col-sm-2">
                            <label for="platform">Anbieter</label>
                            <input type="text" name="platform" id="platform" class="form-control form-control-sm @error('platform') border border-danger @enderror"
                                placeholder="Anbieter" autofocus="" value="{{ old('platform') }}">
                            <input type="hidden" name="platform_id" id="platform_id" value="{{ old('platform_id') }}">
                        </div>
                    </div>
                    <div class="row p-2">

                        <div class="col-sm-2">
                            <label for="grading_media">Grading</label>
                            <select name="grading_media" class="custom-select form-control form-control-sm">
                                <option value="" @if (old('grading_media')=='' ) selected @endif>Grading Media</option>
                                <option value="100" @if (old('grading_media')=='100' ) selected @endif>100% - GER: M- / US: NM
                                </option>
                                <option value="85" @if (old('grading_media')=='85' ) selected @endif>85% - GER: M-- / US: NM
                                </option>
                                <option value="70" @if (old('grading_media')=='70' ) selected @endif>70% - GER: VG++ / US: VG+
                                </option>
                                <option value="50" @if (old('grading_media')=='50' ) selected @endif>50% - GER: VG+ / US: VG+
                                </option>
                                <option value="35" @if (old('grading_media')=='35' ) selected @endif>35% - GER: VG / US: VG
                                </option>
                                <option value="25" @if (old('grading_media')=='25' ) selected @endif>25% - GER: VG- / US: VG
                                </option>
                                <option value="15" @if (old('grading_media')=='15' ) selected @endif>15% - GER: VG-- / US: VG-
                                </option>
                                <option value="10" @if (old('grading_media')=='10' ) selected @endif>10% - GER: G+ / US: VG-
                                </option>
                                <option value="5" @if (old('grading_media')=='5' ) selected @endif>5% - GER G / US: G
                                </option>
                            </select>
                        </div>
                        <div class="col-sm-2">
                            <label for="grading_cover"></label>
                            <select name="grading_cover" class="custom-select form-control form-control-sm">
                                <option value="" @if (old('grading_cover')=='' ) selected @endif>Grading Cover</option>
                                <option value="100" @if (old('grading_cover')=='100' ) selected @endif>100% - GER: M- / US: NM
                                </option>
                                <option value="85" @if (old('grading_cover')=='85' ) selected @endif>85% - GER: M-- / US: NM
                                </option>
                                <option value="70" @if (old('grading_cover')=='70' ) selected @endif>70% - GER: VG++ / US: VG+
                                </option>
                                <option value="50" @if (old('grading_cover')=='50' ) selected @endif>50% - GER: VG+ / US: VG+
                                </option>
                                <option value="35" @if (old('grading_cover')=='35' ) selected @endif>35% - GER: VG / US: VG
                                </option>
                                <option value="25" @if (old('grading_cover')=='25' ) selected @endif>25% - GER: VG- / US: VG
                                </option>
                                <option value="15" @if (old('grading_cover')=='15' ) selected @endif>15% - GER: VG-- / US: VG-
                                </option>
                                <option value="10" @if (old('grading_cover')=='10' ) selected @endif>10% - GER: G+ / US: VG-
                                </option>
                                <option value="5" @if (old('grading_cover')=='5' ) selected @endif>5% - GER G / US: G
                                </option>
                            </select>
                        </div>
                        <div class="col-sm-2">
                            <label for="current_price">Aktueller Preis €</label>
                            <input type="text" name="current_price" id="current_price" class="form-control form-control-sm @error('current_price') border border-danger @enderror"
                                placeholder="Aktueller Preis €" autofocus="" value="{{ old('current_price') }}">
                            <input type="hidden">
                        </div>
                        <div class="col-sm-2">
                            <label for="buy_price">Kaufpreis €</label>
                            <input type="text" name="buy_price" id="buy_price" class="form-control form-control-sm" placeholder="Kaufpreis €"
                                autofocus="" value="{{ old('buy_price') }}">
                            <input type="hidden">
                        </div>
                    </div>
                    <div class="row p-2">
                        <div class="col-sm-4">
                            <label for="floatingTextarea2">Beschreibung</label>
                            <textarea class="form-control form-control-sm" placeholder="Beschreibung" name="note" id="note" style="height: 100px">{{ old('note') }}</textarea>
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