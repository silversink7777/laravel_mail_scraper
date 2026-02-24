<?php

/**
 * スクレイプキーワード設定
 * 利用ソース: release（TDnet適時開示）, tdnet_search（TDnet検索）, jpx（上場廃止・整理等）, yahoo_weather（Yahoo天気・渋谷）
 */
return [
    'release' => [
        'keyword' => [
            ['fff'],
            ['ＧＭＯペパボ'],
            ['お知らせ'],
        ],
        'sendtime' => [
            '00:01',
            '23:59',
        ],
    ],

    /* TDnet トップページのみ（https://www.release.tdnet.info） keyword: 検索ワード */
    'tdnet_search' => [
        'keyword' => ['検索方法'],
    ],

    'jpx' => [],

    'yahoo_weather' => [
        'keyword' => ['渋谷'],
    ],

    /*
    | 通知メールの送信先（.env の SCRAPE_MAIL_TO / SCRAPE_MAIL_CC でも指定可）
    | mail_to: 複数指定可（カンマ区切り）。すべて To で送信されます。
    | mail_cc: 複数指定可（カンマ区切り）。CC で送信されます。
    */
    'mail_to' => array_filter(array_map('trim', explode(',', env('SCRAPE_MAIL_TO', '')))),
    'mail_cc' => array_filter(array_map('trim', explode(',', env('SCRAPE_MAIL_CC', '')))),
];
