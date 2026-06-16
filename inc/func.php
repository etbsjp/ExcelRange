<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/*-------------------------------------------*/
/* Load Module
/*-------------------------------------------*/
require_once( dirname( __FILE__ ) . '/tools/import-excel.php' );

/*-------------------------------------------*/
/* 依存するプラグインのチェック
/*-------------------------------------------*/
if ( ! function_exists( 'exrg_check_required_plugins' ) ) {
	function exrg_check_required_plugins() {
		$required_plugins = [
			'CBX PhpSpreadSheet Library' => 'cbxphpspreadsheet/cbxphpspreadsheet.php',
		];
		foreach ( $required_plugins as $key => $plugin ) {
			if ( ! is_plugin_active( $plugin ) ) {
				add_action( 'admin_notices', function() use ( $plugin, $key ) {
					echo '<div class="notice notice-error"><p>ExcelRange を使用するには、' . esc_html( $key ) . ' プラグインをインストールして有効化する必要があります。</p></div>';
				} );
			}
		}
	}
	add_action( 'admin_init', 'exrg_check_required_plugins' );
}

/*-------------------------------------------*/
/* post_meta キャッシュ取得
/*-------------------------------------------*/
if ( ! function_exists( 'exrg_get_excel_data' ) ) {
	function exrg_get_excel_data( $post_id ) {
		static $cache = [];
		if ( ! isset( $cache[ $post_id ] ) ) {
			$cache[ $post_id ] = get_post_meta( $post_id, 'excelrange_import', true ) ?: [];
		}
		return $cache[ $post_id ];
	}
}

/*-------------------------------------------*/
/* ショートコード [excel range="A1"] [excel range="A1" post_id="123"]
/*-------------------------------------------*/
if ( ! function_exists( 'exrg_excel_shortcode' ) ) {
	function exrg_excel_shortcode( $atts ) {
		$atts    = shortcode_atts( [ 'range' => '', 'post_id' => 0 ], $atts, 'excel' );
		$post_id = $atts['post_id'] ? (int) $atts['post_id'] : get_the_ID();
		if ( ! $post_id || ! $atts['range'] ) { return ''; }
		$data = exrg_get_excel_data( $post_id );
		return $data[ strtoupper( $atts['range'] ) ] ?? '';
	}
	add_shortcode( 'excel', 'exrg_excel_shortcode' );
}

/*-------------------------------------------*/
/* #excel:A1 プレースホルダー置換（リンクURL用）
/*-------------------------------------------*/
if ( ! function_exists( 'exrg_replace_placeholders' ) ) {
	function exrg_replace_placeholders( $content ) {
		if ( strpos( $content, '#excel:' ) === false ) { return $content; }
		$post_id = get_the_ID();
		if ( ! $post_id ) { return $content; }
		$data = exrg_get_excel_data( $post_id );
		return preg_replace_callback(
			'/#excel:([A-Z]{1,2}[0-9]{1,3})/i',
			fn( $m ) => $data[ strtoupper( $m[1] ) ] ?? '',
			$content
		);
	}
	add_filter( 'the_content', 'exrg_replace_placeholders', 8 );
}
