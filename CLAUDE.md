# ExcelRange

etbs が配布する WordPress プラグイン。共通ルールの正本は `~/.claude/etbs-plugin-rules.md`。

## レビュー工程に大（シニアエンジニア）を追加する

このリポジトリでは、安藤（`vk-code-reviewer`）のレビューのあと、**PR を作成する前に**
大（`etbs-senior-wp`）の監査を必ず通すこと。大は etbs の申し送りと過去に踏んだ罠に照らして
「リリースできる形になっているか」を見る担当で、安藤の一般的なコード品質レビューとは層が違う。

- `Agent` ツールで `subagent_type: etbs-senior-wp`、`name: etbs-senior-wp`、
  **`run_in_background: false`** で起動する
- **`isolation: "worktree"` は使えるなら付ける**（付けないと起動応答は「成功」と返るのに
  一度も作業せず待機状態に入ることがある）。ただし ★★ **作業ディレクトリが git リポジトリでないと使えない**。
  vk-orchestrator のペインは対象リポジトリを特定できないと `~/vk-orchestrator-tasks` で開くため、
  **この制約に高い確率で当たる**（2026-08-19 / #72・#73 で発生）。
  その場合は **isolation なしで起動してよい**。実際 #73 は isolation なしで正常に完走している。
  **見分け方は起動応答の形**——`output_file` 付きの正常形なら動いている。無応答のまま進捗が出なければ待機モードなので、
  そのとき初めて「対象リポジトリを cwd にした新セッションでやり直す」に切り替える
- prompt には対象リポジトリ・ブランチ・差分（または PR 番号）を渡す
- 大には **出力の末尾に `監査結果: PASS` または `監査結果: FAIL` を必ず書くよう指示する**
  （★ 大の定義ファイルには出力形式の指定が無いため、指示しないと合否を機械判定できない）
- `監査結果: PASS` を受け取るまで PR を作成しない。`FAIL` なら和田へ差し戻して再監査する

★ 大は vk-agents のメンバー表に登録されていないため、指示が無いと**永久に呼ばれない**。

## 検証環境

Local の `excelrange`（`excelrange.etbs.lc`）。このプラグインは `dirname( __FILE__ )` を
1階層のみ（`excelrange.php` から `inc/func.php` への require、および `inc/func.php` から
`tools/import-excel.php` への require）に使っており `dirname( __FILE__, N )` の複数階層遡りは無いため、
**シンボリックリンク設置でよい**。

CLI 検証では Local の php.ini を `-c` で渡すこと。渡さないと「データベース接続確立エラー」になり、
**サイトが停止しているように見える**（実際は動いている）。`<runId>` は
`ls -d ~/Library/Application\ Support/Local/run/*/mysql/mysqld.sock` で特定する。

動作には CBX PhpSpreadSheet Library プラグイン（有効化必須）が必要。未有効化だと管理画面に
エラー通知が出るだけでフェイルセーフに倒れる（`inc/func.php` の `exrg_check_required_plugins()`）。

## アンインストール

★ `uninstall.php` の方針は**案A**（task-queue #108）。判定は3分類。

| 利用者が作ったコンテンツ（投稿・投稿メタ） | 利用者が設定した値（オプション） | 一時状態・自分が仕掛けた cron |
|---|---|---|
| **消さない** | **消さない** | **消す** |

理由は害の非対称性。消さないことの害は「DB に少量のレコードが残る」だけだが、消すことの害は
復旧不可能。迷ったら残す側に倒す。

このプラグインでの当てはめ:

- **残す** … 投稿メタ `excelrange_import`（`inc/tools/import-excel.php:443`。利用者が取り込んだ表データ）
- **消す** … 該当なし（独自テーブルも cron も持たない）

★ 配布8本すべてがこの3分類で説明できる状態にしてある。テーブルと cron を持つのは editlock だけ、
一時状態のオプションを持つのは pageguard だけで、そこだけが「消す」に該当する。
**他のプラグインで「何も消していない」のは判断の結果であって書き忘れではない。**
横並びで「消す」側へ揃えにこないこと。

## 版数

版数の置き場は `excelrange.php` の `Version:` ヘッダのみ。かつて存在した `$exrg_version` は
どこからも参照されない死に変数だったため削除済み（task-queue#88）。版数を上げる際は
このヘッダ1箇所だけを更新すればよい。

```sh
grep -nE "^ \* Version:" excelrange.php
```

## 配布物

`dist` ブランチへのマージ＝配信。PUC が配る zip には**追跡しているファイルが全部入る**ため、
`.gitignore`（追跡させない）と `.gitattributes` の `export-ignore`（zip から落とす）は役割が別。
両方を維持すること。

## 宣言（Requires）の方針

★★ `Requires at least`（WP）は**実測した下限があるときだけ書く。無ければ書かない。**
`Requires PHP` は実測下限ではなく **「etbs が動作を保証する最低 PHP」の宣言として 7.4 を書く**。
**この2つは過剰宣言したときの害の向きが逆なので、同じ基準で扱わない。他のプラグインと横並びで揃えない。**

| | 過剰に宣言すると | 過小に宣言すると |
|---|---|---|
| `Requires at least`（WP） | **有効化・更新が拒否される**＝修正が届かない個体を作る | 古い WP に入るが、使う API が無ければその場で分かる |
| `Requires PHP` | 入れられる環境が狭まるだけ | 構文エラーで白画面。しかも FTP 手動設置は止められない |

- このリポジトリは `Requires at least: 6.7` を**削除**した（task-queue#88）。
  理由：自前コードの最も新しい WP API は WP 4.2 相当、同梱している PUC（Plugin Update Checker）を
  含めても WP 4.8 相当で、**合成した実下限は 4.8**。6.7 は初版からの定型文で、
  特定の API に紐づいたものではなかった（実測は 2026-08-20 の task-queue#88 コメント参照）
- `Requires PHP: 8.3` は初版からの定型文で実下限ではなかったため **7.4 に下げた**
  （task-queue#88）。EditLock も初版 1.0.0 で同じ 8.3 を宣言し、1.0.1 で 7.4 に下げている。
  ★ これは実測下限そのものではない。「7.4 で動く」ことの証明であって *7.4 未満で動かない*
  ことの証明ではなく、7.3 以下は未検証。そのうえで保守方針として 7.4 を宣言している。
  次に見た人が「下限じゃないなら消せる」と判断しないよう、この理由を残しておく
- 依存プラグイン CBX PhpSpreadSheet Library の PHP バージョン要求（`Requires PHP: 8.1.99`）は
  **ヘッダでは締めない**。`inc/func.php:12` の `exrg_check_required_plugins()` が
  実行時（`admin_init`）に有効化チェックをしており、未有効化なら管理画面に通知が出る
  フェイルセーフ構成のため
- 一方、プラグインとしての有効化必須自体は `Requires Plugins:  cbxphpspreadsheet` で宣言している
  （task-queue#88）。`inc/func.php:18` の `is_plugin_active()` チェックは管理画面通知を出すだけで
  処理は止めないが、`inc/tools/import-excel.php:420-423` には `CBXPHPSPREADSHEET_ROOT_PATH` 未定義時に
  `wp_send_json_error()`（内部で `wp_die()`）により処理を打ち切るガードがあり、この経路は
  PHP Fatal にはならない（Excel取り込み機能が使えなくなるだけの、いずれもフェイルセーフな構成）。
  つまりこの宣言は「Fatal を防ぐため」ではなく、**主要機能（Excelインポート）が丸ごと欠落した状態の
  まま有効化され続けることを防ぐため**のもの。cbxphpspreadsheet は wordpress.org 未掲載
  （GitHub 配布のみ）だが、WP コアの `Requires Plugins` 解決は wordpress.org ではなく
  **インストール済みプラグインのフォルダ名（スラッグ）を突き合わせる**方式
  （`WP_Plugin_Dependencies::convert_to_slug()` / `get_plugin_dirnames()`）のため、
  wordpress.org 非掲載でも有効化ブロックとしては機能する（Local `excelrange.etbs.lc` で
  WP 7.0 のコアを CLI から `wp-load.php` 経由で読み込み、cbxphpspreadsheet 停止中に
  `activate_plugin('excelrange/excelrange.php')` が `plugin_missing_dependencies` の
  `WP_Error` で拒否されることを実測済み）。wordpress.org 非掲載の影響を受けるのは
  プラグイン一覧・インストール画面での名称表示とワンクリックインストール導線のみ
  （`get_dependency_api_data()` が `plugins_api()` を叩く箇所で、失敗しても表示が
  スラッグのままになるだけでブロック機構自体には影響しない）。
  ★ `Requires Plugins` は WP 6.5 で追加された機構（`WP_Plugin_Dependencies` の
  `@since 6.5.0`）。6.5 未満ではヘッダごと無視されるため、その版数帯では
  `exrg_check_required_plugins()` の実行時チェックのみが防衛線になる

★ `README.md` の「必要環境」にもヘッダと同じ情報を書いている（利用者向けの説明のため）。
**ヘッダの `Requires at least` / `Requires PHP` を変更したときは、`README.md` の該当箇所も
同時に見直すこと。** `README.md` は `export-ignore` されておらず配布 zip に含まれるため、
ヘッダだけ直しても README が古いままだと利用者には要件が伝わり続ける。揃えないまま放置すると、
次に見た人がどちらが正しいか分からず、README に合わせてヘッダへ過剰宣言を書き戻す方向に
動きかねない。

★★ **「据え置き」と「新規に足す」は別問題**（2026-08-25 / task-queue #111 で再確認）。
既に宣言している版を据え置いても新たに締め出す個体は生まれないが、**無宣言のプラグインに
`Requires PHP` を新しく足すと、いま更新が届いている個体を以後届かなくする**。
`woo-checkout-colorbox` と `widget-shortcode-tools` が無宣言なのは、この理由による意図的な判断。
**8本で揃えにこないこと。**
