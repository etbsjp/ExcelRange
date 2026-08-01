<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Exrg_Import_Excel {

	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'add_menu_page' ] );
		add_action( 'wp_dashboard_setup', [ __CLASS__, 'add_dashboard_widget' ] );
		add_action( 'wp_ajax_exrg_search_posts',      [ __CLASS__, 'ajax_search_posts' ] );
		add_action( 'wp_ajax_exrg_import_excel',      [ __CLASS__, 'ajax_import_excel' ] );
		add_action( 'wp_ajax_exrg_check_excel_data',  [ __CLASS__, 'ajax_check_excel_data' ] );
		add_action( 'wp_ajax_exrg_delete_excel_data', [ __CLASS__, 'ajax_delete_excel_data' ] );
	}

	public static function add_menu_page() {
		add_menu_page(
			__( 'Excelインポート', 'excelrange' ),
			__( 'Excelインポート', 'excelrange' ),
			'edit_pages',
			'exrg-import-excel',
			[ __CLASS__, 'render_import_page' ],
			'dashicons-upload',
			16
		);
	}

	public static function add_dashboard_widget() {
		if ( ! current_user_can( 'edit_pages' ) ) { return; }
		wp_add_dashboard_widget(
			'exrg_dashboard_widget',
			'ExcelRange',
			[ __CLASS__, 'render_dashboard_widget' ]
		);
	}

	public static function render_dashboard_widget() {
		$import_url = admin_url( 'admin.php?page=exrg-import-excel' );
		?>
		<p>ExcelファイルをWordPress投稿に取り込み、ショートコードで値を出力できます。</p>

		<strong>使用例</strong>
		<ul style="margin:6px 0 12px 1.2em;list-style:disc;">
			<li>セルの値を表示：<code>[excel range="B2"]</code></li>
			<li>別の投稿のデータを参照：<code>[excel range="B2" post_id="123"]</code></li>
			<li>リンクのURL欄にセルのURLを使用：<code>#excel:A1</code></li>
		</ul>

		<strong>注意事項</strong>
		<ul style="margin:6px 0 12px 1.2em;list-style:disc;">
			<li>インポートできる範囲はアクティブシートのA1〜AZ300です。</li>
			<li>セル内のHTMLタグ・JSコードもそのまま出力されます。安全確認は自己責任で行ってください。</li>
		</ul>

		<strong>サポート</strong>
		<p style="margin:6px 0 12px;">有償サポートやカスタマイズは<a href="https://etbs.jp/product-category/wordpress-tools/?utm_source=excelrange&utm_medium=plugin" target="_blank" rel="noopener">こちらのページ</a>からお問い合わせください。開発の継続は<a href="https://etbs.jp/product/donate/?utm_source=excelrange&utm_medium=plugin" target="_blank" rel="noopener">ご支援</a>で応援いただけます。</p>

		<a href="<?php echo esc_url( $import_url ); ?>" class="button button-primary">Excelインポート画面を開く</a>
		<?php
	}

	public static function render_import_page() {
		$post_types   = get_post_types( [ 'public' => true ], 'objects' );
		$delete_nonce = wp_create_nonce( 'exrg_delete_nonce' );
		?>
		<div class="wrap">
			<h1>Excelインポート</h1>

			<div style="background:#f6f7f7;border:1px solid #c3c4c7;border-radius:4px;padding:12px 16px;margin:12px 0 20px;">
				<strong>使用例</strong>
				<ul style="margin:8px 0 0 1.2em;list-style:disc;">
					<li>段落・見出し内でセルの値を表示：<code>[excel range="B2"]</code></li>
					<li>別の投稿のデータを参照：<code>[excel range="B2" post_id="123"]</code></li>
					<li>リンクのURL欄にセルのURLを使用：<code>#excel:A1</code></li>
				</ul>
			</div>

			<table class="form-table">
				<tr>
					<th scope="row">投稿タイプ</th>
					<td>
						<select id="exrg-post-type">
							<option value="">選択してください</option>
							<?php foreach ( $post_types as $pt ) : ?>
							<option value="<?php echo esc_attr( $pt->name ); ?>"><?php echo esc_html( $pt->label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">タイトルで検索</th>
					<td style="position:relative;">
						<input type="text" id="exrg-post-search" placeholder="2文字以上入力..." style="width:300px;" autocomplete="off">
						<div id="exrg-search-results" style="display:none;position:absolute;top:100%;left:0;width:300px;background:#fff;border:1px solid #c3c4c7;box-shadow:0 2px 6px rgba(0,0,0,.1);z-index:100;max-height:220px;overflow-y:auto;"></div>
					</td>
				</tr>
				<tr>
					<th scope="row">または投稿ID</th>
					<td>
						<input type="number" id="exrg-post-id" placeholder="投稿ID" style="width:100px;">
						<span id="exrg-post-title" style="margin-left:10px;color:#50575e;"></span>
					</td>
				</tr>
				<tr>
					<th scope="row">Excelファイル</th>
					<td>
						<input type="file" id="exrg-excel-file" accept=".xlsx,.xls,.xlsm">
						<p class="description" style="color:#d63638;">※ インポートを実行すると既存データは上書きされます。</p>
					</td>
				</tr>
			</table>

			<p>
				<button id="exrg-import-btn" class="button button-primary">インポート実行</button>
				<button id="exrg-check-btn" class="button" style="margin-left:8px;">現在のデータを確認</button>
			</p>

			<div id="exrg-import-result" style="margin-top:20px;">
				<button id="exrg-delete-btn" class="button" style="display:none;margin-bottom:12px;color:#d63638;border-color:#d63638;">データを削除</button>
			</div>

			<div>
				<p style="margin-top:40px;font-size:14px;color:#50575e;">
					<strong>注意事項</strong><br>
					・インポートできるのはアクティブなシートのA1〜AZ300の範囲です。<br>
					・セル内の値をそのまま保存するため、HTMLタグやJavaScriptコードも出力されます。安全確認は自己責任で行ってください。<br>
				</p>
				<p style="font-size:14px;color:#50575e;">
					<strong>サポート</strong><br>
					有償サポートやカスタマイズをご希望の方は、<a href="https://etbs.jp/product-category/wordpress-tools/" target="_blank" rel="noopener">こちらのページ</a>からお問い合わせください。<br>
				</p>
			</div>
		</div>

		<!-- 削除確認モーダル -->
		<dialog id="exrg-delete-dialog" style="border:1px solid #c3c4c7;border-radius:4px;padding:24px 28px;min-width:300px;box-shadow:0 4px 16px rgba(0,0,0,.15);">
			<p style="margin:0 0 16px;font-size:14px;">このデータを削除しますか？<br><strong>この操作は元に戻せません。</strong></p>
			<div style="display:flex;gap:8px;justify-content:flex-end;">
				<button id="exrg-delete-cancel" class="button">キャンセル</button>
				<button id="exrg-delete-confirm" class="button" style="color:#d63638;border-color:#d63638;">削除する</button>
			</div>
		</dialog>

		<script>
		jQuery( function( $ ) {
			var nonce        = '<?php echo wp_create_nonce( 'exrg_nonce' ); ?>';
			var deleteNonce  = '<?php echo esc_js( $delete_nonce ); ?>';
			var selectedPostId = 0;
			var searchTimer;
			var dialog       = document.getElementById( 'exrg-delete-dialog' );

			/* ---------- タイトル検索 ---------- */
			$( '#exrg-post-search' ).on( 'input', function() {
				clearTimeout( searchTimer );
				var term     = $( this ).val();
				var postType = $( '#exrg-post-type' ).val();
				if ( term.length < 2 || ! postType ) {
					$( '#exrg-search-results' ).hide();
					return;
				}
				searchTimer = setTimeout( function() {
					$.post( ajaxurl, {
						action:    'exrg_search_posts',
						nonce:     nonce,
						post_type: postType,
						search:    term
					}, function( res ) {
						if ( ! res.success ) { return; }
						var html = '';
						res.data.forEach( function( post ) {
							html += '<div class="exrg-result-item" data-id="' + post.id + '" style="padding:6px 10px;cursor:pointer;">'
								+ '(' + post.id + ') ' + $( '<div>' ).text( post.title ).html()
								+ '</div>';
						} );
						if ( ! html ) {
							html = '<div style="padding:6px 10px;color:#999;">見つかりません</div>';
						}
						$( '#exrg-search-results' ).html( html ).show();
					} );
				}, 300 );
			} );

			$( document ).on( 'mouseenter', '.exrg-result-item', function() {
				$( this ).css( 'background', '#f0f0f1' );
			} ).on( 'mouseleave', '.exrg-result-item', function() {
				$( this ).css( 'background', '' );
			} );

			$( document ).on( 'click', '.exrg-result-item', function() {
				selectedPostId = $( this ).data( 'id' );
				$( '#exrg-post-id' ).val( selectedPostId );
				$( '#exrg-post-search' ).val( $( this ).text().trim() );
				$( '#exrg-post-title' ).text( '' );
				$( '#exrg-search-results' ).hide();
			} );

			$( document ).on( 'click', function( e ) {
				if ( ! $( e.target ).closest( '#exrg-post-search, #exrg-search-results' ).length ) {
					$( '#exrg-search-results' ).hide();
				}
			} );

			/* ---------- ID 直接入力 ---------- */
			$( '#exrg-post-id' ).on( 'change', function() {
				selectedPostId = parseInt( $( this ).val() ) || 0;
				$( '#exrg-post-search' ).val( '' );
				$( '#exrg-search-results' ).hide();
				$( '#exrg-delete-btn' ).hide();
				if ( ! selectedPostId ) {
					$( '#exrg-post-title' ).text( '' );
					return;
				}
				$.post( ajaxurl, {
					action:  'exrg_search_posts',
					nonce:   nonce,
					post_id: selectedPostId
				}, function( res ) {
					if ( res.success && res.data.length ) {
						$( '#exrg-post-title' ).text( res.data[0].title );
					} else {
						$( '#exrg-post-title' ).text( '（投稿が見つかりません）' );
					}
				} );
			} );

			/* ---------- インポート実行 ---------- */
			$( '#exrg-import-btn' ).on( 'click', function() {
				var postId = parseInt( $( '#exrg-post-id' ).val() ) || selectedPostId;
				var file   = $( '#exrg-excel-file' )[0].files[0];

				if ( ! postId ) {
					alert( '投稿を選択またはIDを入力してください。' );
					return;
				}
				if ( ! file ) {
					alert( 'Excelファイルを選択してください。' );
					return;
				}

				var formData = new FormData();
				formData.append( 'action',     'exrg_import_excel' );
				formData.append( 'nonce',      nonce );
				formData.append( 'post_id',    postId );
				formData.append( 'excel_file', file );

				$( '#exrg-import-btn' ).prop( 'disabled', true ).text( 'インポート中...' );
				$( '#exrg-delete-btn' ).hide();
				$( '#exrg-import-result' ).find( '.exrg-result-content' ).remove();

				$.ajax( {
					url:         ajaxurl,
					type:        'POST',
					data:        formData,
					processData: false,
					contentType: false,
					success: function( res ) {
						var html;
						if ( res.success ) {
							html = '<div class="notice notice-success inline"><p>' + res.data.message + '</p></div>';
							html += '<table class="widefat striped" style="margin-top:16px;"><thead><tr><th>セル</th><th>値</th></tr></thead><tbody>';
							res.data.cells.forEach( function( cell ) {
								html += '<tr><td>' + cell.key + '</td><td>' + $( '<div>' ).text( cell.value ).html() + '</td></tr>';
							} );
							html += '</tbody></table>';
							$( '#exrg-delete-btn' ).show();
						} else {
							html = '<div class="notice notice-error inline"><p>' + res.data + '</p></div>';
						}
						$( '#exrg-import-result' ).append( $( '<div class="exrg-result-content">' ).html( html ) );
					},
					error: function() {
						$( '#exrg-import-result' ).append(
							'<div class="exrg-result-content"><div class="notice notice-error inline"><p>通信エラーが発生しました。</p></div></div>'
						);
					},
					complete: function() {
						$( '#exrg-import-btn' ).prop( 'disabled', false ).text( 'インポート実行' );
					}
				} );
			} );

			/* ---------- データ確認 ---------- */
			$( '#exrg-check-btn' ).on( 'click', function() {
				var postId = parseInt( $( '#exrg-post-id' ).val() ) || selectedPostId;
				if ( ! postId ) {
					alert( '投稿を選択またはIDを入力してください。' );
					return;
				}
				$( '#exrg-check-btn' ).prop( 'disabled', true ).text( '確認中...' );
				$( '#exrg-delete-btn' ).hide();
				$( '#exrg-import-result' ).find( '.exrg-result-content' ).remove();

				$.post( ajaxurl, {
					action:  'exrg_check_excel_data',
					nonce:   nonce,
					post_id: postId
				}, function( res ) {
					var html;
					if ( res.success ) {
						html = '<div class="notice notice-info inline"><p>' + res.data.message + '</p></div>';
						if ( res.data.cells.length ) {
							html += '<table class="widefat striped" style="margin-top:16px;"><thead><tr><th>セル</th><th>値</th></tr></thead><tbody>';
							res.data.cells.forEach( function( cell ) {
								html += '<tr><td>' + cell.key + '</td><td>' + $( '<div>' ).text( cell.value ).html() + '</td></tr>';
							} );
							html += '</tbody></table>';
							$( '#exrg-delete-btn' ).show();
						}
					} else {
						html = '<div class="notice notice-error inline"><p>' + res.data + '</p></div>';
					}
					$( '#exrg-import-result' ).append( $( '<div class="exrg-result-content">' ).html( html ) );
				} ).always( function() {
					$( '#exrg-check-btn' ).prop( 'disabled', false ).text( '現在のデータを確認' );
				} );
			} );

			/* ---------- 削除ボタン → モーダル表示 ---------- */
			$( '#exrg-delete-btn' ).on( 'click', function() {
				dialog.showModal();
			} );

			$( '#exrg-delete-cancel' ).on( 'click', function() {
				dialog.close();
			} );

			dialog.addEventListener( 'click', function( e ) {
				if ( e.target === dialog ) { dialog.close(); }
			} );

			/* ---------- 削除確定 ---------- */
			$( '#exrg-delete-confirm' ).on( 'click', function() {
				var postId = parseInt( $( '#exrg-post-id' ).val() ) || selectedPostId;
				if ( ! postId ) { dialog.close(); return; }

				$( '#exrg-delete-confirm' ).prop( 'disabled', true ).text( '削除中...' );

				$.post( ajaxurl, {
					action:  'exrg_delete_excel_data',
					nonce:   deleteNonce,
					post_id: postId
				}, function( res ) {
					dialog.close();
					$( '#exrg-delete-btn' ).hide();
					$( '#exrg-import-result' ).find( '.exrg-result-content' ).remove();
					var html;
					if ( res.success ) {
						html = '<div class="notice notice-success inline"><p>' + res.data + '</p></div>';
					} else {
						html = '<div class="notice notice-error inline"><p>' + res.data + '</p></div>';
					}
					$( '#exrg-import-result' ).append( $( '<div class="exrg-result-content">' ).html( html ) );
				} ).always( function() {
					$( '#exrg-delete-confirm' ).prop( 'disabled', false ).text( '削除する' );
				} );
			} );
		} );
		</script>
		<?php
	}

	public static function ajax_search_posts() {
		check_ajax_referer( 'exrg_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_send_json_error( '権限がありません' );
		}

		$args = [
			'post_status'    => 'any',
			'posts_per_page' => 10,
			'no_found_rows'  => true,
		];

		if ( ! empty( $_POST['post_id'] ) ) {
			$args['p']         = (int) $_POST['post_id'];
			$args['post_type'] = 'any';
		} else {
			$args['post_type'] = sanitize_key( $_POST['post_type'] ?? 'post' );
			$args['s']         = sanitize_text_field( $_POST['search'] ?? '' );
		}

		$query   = new WP_Query( $args );
		$results = [];
		foreach ( $query->posts as $post ) {
			$results[] = [ 'id' => $post->ID, 'title' => $post->post_title ];
		}
		wp_send_json_success( $results );
	}

	public static function ajax_import_excel() {
		check_ajax_referer( 'exrg_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_send_json_error( '権限がありません' );
		}

		$post_id = (int) ( $_POST['post_id'] ?? 0 );
		if ( ! $post_id || ! get_post( $post_id ) ) {
			wp_send_json_error( '投稿が見つかりません' );
		}

		if ( empty( $_FILES['excel_file'] ) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK ) {
			wp_send_json_error( 'ファイルのアップロードに失敗しました' );
		}

		$file     = $_FILES['excel_file'];
		$ext      = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		$allowed  = [ 'xlsx', 'xls', 'xlsm' ];
		if ( ! in_array( $ext, $allowed, true ) ) {
			wp_send_json_error( 'Excelファイル（.xlsx / .xls / .xlsm）のみアップロードできます' );
		}

		$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], [
			'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'xls'  => 'application/vnd.ms-excel',
			'xlsm' => 'application/vnd.ms-excel.sheet.macroEnabled.12',
		] );
		if ( empty( $check['ext'] ) ) {
			wp_send_json_error( 'ファイルの内容がExcel形式ではありません' );
		}

		if ( ! defined( 'CBXPHPSPREADSHEET_ROOT_PATH' ) ||
			! file_exists( CBXPHPSPREADSHEET_ROOT_PATH . 'lib/vendor/autoload.php' ) ) {
			wp_send_json_error( 'CBX PhpSpreadSheet Library が有効化されていません' );
		}
		require_once CBXPHPSPREADSHEET_ROOT_PATH . 'lib/vendor/autoload.php';

		$tmp_path = $file['tmp_name'];
		$data     = [];

		try {
			$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load( $tmp_path );
			$sheet       = $spreadsheet->getActiveSheet();
			$columns     = exrg_generate_columns();

			foreach ( $columns as $col ) {
				for ( $row = 1; $row <= 300; $row++ ) {
					$value = $sheet->getCell( $col . $row )->getValue();
					if ( $value !== null && $value !== '' ) {
						$data[ $col . $row ] = (string) $value;
					}
				}
			}

			update_post_meta( $post_id, 'excelrange_import', $data );

		} catch ( \Exception $e ) {
			wp_delete_file( $tmp_path );
			wp_send_json_error( 'ファイルの読み込みに失敗しました: ' . $e->getMessage() );
		}

		wp_delete_file( $tmp_path );

		$cells = [];
		foreach ( $data as $key => $value ) {
			$cells[] = [ 'key' => $key, 'value' => $value ];
		}

		wp_send_json_success( [
			'message' => count( $data ) . '件のセルを取り込みました（投稿ID: ' . $post_id . '）',
			'cells'   => $cells,
		] );
	}

	public static function ajax_check_excel_data() {
		check_ajax_referer( 'exrg_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_send_json_error( '権限がありません' );
		}

		$post_id = (int) ( $_POST['post_id'] ?? 0 );
		if ( ! $post_id || ! get_post( $post_id ) ) {
			wp_send_json_error( '投稿が見つかりません' );
		}

		$data = get_post_meta( $post_id, 'excelrange_import', true ) ?: [];

		if ( empty( $data ) ) {
			wp_send_json_success( [
				'message' => '投稿ID: ' . $post_id . ' にインポートデータはありません',
				'cells'   => [],
			] );
		}

		$cells = [];
		foreach ( $data as $key => $value ) {
			$cells[] = [ 'key' => $key, 'value' => $value ];
		}

		wp_send_json_success( [
			'message' => count( $data ) . '件のセルが保存されています（投稿ID: ' . $post_id . '）',
			'cells'   => $cells,
		] );
	}

	public static function ajax_delete_excel_data() {
		check_ajax_referer( 'exrg_delete_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_send_json_error( '権限がありません' );
		}

		$post_id = (int) ( $_POST['post_id'] ?? 0 );
		if ( ! $post_id || ! get_post( $post_id ) ) {
			wp_send_json_error( '投稿が見つかりません' );
		}

		$existing = get_post_meta( $post_id, 'excelrange_import', true );
		if ( empty( $existing ) ) {
			wp_send_json_error( '削除するデータがありません' );
		}

		delete_post_meta( $post_id, 'excelrange_import' );

		$remaining = get_post_meta( $post_id, 'excelrange_import', true );
		if ( ! empty( $remaining ) ) {
			wp_send_json_error( 'データの削除に失敗しました' );
		}

		wp_send_json_success( '投稿ID: ' . $post_id . ' のインポートデータを削除しました' );
	}
}

if ( ! function_exists( 'exrg_generate_columns' ) ) {
	function exrg_generate_columns() {
		$cols = [];
		for ( $i = 0; $i < 26; $i++ ) {
			$cols[] = chr( 65 + $i ); // A-Z
		}
		for ( $i = 0; $i < 26; $i++ ) {
			$cols[] = 'A' . chr( 65 + $i ); // AA-AZ
		}
		return $cols;
	}
}

Exrg_Import_Excel::init();
