<?php

namespace App\Console\Commands;

use App\Mail\ScrapeNotificationMail;
use App\Services\JpxScraper;
use App\Services\ReleaseScraper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ScrapeAndNotifyCommand extends Command
{
    protected $signature = 'scrape:notify';

    protected $description = 'TDnet・JPX をスクレイプし、マッチ時にメール送信';

    public function handle(ReleaseScraper $releaseScraper, JpxScraper $jpxScraper): int
    {
        $configs = config('scrape');
        $sources = ['release' => $releaseScraper, 'jpx' => $jpxScraper];

        $matched = [];

        Log::info('[Run] Start ' . now()->format('Y-m-d H:i:s'));

        foreach ($configs as $key => $value) {
            if (in_array($key, ['mail_to', 'mail_cc'], true)) {
                continue;
            }
            if (! is_array($value)) {
                continue;
            }

            $scraper = $sources[$key] ?? null;
            if ($scraper === null) {
                continue;
            }

            try {
                Log::info('[Run] Processing ' . $key . '...');
                $links = $scraper->scrape($value);
                $count = is_array($links) ? count($links) : 0;
                Log::info('[Run] ' . $key . ': ' . $count . ' match(es)');

                if (! empty($links)) {
                    $matched[$key] = $links;
                }
            } catch (\Throwable $e) {
                Log::error($key . ' Exception: ' . $e->getMessage());
            }
        }

        if (empty($matched)) {
            $this->info('ニュースが1件もマッチしませんでした。履歴やサイト構造を確認してください。');
            Log::info('[Run] No match, mail skipped');
            Log::info('[Run] End');
            return self::SUCCESS;
        }

        $this->info('マッチ成功！メール送信処理に入ります。');
        Log::info('[Run] Sending mail (' . count($matched) . ' source(s))');

        $to = config('scrape.mail_to', []);
        $cc = config('scrape.mail_cc', []);

        if (empty($to)) {
            Log::warning('[Run] 送信先が未設定のためメールを送信しません。config/scrape.php または .env の SCRAPE_MAIL_TO を設定してください。');
            Log::info('[Run] End');
            return self::SUCCESS;
        }

        $firstTo = array_shift($to);
        $recipients = Mail::to($firstTo);
        foreach ($to as $address) {
            $recipients->cc($address);
        }
        foreach ($cc as $address) {
            $recipients->cc($address);
        }

        try {
            $recipients->send(new ScrapeNotificationMail($matched));
        } catch (\Throwable $e) {
            Log::error('Mail send error: ' . $e->getMessage());
            throw $e;
        }

        Log::info('[Run] End');
        return self::SUCCESS;
    }
}
