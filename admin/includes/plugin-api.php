<?php
// admin/includes/plugin-api.php

class RaptioHook {
    private static $actions = [];
    private static $filters = [];

    // --- Action系 (イベント実行: do_action / add_action) ---
    public static function add(string $tag, callable $callback, int $priority = 10) {
        if (!isset(self::$actions[$tag])) {
            self::$actions[$tag] = [];
        }
        self::$actions[$tag][$priority][] = $callback;
    }

    public static function do(string $tag, ...$args) {
        if (!isset(self::$actions[$tag])) return;
        
        ksort(self::$actions[$tag]);
        foreach (self::$actions[$tag] as $priority_group) {
            foreach ($priority_group as $callback) {
                call_user_func_array($callback, $args);
            }
        }
    }

    // --- Filter系 (データ加工: apply_filters / add_filter) ---
    public static function add_filter(string $tag, callable $callback, int $priority = 10) {
        if (!isset(self::$filters[$tag])) {
            self::$filters[$tag] = [];
        }
        self::$filters[$tag][$priority][] = $callback;
    }

    public static function apply(string $tag, $value, ...$args) {
        if (!isset(self::$filters[$tag])) return $value;

        ksort(self::$filters[$tag]);
        foreach (self::$filters[$tag] as $priority_group) {
            foreach ($priority_group as $callback) {
                // データを書き換えて順次回す
                $value = call_user_func_array($callback, array_merge([$value], $args));
            }
        }
        return $value;
    }
}