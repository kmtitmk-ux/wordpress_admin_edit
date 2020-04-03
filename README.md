# コミットについて
gitにはテーマとmu-pluginsだけをコミットして、他は除外します。  
状況に応じてgitignoreを修正してください。

# 開発環境
タスクランナーにはlaravel-mixを使っていますが、自由に変更してください。

## versサーバーでの利用
versサーバーにはwp-cliをインストールしていますので、コマンドを使うと簡単です。

## wp_cliのコマンド一部
コマンドの一部を書いておきますが、詳しくは公式を参照してください。

### インストール
```
$ wp core download --locale=ja --path={インストールパス}
```

### アップデート
```
$ wp core update
$ wp plugin update --all
```

### テーマ変更
```
$ wp theme activate original
```

## MAMPでwp-cliを利用する場合
wp-config.phpのDB_HOSTを以下に設定してください。  
```
define( 'DB_HOST', 'localhost:/Applications/MAMP/tmp/mysql/mysql.sock' );
```

# wordpressのセキュリティ設定
最低限のセキュリティ設定となります。  
情報は常に変わっていくので最新情報に合わせてください。

## wp-config.phpのパーミッションとオーナーの変更
```
# chown apache:apache wp-config.php 
# chmod 400 wp-config.php 
```
共用のレンタルサーバーで設定ができない場合は、そこの仕様にのっとってください。

## wp-config.phpの設定
wp-config.phpとwp-config-sample.phpはドキュメントルートの外に移動させます。  
共用のレンタルサーバーで設定ができない場合は、そこの仕様にのっとってください。

## セキュリティのプラグイン導入
SiteGuardを利用することで、最低限必要なことは全部プラグインで設定できます。
```
$ wp plugin install siteguard --activate
```