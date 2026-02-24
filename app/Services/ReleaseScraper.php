<?php

namespace App\Services;

use App\Models\ScrapeHistory;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

/**
 * TDnet 適時開示（当日分のみ取得。I_list_01_YYYYMMDD ～ I_list_19_YYYYMMDD）
 * config: sendtime => [開始時刻, 終了時刻], keyword => [ ['キーワード'] ]
 */
class ReleaseScraper
{
    /** TDnet 適時開示 ベースURL */
    private const BASE_URL = 'https://www.release.tdnet.info/inbs/';

    public function scrape(array $config): array
    {
        if (! isset($config['sendtime'][0], $config['keyword']) || ! is_array($config['keyword'])) {
            throw new \InvalidArgumentException(
                "release config requires 'sendtime'=>['HH:MM', optional 'HH:MM'], 'keyword'=>[['キーワード']]"
            );
        }

        $links = [];
        $start = strtotime($config['sendtime'][0]);
        $end = isset($config['sendtime'][1]) ? strtotime($config['sendtime'][1]) : strtotime('23:59');
        $lastValue = ScrapeHistory::getLastValue('release');

        $newLastValue = $lastValue;

        for ($i = 1; $i < 20; $i++) {
            $url = self::BASE_URL . 'I_list_' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . '_' . date('Ymd') . '.html';
            $response = Http::timeout(15)->get($url);

            if (! $response->successful()) {
                break;
            }

            $html = $response->body();
            $crawler = new Crawler($html, $url);

            $rows = $crawler->filter('#main-list-table tr');
            if ($rows->count() === 0) {
                break;
            }

            $breakOuter = false;
            foreach ($rows as $j => $tr) {
                $rowCrawler = new Crawler($tr);
                $a = $rowCrawler->filter('a')->first();
                if ($a->count() === 0) {
                    continue;
                }

                $href = $a->attr('href');
                if ($href === null) {
                    continue;
                }

                if ($lastValue !== null && $href === $lastValue) {
                    $breakOuter = true;
                    break;
                }

                if ($i === 1 && $j === 0) {
                    $newLastValue = $href;
                }

                $tds = $rowCrawler->filter('td');
                if ($tds->count() < 3) {
                    continue;
                }

                $timeStr = trim($tds->eq(0)->text(''));
                $time = strtotime($timeStr);
                if ($time === false || $time < $start || $time > $end) {
                    continue;
                }

                $codeCell = trim($tds->eq(1)->text(''));
                $code = substr($codeCell, 0, 4);
                $companyName = trim($tds->eq(2)->text(''));
                $text = trim($a->text(''));

                foreach ($config['keyword'] as $keyWord) {
                    $main = $keyWord[0] ?? '';
                    if ($main === '' || ! preg_match('/' . preg_quote($main, '/') . '/u', $text)) {
                        continue;
                    }

                    $checked = true;
                    foreach (array_slice($keyWord, 1) as $exclude) {
                        $exclude = trim($exclude, '-');
                        if ($exclude !== '' && preg_match('/' . preg_quote($exclude, '/') . '/u', $text)) {
                            $checked = false;
                            break;
                        }
                    }

                    if ($checked) {
                        $links[] = [
                            'href' => self::BASE_URL . $href,
                            'text' => $text,
                            'code' => $code,
                            'company_name' => $companyName,
                        ];
                        break;
                    }
                }
            }

            if ($breakOuter) {
                break;
            }
        }

        ScrapeHistory::setLastValue('release', $newLastValue);

        return $links;
    }
}
