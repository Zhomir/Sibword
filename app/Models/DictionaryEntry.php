<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DictionaryEntry extends Model
{
    use HasFactory;

    protected $table = 'lexemes';

    protected $fillable = [
        'language_id',
        'word',
        'translation',
        'transcription',
        'complexity_index',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $entry): void {
            if (empty($entry->language_id)) {
                $entry->language_id = static::ensureDefaultLanguageId();
            }
            if (empty($entry->status)) {
                $entry->status = 'published';
            }
        });
    }

    public static function seedDefaultsIfEmpty(): void
    {
        $languageId = static::ensureDefaultLanguageId();
        if (static::query()->where('language_id', $languageId)->exists()) {
            return;
        }

        static::query()->insert(array_map(
            static fn (array $entry) => [
                'language_id' => $languageId,
                'word' => $entry['word'],
                'translation' => $entry['translation'],
                'transcription' => $entry['transcription'] ?? null,
                'complexity_index' => $entry['complexity_index'] ?? 0,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            static::defaultEntries()
        ));
    }

    public static function defaultEntries(): array
    {
        return [
            ['word' => 'Сайн байна', 'translation' => 'Здравствуйте', 'complexity_index' => 0.2],
            ['word' => 'Эжы', 'translation' => 'Мама', 'complexity_index' => 0.1],
            ['word' => 'Аба', 'translation' => 'Папа', 'complexity_index' => 0.1],
            ['word' => 'Ном', 'translation' => 'Книга', 'complexity_index' => 0.2],
            ['word' => 'Һургуули', 'translation' => 'Школа', 'complexity_index' => 0.3],
            ['word' => 'Алтан загаһан', 'translation' => 'Золотая рыбка', 'complexity_index' => 0.4],
        ];
    }

    private static function ensureDefaultLanguageId(): int
    {
        $bxr = DB::table('languages')->where('code', 'bxr')->first();
        if ($bxr) {
            return (int) $bxr->id;
        }

        $legacyBua = DB::table('languages')->where('code', 'bua')->first();
        if ($legacyBua) {
            DB::table('languages')
                ->where('id', (int) $legacyBua->id)
                ->update([
                    'code' => 'bxr',
                    'updated_at' => now(),
                ]);

            return (int) $legacyBua->id;
        }

        return (int) DB::table('languages')->insertGetId([
            'code' => 'bxr',
            'name' => 'Бурятский язык',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
