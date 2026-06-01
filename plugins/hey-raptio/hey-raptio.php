<?php
/*
Plugin Name: Hey Raptio
Plugin URI:  https://raptio-cms.example.com/plugins/hey-raptio
Description: Raptio CMS用公式プラグイン。管理画面ダッシュボードに専用ウィジェットを追加し、システム状態を表示します。
Version:     1.0.0
Requires at least: 1.0.0
Requires PHP: 8.0
Author:      Raptio Developer
Author URI:  https://developer.example.com
License:     GPLv2
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
Text Domain: hey-raptio
Domain Path: /languages
*/

class HeyRaptioPlugin {
    /**
     * プラグインの初期化
     */
    public function __construct() {
        // システム側のダッシュボードウィジェット用フックに処理を登録
        // 既存のシステムと連携するために RaptioHook を使用
        if (class_exists('RaptioHook')) {
            RaptioHook::add('dashboard_widgets', [$this, 'render_widget'], 10);
        }
    }

    /**
     * ダッシュボードウィジェットの表示内容
     */
    public function render_widget() {
        ?>
        <div class="settings-card" style="background: #ffffff; border: 1px solid #ccd0d4; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,0.04); padding: 16px; margin-bottom: 20px;">
            <h2 style="margin: 0 0 12px 0; font-size: 1.3em; color: #2271b1; border-bottom: 2px solid #2271b1; padding-bottom: 8px;">
                Hey Raptio!
            </h2>
            <p style="margin: 0 0 16px 0; color: #3c434a; line-height: 1.5;">
                Raptio CMSは現在正常に稼働しています。このウィジェットはプラグインシステムによって動的にレンダリングされています。
            </p>
            <div class="plugin-info" style="font-size: 0.85em; color: #646970; border-top: 1px solid #f0f0f1; padding-top: 10px;">
                <strong>Status:</strong> Active<br>
                <strong>Version:</strong> 1.0.0<br>
                <strong>License:</strong> GPLv2
            </div>
        </div>
        <?php
    }
}

// プラグインのインスタンス化
new HeyRaptioPlugin();