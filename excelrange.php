<?php
/**
 * Plugin Name:       ExcelRange
 * Description:       エクセルファイルのアクティブなシートのA1〜AZ300の範囲をpost_metaに保存してショートコードで呼び出すプラグイン
 * Version:           1.0.3
 * Requires PHP:      7.4
 * Author:            DAI
 * Author URI:        https://etbs.jp
 * Plugin URI:        https://etbs.jp/product-category/wordpress-tools/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       excelrange
 * 
 * @package excelrange
 */

define( 'EXRG_PLUGIN_FILE', __FILE__ );

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once( dirname( __FILE__ ) . '/inc/func.php' );

/*-------------------------------------------*/
/*  プラグインのアップデートチェック
/*-------------------------------------------*/
require 'inc/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/etbsjp/excelrange/',
	__FILE__,
	'excelrange'
);
$myUpdateChecker->setBranch( 'dist' );

/*-------------------------------------------*/
/* プラグインを有効化したときに実行
/*-------------------------------------------*/
if ( ! function_exists( 'excelrange_plugin_activate' ) ){
    function excelrange_plugin_activate() {
        // ここに有効化したときの処理を記述
    }
    register_activation_hook( __FILE__, 'excelrange_plugin_activate' );
}
?>