<?php

namespace App\Services;

use App\Models\ScrapeHistory;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

/**
 * JPX 上場廃止・整理等
 */
class JpxScraper
{
    private const URL = 'https://www.jpx.co.jp/markets/equities/suspended/index.html';

    public function scrape(array $config): array
    {
        $response = Http::timeout(15)->get(self::URL);
        if (! $response->successful()) {
            return [];
        }

        $html = $response->body();
        $crawler = new Crawler($html, self::URL);

        // readArea 内の section/div の3番目の div 内の最初の table の tr
        $rows = $crawler->filterXPath('//div[@id="readArea"]/section/div[3]//table[1]//tr');
        if ($rows->count() < 3) {
            return [];
        }

        $links = [];
        $lastValue = ScrapeHistory::getLastValue('jpx') ?? '';
        $newLastValue = $lastValue;

        foreach ($rows as $i => $tr) {
            if ($i < 2) {
                continue;
            }

            $rowCrawler = new Crawler($tr);
            $tds = $rowCrawler->filter('td');
            if ($tds->count() < 2) {
                continue;
            }

            $td0 = trim($tds->eq(0)->text(''));
            $td1 = trim($tds->eq(1)->text(''));
            $key = $td0 . $td1;

            if ($lastValue !== '' && $key === $lastValue) {
                break;
            }

            if ($i === 2) {
                $newLastValue = $key;
            }

            $links[] = [
                'href' => self::URL,
                'text' => $td0,
                'code' => $td1,
            ];
        }

        ScrapeHistory::setLastValue('jpx', $newLastValue);

        return $links;
    }
}
