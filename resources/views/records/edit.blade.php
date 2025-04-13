<x-app-layout>
    <div class="container">
        <div class="wrapper flex">
            <div class="card" id="recordEdit">
                <div class="card-header">{{ __('Edit Record') }}</div>
                <form action="{{ route('records.update', ['record' => $record->id]) }}" method="post" class="Record" enctype="multipart/form-data">
                    @method('PUT')
                    @csrf
                    <div class="row p-2">
                        <div class="col-sm-1">
                            <input class="form-check-input" type="radio" name="kind" id="Radios1" value="LP"
                                @if ($record->kind == 'LP') checked @endif>
                            <label class="form-check-label" for="Radios1">
                                LP
                            </label><br>
                            <input class="form-check-input" type="radio" name="kind" id="Radios2" value="CD"
                                @if ($record->kind == 'CD') checked @endif>
                            <label class="form-check-label" for="Radios2">
                                CD
                            </label>
                        </div>
                        <div class="col-sm-2">
                            <input class="form-check-input" type="checkbox" id="sold" name="sold"
                                @if ($record->sold) checked @endif>
                            <label class="form-check-label" for="sold">Verkauft</label><br>
                            <input class="form-check-input" type="checkbox" id="lost" name="lost"
                                @if ($record->lost) checked @endif>
                            <label class="form-check-label" for="lost">Verloren</label><br>
                        </div>
                    </div>
                    <div class="row p-2">
                        <div class="col-sm-3">
                            <label for="floatingInput">Künstler</label>
                            @error('artist_name')
                            <span class="text-danger">
                                {{ $message }}
                            </span>
                            @enderror
                            <input type="text" name="artist_name" id="artist_name"
                                class="form-control form-control-sm @error('artist_name') border border-danger @enderror" placeholder="Künstler"
                                autofocus="" value="{{ $record->artist->name }}" required>
                            <input type="hidden" name="artist_id" id="artist_id" value="{{ $record->artist_id }}">
                        </div>
                        <div class="col-sm-3">
                            <label for="title">Titel</label>
                            @error('title')
                            <span class="text-danger">
                                {{ $message }}
                            </span>
                            @enderror
                            <input type="text" name="title" id="title"
                                class="form-control form-control-sm @error('title') border border-danger @enderror" placeholder="Titel"
                                autofocus="" value="{{ $record->title }}">
                            <input type="hidden">
                        </div>
                        <div class="col-sm-3">
                            <label for="label_name">Label</label>
                            @error('label_name')
                            <span class="text-danger">
                                {{ $message }}
                            </span>
                            @enderror
                            <input type="text" name="label_name" id="label_name"
                                class="form-control form-control-sm @error('label_name') border border-danger @enderror" placeholder="Label"
                                autofocus="" value="{{ $record->label->name }}">
                            <input type="hidden" name="label_id" id="label_id" value="{{ $record->label_id }}">
                        </div>
                    </div>
                    <div class="row p-2">
                        <div class="col-sm-2">
                            <label for="barcode">Barcode</label>
                            <input type="text" name="barcode" id="barcode" class="form-control form-control-sm" placeholder="Barcode"
                                autofocus="" value="{{ $record->barcode }}">
                            <input type="hidden">
                        </div>
                        <div class="col-sm-2">
                            <label for="catalog_number">Katalog-Nr.</label>
                            <input type="text" name="catalog_number" id="catalog_number" class="form-control form-control-sm"
                                placeholder="Katalog-Nr." autofocus="" value="{{ $record->catalog_number }}">
                            <input type="hidden">
                        </div>
                        <div class="col-sm-2">
                            <label for="matrix_number">Matrix-Nr.</label>
                            <input type="text" name="matrix_number" id="matrix_number" class="form-control form-control-sm"
                                placeholder="Matrix-Nr." autofocus="" value="{{ $record->matrix_number }}">
                            <input type="hidden">
                        </div>
                        <div class="col-sm-2">
                            <label for="archive_number">Archive-Nummer</label>
                            <input type="text" name="archive_number" id="archive_number" class="form-control form-control-sm"
                                placeholder="Archive-Nummer" autofocus="" value="{{ $record->archive_number }}">
                            <input type="hidden">
                        </div>
                    </div>
                    <div class="row p-2">
                        <div class="col-sm-2">
                            <label for="country">Herkunftsland</label>
                            <input type="text" name="country_name" id="country_name" class="form-control form-control-sm"
                                placeholder="Herkunftsland" autofocus="" value="@if ($record->country_id) {{ $record->country->name }} @endif">
                            <input type="hidden" name="country_id" id="country_id" value="{{ $record->country_id }}">
                        </div>
                        <div class="col-sm-2">
                            <label for="release_date">Veröffentlichungsdat.</label>
                            <input type="text" name="release_date" id="release_date" class="form-control form-control-sm"
                                placeholder="Veröffentlichungsdat." autofocus="" value="{{ $record->release_date }}">
                            <input type="hidden">
                        </div>
                        <div class="col-sm-2">
                            <label for="reissue_date">Datum Neuauflage</label>
                            <input type="text" name="reissue_date" id="reissue_date" class="form-control form-control-sm"
                                placeholder="Datum Neuauflage" autofocus="" value="{{ $record->reissue_date }}">
                            <input type="hidden">
                        </div>
                        <div class="col-sm-2">
                            <label for="platform">Anbieter</label>
                            <input type="text" name="platform" id="platform" class="form-control form-control-sm @error('platform') border border-danger @enderror"
                                placeholder="Anbieter" autofocus="" value="@if ($record->platform) {{ $record->platform->name }} @endif">
                            <input type="hidden" name="platform_id" id="platform_id" value="{{ $record->platform_id }}">
                        </div>
                    </div>
                    <div class="row p-2">
                        <div class="col-sm-2">
                            <label for="grading_media">Grading</label>
                            <select name="grading_media" class="custom-select form-control form-control-sm">
                                <option value="0" @if ($record->grading_media == '') selected @endif>Grading Media</option>
                                <option value="100" @if ($record->grading_media == '100') selected @endif>100% - GER: M- / US: NM
                                </option>
                                <option value="85" @if ($record->grading_media == '85') selected @endif>85% - GER: M-- / US: NM
                                </option>
                                <option value="70" @if ($record->grading_media == '70') selected @endif>70% - GER: VG++ / US: VG+
                                </option>
                                <option value="50" @if ($record->grading_media == '50') selected @endif>50% - GER: VG+ / US: VG+
                                </option>
                                <option value="35" @if ($record->grading_media == '35') selected @endif>35% - GER: VG / US: VG
                                </option>
                                <option value="25" @if ($record->grading_media == '25') selected @endif>25% - GER: VG- / US: VG
                                </option>
                                <option value="15" @if ($record->grading_media == '15') selected @endif>15% - GER: VG-- / US: VG-
                                </option>
                                <option value="10" @if ($record->grading_media == '10') selected @endif>10% - GER: G+ / US: VG-
                                </option>
                                <option value="5" @if ($record->grading_media == '5') selected @endif>5% - GER G / US: G
                                </option>
                            </select>
                        </div>
                        <div class="col-sm-2">
                            <label for="grading_cover"></label>
                            <select name="grading_cover" class="custom-select form-control form-control-sm">
                                <option value="0" @if ($record->grading_cover == '') selected @endif>Grading Cover</option>
                                <option value="100" @if ($record->grading_cover == '100') selected @endif>100% - GER: M- / US: NM
                                </option>
                                <option value="85" @if ($record->grading_cover == '85') selected @endif>85% - GER: M-- / US: NM
                                </option>
                                <option value="70" @if ($record->grading_cover == '70') selected @endif>70% - GER: VG++ / US: VG+
                                </option>
                                <option value="50" @if ($record->grading_cover == '50') selected @endif>50% - GER: VG+ / US: VG+
                                </option>
                                <option value="35" @if ($record->grading_cover == '35') selected @endif>35% - GER: VG / US: VG
                                </option>
                                <option value="25" @if ($record->grading_cover == '25') selected @endif>25% - GER: VG- / US: VG
                                </option>
                                <option value="15" @if ($record->grading_cover == '15') selected @endif>15% - GER: VG-- / US: VG-
                                </option>
                                <option value="10" @if ($record->grading_cover == '10') selected @endif>10% - GER: G+ / US: VG-
                                </option>
                                <option value="5" @if ($record->grading_cover == '5') selected @endif>5% - GER G / US: G
                                </option>
                            </select>
                        </div>
                        <div class="col-sm-2">
                            <label for="current_price">Aktueller Preis €</label>
                            @error('current_price')
                            <span class="text-danger">
                                {{ $message }}
                            </span>
                            @enderror
                            <input type="text" name="current_price" id="current_price" class="form-control form-control-sm @error('current_price') border border-danger @enderror"
                                placeholder="Aktueller Preis €" autofocus="" value="{{ $record->current_price }}">
                            <input type="hidden">
                        </div>
                        <div class="col-sm-2">
                            <label for="buy_price">Kaufpreis €</label>
                            <input type="text" name="buy_price" id="buy_price" class="form-control form-control-sm" placeholder="Kaufpreis €"
                                autofocus="" value="{{ $record->buy_price }}">
                            <input type="hidden">
                        </div>
                    </div>
                    <div class="row p-2">
                        <div class="col-sm-2">
                            <label for="sold_date" id="sold_date_label"
                                @if (!$record->sold) style="display: none" @endif>Verkaufsdatum</label>
                            <input type="text" name="sold_date" id="sold_date" class="form-control form-control-sm"
                                placeholder="Verkaufsdatum" autofocus=""
                                @if (!$record->sold) style="display: none" @endif
                            value="{{ $record->sold_date }}">
                        </div>
                        <div class="col-sm-2">
                            <label for="sold_to" id="sold_to_label"
                                @if (!$record->sold) style="display: none" @endif>Verkauf an</label>
                            <input type="text" name="sold_to" id="sold_to" class="form-control form-control-sm" placeholder="Verkauf an"
                                autofocus="" @if (!$record->sold) style="display: none" @endif
                            value="{{ $record->sold_to }}">
                        </div>
                        <div class="col-sm-2">
                            <label for="sold_price" id="sold_price_label"
                                @if (!$record->sold) style="display: none" @endif>Verkaufspreis €</label>
                            <input type="int" name="sold_price" id="sold_price" class="form-control form-control-sm"
                                placeholder="Verkaufspreis €" autofocus=""
                                @if (!$record->sold) style="display: none" @endif
                            value="{{ $record->sold_price }}">
                        </div>
                    </div>
                    <div class="row p-2">
                        <div class="col-sm-4">
                            <label for="floatingTextarea2">Beschreibung</label>
                            <textarea class="form-control form-control-sm" placeholder="Beschreibung" name="note" id="note" style="height: 100px"> @if ($record->note) {{ $record->note }} @endif</textarea>
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