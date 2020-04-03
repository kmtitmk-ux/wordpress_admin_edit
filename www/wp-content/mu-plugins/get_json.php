<?php
$GetJson = new GetJson();

/**
 * スプレッドシートからjson形式に変換するクラス
 */
class GetJson
{
    private $pages = array(
        'get_json' => array(
            'title' => 'JSON取得',
            'sub' => false
        ),
    );


    public function __construct()
    {
        //データベース保存処理
        add_action('plugins_loaded', array( $this, 'option_save'));
        //メニュー追加のフック
        add_action('admin_menu', array( $this, 'add_menu'));
    }


    /**
     * 管理画面のメインページ追加
     *
     * @return void
     */
    public function add_menu()
    {
        foreach ($this->pages as $k => $v) {
            if ($v['sub']) {
                $this->add_sub_menu($k, $v);
            } else {
                add_menu_page($v['title'], $v['title'], 'manage_options', $k, array( $this, $k.'_setting' ), 'dashicons-welcome-learn-more', 80);
            }
        }
        unset($k, $v);
    }


    /**
     * 管理画面のサブページ追加（今は利用していない）
     * 機能拡張する時に利用してください。
     *
     * @param string $key
     * @param array $sub
     * @return void
     */
    public function add_sub_menu($key=null, $data=array())
    {
        add_submenu_page($data['sub'], $data['title'], $data['title'], 'manage_options', $key, array($this, $key . '_setting'));
    }


    /**
     * 管理ページを追加するときデフォルトのテンプレート設定
     *
     * @param [type] $html
     * @return void
     */
    public function set_basic_setting($html = null)
    {
        if (!empty($_GET['page'])) {
            print <<< EOT
            <div class="wrap add_page">
                <h1>{$this->pages[$_GET['page']]['title']}</h1>
                {$html}
            </div>
EOT;
        }
    }


    /**
     * 記事のインポート
     *
     * @return void
     */
    public function get_json_setting()
    {
        $textarea = '';
        if (!empty($_GET['get_json_id']) && !empty($_GET['get_json_num'])) {
            $url = sprintf('https://spreadsheets.google.com/feeds/list/%s/%s/public/values?alt=json', $_GET['get_json_id'], $_GET['get_json_num']);
            $json = file_get_contents($url);
            $data = [];
            if ($json) {
                $json = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
                $json = json_decode($json, true);
                foreach ($json as $value) {
                    if (!empty($value['entry']) && is_array($value['entry'])) {
                        foreach ($value['entry'] as $v) {
                            $data[$v['gsx$ja']['$t']] = [
                                $v['gsx$en']['$t'],
                                //$v['gsx$en']['$t']] その他を設定する場合配列に追加していく
                            ];
                        }
                    }
                    unset($v);

                    if ($data) {
                        $textarea = '<textarea class="large-text" rows="10">' . json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) . '</textarea>';
                    }
                }
                unset($value);
            }
        }
        $nonce = wp_nonce_field('get_json', 'get_json_nonce', false, false);
        $get_json_id = get_option('get_json_id');
        $get_json_num = get_option('get_json_num');
        $html = <<< EOT
        <form action="/wp-admin/admin.php" method="get">
            <table class="form-table">
                <tr>
                    <th>シートID</td>
                    <td><input class="regular-text" name="get_json_id" type="text" value="{$get_json_id}"></td>
                </tr>
                <tr>
                    <th>シート番号</td>
                    <td><input class="regular-text" name="get_json_num" type="text" value="{$get_json_num}"></td>
                </tr>
            </table>
            {$nonce}
            <p class="submit"><input type="submit" class="button button-primary" value="JSONを取得する"></p>
            <input type="hidden" name="page" value="{$_GET['page']}">
            {$textarea}
        </form>
EOT;
        $this->set_basic_setting($html);
    }


    /**
     *
     * @return void
     */
    public function option_save()
    {
        if (!empty($_GET['get_json_nonce'])
        && wp_verify_nonce($_GET['get_json_nonce'], 'get_json')) {
            update_option('get_json_id', $_GET['get_json_id']);
            update_option('get_json_num', $_GET['get_json_num']);
        }
    }
}
