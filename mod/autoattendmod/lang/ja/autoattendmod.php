<?php
$string['autoattendmod:addinstance'] = '自動出欠モジュールを追加する';
$string['autoattendmod:view'] = 'モジュールの閲覧';
$string['autoattendmod:viewreports'] = 'レポートの閲覧';
$string['autoattendmod:receivemail'] = 'メール通知を受信する';

$string['use_item'] = '{$a} を使用する';
$string['username_manage'] = 'ユーザ名管理';
$string['username_manage_help'] = '表示される名前のパターンを選択できます';

$string['pluginname'] = '自動出欠モジュール';
$string['pluginadministration'] = '自動出欠モジュール管理';
$string['name'] = '名称';
$string['description'] = '説明';

$string['modulename'] = '自動出欠';
$string['modulename_help'] = '1つのコースにつき1つの自動出欠管理モジュールを追加することができます．このモジュールを削除しても，出欠のデータには影響しません! <br />
このモジュールは，自動出欠ブロックとの組み合わせでのみ使用することができます．このモジュールを追加する前に，必ず自動出欠ブロックを追加してください．<br />
このモジュールは，半自動モードで出欠をとる場合，またはコースの評定に出欠点を入れる場合に必要となります．';
$string['modulenameplural'] = '自動出欠管理';
$string['modulenamesimple'] = '出欠管理';

$string['misconfigured'] = 'コースが正しく設定されていません';
$string['modidincorrect'] = '不正なモジュールIDです';
$string['modincorrect'] = '不正なコースモジュールです<br />自動出欠ブロックがインストールされていない可能性があります';

$string['session_num'] = '授業数';

$string['autoattendmod_options'] = '自動出欠モジュールのオプション';

$string['disp_info'] = 'ブロックに直近または実施中の授業情報を表示する';
$string['disp_info_title'] = '直近・実施中授業情報の表示';
$string['disp_info_help'] = '直近または実施中の授業の出席キー（半自動の場合）と出席者数をブロックに表示する';

$string['pre_disptime'] ='授業情報表示時の猶予時間（分）';
$string['pre_disptime_title'] ='授業情報表示時の猶予時間（分）';
$string['pre_disptime_help'] ='ブロックに直近または実施中の授業の情報を表示する場合，授業開始の何分前から表示するかを指定する．';

$string['permit_homeroom_title'] = 'ホームルームモジュール';
$string['permit_homeroom'] = 'ホームルームモジュールからのアクセスを許可する';
$string['permit_homeroom_help'] = 'チェックを外すと，ホームルームモジュールからアクセスされません';
$string['disp_localhostname_title'] = 'ローカルホスト名表示';
$string['disp_localhostname'] = 'ローカルIPアドレスの代わりにホスト名を表示する';
$string['disp_localhostname_help'] = 'IPアドレスがローカルな場合，IPアドレスではなくてローカルホスト名を表示する';
$string['feedback_title'] = 'アンケートリンク';
$string['feedback_disp'] = 'アンケートリンクの表示';
$string['feedback_disp_help'] = 'チェックを外すと，アンケートへのリンクが表示されません';
$string['email_enable_title'] = '教師への結果通知';
$string['email_enable'] = '教師に出欠結果をメール通知する機能を有効にする';
$string['email_enable_help'] = '有効にした場合，授業が終わった時に出欠のレポートが教師にメールで送られます．ただし，autoattendmod:receivemail capability に "教師" を追加しないといけません．';
$string['email_allrep_title'] = '教師への全結果通知';
$string['email_allrep'] = '全期間中のレポートデータを教師にメールで送る';
$string['email_allrep_help'] = 'もしメール通知が有効で，かつこのチェックボックスがチェックされた場合，全期間のレポートが教師に送られます．チェックしない場合は１回分の出欠レポートのみ送られます';
$string['email_key_title'] = '教師へのキー通知';
$string['email_key'] = '教師に出席キーをメール通知する機能を有効にする';
$string['email_key_help'] = '有効にした場合，授業が始まった時に出席キーが教師にメールで送られます（出席キーが有効な場合）．ただし，autoattendmod:receivemail capability に "教師" を追加しないといけません．';
$string['email_user_title'] = '学生への通知';
$string['email_user'] = '学生に出欠確認をメール通知する機能を有効にする';
$string['email_user_help'] = '有効にした場合，学生の出席データが変化したときに通知が学生にメールで送られます．ただし，autoattendmod:receivemail capability に "学生" を追加しないといけません．';
$string['backup_block_title'] = '出欠データのバックアップ';
$string['backup_block'] = 'コースをバックアップする時に，自動出欠ブロックが管理するデータもバックアップする';
$string['backup_block_help'] = '現在のバージョンでは自動出欠ブロックが出欠データを管理しています．自動出欠モジュールを削除しても，そのコースの出欠データはデータベース上に残っています';
$string['excelver_title'] = 'Excel2007 の使用';
$string['excelver_disp'] = 'レポートのダウンロードに古い Excel (Excel2007) のフォーマットを使用する';
$string['excelver_disp_help'] = 'Excel のレポートのダウンロードに失敗する場合は，チェックを入れてみてください．';
$string['summertime_title'] = '夏時間の使用';
$string['summertime_disp']  = '授業の設定時に夏時間が指定できる．';
$string['summertime_disp_help'] = '各授業の設定時に夏時間が指定できるようになる．';

$string['firstinstallblock']  = '先に 自動出欠ブロックをインストールしてください．';
$string['firstinstanceblock'] = '先に 自動出欠ブロックのインスタンスを作成してください．';
$string['onlyonemodule'] = '自動出欠モジュールのインスタンスは１つのコースに１個だけしか作成できません．';
