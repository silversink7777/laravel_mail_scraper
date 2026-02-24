<?php

namespace App\Services;

use App\Models\ScrapeHistory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; // ログ出力用
use Illuminate\Http\Client\ConnectionException; // 通信エラー捕捉用
use Symfony\Component\DomCrawler\Crawler;

/**
 * TDnet トップページのみスクレイプ（https://www.release.tdnet.info）
 * config: keyword => 検索ワード（配列可）
 */
class TdnetSearchScraper
{
    private const TOP_URL = 'https://www.release.tdnet.info';

    private const BASE_URL = 'https://www.release.tdnet.info/inbs/';

    private const SOURCE = 'tdnet_search';

    public function scrape(array $config): array
    {
        try {
            // User-Agentを偽装してリクエスト（※セキュリティ対策による即時ブロックを軽減するため）
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            ])->timeout(15)->get(self::TOP_URL);

        } catch (ConnectionException $e) {
            // タイムアウトや接続エラー（IPブロックで接続すらさせてもらえない場合など）
            Log::error("TDnet Scrape Connection Error: 接続できませんでした。", [
                'message' => $e->getMessage(),
                'url' => self::TOP_URL
            ]);
            return [];
        } catch (\Exception $e) {
            // その他の予期せぬエラー
            Log::error("TDnet Scrape Unexpected Error: " . $e->getMessage());
            return [];
        }

        // HTTPステータスコードが 200 OK 以外だった場合（ここが「弾かれた」判定）
        if (! $response->successful()) {
            Log::warning("TDnet Scrape Blocked: アクセスが拒否または失敗しました。", [
                'status' => $response->status(), // 403, 429, 500 など
                'reason' => $response->reason(), // Forbidden, Too Many Requests など
                'url'    => self::TOP_URL,
                'headers' => $response->headers(), // 相手サーバーからのレスポンスヘッダ
                // エラーページの内容などを記録（先頭500文字）
                'body_preview' => substr($response->body(), 0, 500),
            ]);
            return [];
        }

        // --- 以下、解析処理 ---

        $html = $response->body();
        $crawler = new Crawler($html, self::TOP_URL);

        $rows = $crawler->filter('#main-list-table tr');
        if ($rows->count() === 0) {
            // HTML構造が変わった、またはデータが空の場合
            Log::info("TDnet Scrape: テーブル行が見つかりませんでした。");
            return [];
        }

        $keywords = $config['keyword'] ?? [];
        $keywords = array_filter(is_array($keywords) ? $keywords : [], fn ($k) => trim((string) $k) !== '');

        $links = [];
        $lastValue = ScrapeHistory::getLastValue(self::SOURCE);
        $newLastValue = $lastValue;

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

            // 前回取得した最新のものと同じならループ終了
            if ($lastValue !== null && $href === $lastValue) {
                break;
            }

            // ループの最初（一番上）が最新の記事
            if ($j === 0) {
                $newLastValue = $href;
            }

            $tds = $rowCrawler->filter('td');
            if ($tds->count() < 3) {
                continue;
            }

            $text = trim($a->text(''));
            $matched = empty($keywords);
            
            foreach ($keywords as $keyWord) {
                $word = is_array($keyWord) ? ($keyWord[0] ?? '') : $keyWord;
                $word = trim((string) $word);
                if ($word !== '' && preg_match('/' . preg_quote($word, '/') . '/u', $text)) {
                    $matched = true;
                    break;
                }
            }
            
            if (! $matched) {
                continue;
            }

            $codeCell = trim($tds->eq(1)->text(''));
            $code = substr($codeCell, 0, 4);
            $companyName = trim($tds->eq(2)->text(''));

            $links[] = [
                'href' => str_starts_with($href, 'http') ? $href : (self::BASE_URL . $href),
                'text' => $text,
                'code' => $code,
                'company_name' => $companyName,
            ];
        }

        // 最新の識別子を保存
        ScrapeHistory::setLastValue(self::SOURCE, $newLastValue);

        return $links;
    }
}