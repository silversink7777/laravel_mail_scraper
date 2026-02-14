<?php

/**
 * スクレイプキーワード設定
 * 利用ソース: release（TDnet適時開示）, jpx（上場廃止・整理等）
 */
return [
    'release' => [
        'keyword' => [
            ['fff'],
            ['ggg'],
            ['お知らせ'],
        ],
        'sendtime' => [
            '00:01',
            '23:59',
        ],
    ],
    'jpx' => [],

    /*
    | 通知メールの送信先（.env の SCRAPE_MAIL_TO / SCRAPE_MAIL_CC でも指定可）
    */
    'mail_to' => array_filter(array_map('trim', explode(',', env('SCRAPE_MAIL_TO', '')))),
    'mail_cc' => array_filter(array_map('trim', explode(',', env('SCRAPE_MAIL_CC', '')))),
];
