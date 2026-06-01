<?php
// includes/widget-registry.php

function get_all_widget_definitions() {
    $layout_options = [
        'title-only'        => 'タイトルのみ',
        'thumb-small-side'  => 'サムネイル小・タイトル横',
        'thumb-only'        => 'サムネイルのみ（グリッド）',
        'thumb-large-under' => 'サムネイル大・タイトル下',
        'thumb-large-hover' => 'サムネイル大・タイトルホバー'
    ];

    return [
        'recent' => [
            'label' => '新着記事', 
            'desc' => '最近の投稿をリスト表示', 
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'タイトル'],
                'count' => ['type' => 'number', 'label' => '表示数'],
                'layout' => ['type' => 'select', 'label' => 'レイアウト', 'options' => $layout_options]
            ]
        ],
        'popular' => [
            'label' => '人気記事', 
            'desc' => '閲覧数の多い記事を表示', 
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'タイトル'],
                'count' => ['type' => 'number', 'label' => '表示数'],
                'layout' => ['type' => 'select', 'label' => 'レイアウト', 'options' => $layout_options]
            ]
        ],
        'text' => [
            'label' => 'テキスト', 
            'desc' => '任意のテキストを表示', 
            'fields' => ['title' => ['type' => 'text', 'label' => 'タイトル'], 'content' => ['type' => 'textarea', 'label' => '内容']]
        ],
        'html' => [
            'label' => 'HTML', 
            'desc' => 'カスタムHTMLコード', 
            'fields' => ['html_code' => ['type' => 'textarea', 'label' => 'HTMLコード']]
        ],
        'categories' => [
            'label' => 'カテゴリー', 
            'desc' => '記事のカテゴリー一覧', 
            'fields' => ['title' => ['type' => 'text', 'label' => 'タイトル']]
        ],
        'search' => [
            'label' => '検索', 
            'desc' => 'サイト内検索フォーム', 
            'fields' => ['title' => ['type' => 'text', 'label' => 'タイトル']]
        ]
    ];
}

function get_widget_definition($type) {
    $defs = get_all_widget_definitions();
    return $defs[$type] ?? null;
}