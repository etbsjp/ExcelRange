=== ETBS ExcelRange ===
Contributors:      etbsjp
Tags:              excel, spreadsheet, shortcode, import
Stable tag:        1.0.5
Requires PHP:      7.4
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Excel ファイルのシートを投稿に取り込み、ショートコードで任意のセルの値を表示できるプラグインです。

== Description ==

Excel ファイル（アクティブなシートの A1〜AZ300 の範囲）を投稿に取り込んで保存し、ショートコードで任意のセルの値を本文に表示できます。

= できること =

* **Excelインポート** … 管理メニューから Excel ファイルを取り込み、投稿に紐づけて保存します。
* **ショートコードで表示** … 取り込んだ範囲の値を、セルを指定して本文に出力できます。

= 動作環境 =

* PHP 7.4 以上
* **cbxphpspreadsheet プラグインが必要です**（Excel ファイルの読み取りに使用します）。未導入の場合は有効化できません。

WordPress のバージョン下限は設けていません。

== Changelog ==

= 1.0.5 =
* [ その他 ] プラグインの表示名を「ETBS ExcelRange」に変更しました。フォルダ名・設定・更新の受け取りには影響しません。
* [ その他 ] 変更履歴の記載を開始しました。

= 1.0.4 =
* [ 仕様変更 ] 動作要件の宣言を、実際に必要な下限に合わせて整理しました。
* [ 仕様変更 ] 必要なプラグインとして cbxphpspreadsheet を宣言しました。未導入の環境では有効化できなくなります。

= それ以前 =
* 1.0.3 以前の変更は記録していません。
