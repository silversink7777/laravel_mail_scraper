<?php

namespace App\Services;

use App\Models\ScrapeHistory;
use Illuminate\Support\Facades\Http;

/**
 * Yahoo天気（渋谷）ページに指定キーワードが含まれるかチェック
 * 履歴で「前回もマッチ済み」の場合は重複送信しない
 */
class YahooWeatherScraper
{
    private const URL = 'https://weather.yahoo.co.jp/weather/jp/13/4410/13113.html';

    private const SOURCE = 'yahoo_weather';

    public function scrape(array $config): array
    {
        $keywords = $config['keyword'] ?? [];
        $keywords = array_filter(is_array($keywords) ? $keywords : []);

        if (empty($keywords)) {
            return [];
        }

        $response = Http::timeout(15)->get(self::URL);
        if (! $response->successful()) {
            return [];
        }

        $body = $response->body();
        $lastValue = ScrapeHistory::getLastValue(self::SOURCE);

        foreach ($keywords as $keyword) {
            $word = is_array($keyword) ? ($keyword[0] ?? '') : $keyword;
            $word = trim((string) $word);
            if ($word === '') {
                continue;
            }

            if (! str_contains($body, $word)) {
                if ($lastValue === 'found') {
                    ScrapeHistory::setLastValue(self::SOURCE, 'not_found');
                }
                return [];
            }
        }

        // キーワードがすべて含まれている
        if ($lastValue === 'found') {
            return [];
        }

        ScrapeHistory::setLastValue(self::SOURCE, 'found');

        return [
            [
                'href' => self::URL,
                'text' => 'Yahoo天気（渋谷）に「' . implode('」「', $keywords) . '」が表示されています',
            ],
        ];
    }
}
