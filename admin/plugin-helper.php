<?php
// admin/plugin-helper.php

class RaptioPlugin {
    private static $actions = [];

    // プラグイン側から特定のフックに処理を割り込ませるメソッド
    public static function add_action($hook_name, $callback) {
        self::$actions[$hook_name][] = $callback;
    }

    // CMSコア側から登録されたプラグイン処理を呼び出すメソッド
    public static function do_action($hook_name, &$value = null) {
        if (!isset(self::$actions[$hook_name])) return $value;

        foreach (self::$actions[$hook_name] as $callback) {
            if (is_callable($callback)) {
                $value = call_user_func($callback, $value);
            }
        }
        return $value;
    }
}