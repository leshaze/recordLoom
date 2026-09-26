<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - for_sale: unused duplicate of "selling", values are carried over before the column is dropped
 * - release_date / reissue_date (free text) become release_year / reissue_year
 * - sold_date (free text) becomes a real date
 * - cover images and "Zusatzinfos" (editions like Boxset or Erstpressung)
 *
 * No information is lost: text that can not be converted exactly is appended to the note.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->unsignedSmallInteger('release_year')->nullable();
            $table->unsignedSmallInteger('reissue_year')->nullable();
            $table->date('sold_on')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('cover_thumbnail_path')->nullable();
        });

        DB::table('records')->where('for_sale', true)->update(['selling' => true]);

        DB::table('records')->orderBy('id')->each(function ($record) {
            $notes = [];

            [$releaseYear, $keep] = $this->toYear($record->release_date);
            if ($keep) {
                $notes[] = 'Veröffentlichung (alt): '.trim($record->release_date);
            }

            [$reissueYear, $keep] = $this->toYear($record->reissue_date);
            if ($keep) {
                $notes[] = 'Neuauflage (alt): '.trim($record->reissue_date);
            }

            $soldOn = $this->toDate($record->sold_date);
            if ($soldOn === null && filled($record->sold_date)) {
                $notes[] = 'Verkaufsdatum (alt): '.trim($record->sold_date);
            }

            $note = $record->note;
            if ($notes) {
                $note = trim(trim((string) $note)."\n".implode("\n", $notes));
            }

            DB::table('records')->where('id', $record->id)->update([
                'release_year' => $releaseYear,
                'reissue_year' => $reissueYear,
                'sold_on' => $soldOn,
                'note' => $note,
            ]);
        });

        Schema::table('records', function (Blueprint $table) {
            $table->dropColumn(['for_sale', 'release_date', 'reissue_date', 'sold_date']);
        });

        Schema::create('editions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name')->unique();
        });

        Schema::create('edition_record', function (Blueprint $table) {
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('record_id')->constrained()->cascadeOnDelete();
            $table->primary(['edition_id', 'record_id']);
        });

        $now = now();
        DB::table('editions')->insert(collect(['Boxset', 'Erstpressung', 'Limitierte Auflage', 'Picture Disc', 'Farbiges Vinyl'])
            ->map(fn ($name) => ['name' => $name, 'created_at' => $now, 'updated_at' => $now])
            ->all());
    }

    public function down(): void
    {
        Schema::dropIfExists('edition_record');
        Schema::dropIfExists('editions');

        Schema::table('records', function (Blueprint $table) {
            $table->boolean('for_sale')->default(false);
            $table->string('release_date')->nullable();
            $table->string('reissue_date')->nullable();
            $table->string('sold_date')->nullable();
        });

        DB::table('records')->orderBy('id')->each(function ($record) {
            DB::table('records')->where('id', $record->id)->update([
                'release_date' => $record->release_year,
                'reissue_date' => $record->reissue_year,
                'sold_date' => $record->sold_on ? Carbon::parse($record->sold_on)->format('d.m.Y') : null,
            ]);
        });

        Schema::table('records', function (Blueprint $table) {
            $table->dropColumn(['release_year', 'reissue_year', 'sold_on', 'cover_path', 'cover_thumbnail_path']);
        });
    }

    /**
     * Returns the year and whether the original text holds more than the year.
     *
     * @return array{0: int|null, 1: bool}
     */
    private function toYear(?string $value): array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return [null, false];
        }

        if (preg_match('/^(19|20)\d{2}$/', $value)) {
            return [(int) $value, false];
        }

        if (preg_match('/\b((?:19|20)\d{2})\b/', $value, $matches)) {
            return [(int) $matches[1], true];
        }

        return [null, true];
    }

    private function toDate(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['d.m.Y', 'j.n.Y', 'Y-m-d', 'd/m/Y', 'd.m.y', 'j.n.y'] as $format) {
            $date = DateTime::createFromFormat('!'.$format, $value);
            $errors = DateTime::getLastErrors();
            $valid = $errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0);
            if ($date && $valid && (int) $date->format('Y') >= 1900 && (int) $date->format('Y') <= 2100) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }
};
