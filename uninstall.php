<?php
/**
 * アンインストール処理。
 *
 * ExcelRange は取り込んだ表データを投稿メタ `excelrange_import` に保存している
 * （`inc/tools/import-excel.php:443`）。これは利用者が取り込んだコンテンツそのもので、
 * 削除すると復旧手段が無い。そのため、このプラグインは削除時にデータを一切消さない
 * （task-queue #108 の案A決定）。
 *
 * 独自テーブルも cron も持たないため、案Aに従うと「何もしない」が正しい実装になる。
 * 空関数ではなくこの docblock を残しているのは、「まだ書いていない」と読まれないようにするため。
 *
 * @package excelrange
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

// 意図的に何もしない。
