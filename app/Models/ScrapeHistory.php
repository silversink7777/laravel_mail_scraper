<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScrapeHistory extends Model
{
    protected $table = 'scrape_histories';

    protected $fillable = ['source', 'last_value'];

    /**
     * ソース名で履歴を取得。なければ null。
     */
    public static function getLastValue(string $source): ?string
    {
        $row = static::where('source', $source)->first();

        return $row?->last_value;
    }

    /**
     * ソースの最終処理値を更新。
     */
    public static function setLastValue(string $source, ?string $value): void
    {
        static::updateOrCreate(
            ['source' => $source],
            ['last_value' => $value]
        );
    }
}
