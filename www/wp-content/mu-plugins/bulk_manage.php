<?php
$AdminBulkManage = new AdminBulkManage();

/**
 * カスタム投稿タイプとカスタムタクソノミーの一括設定と、
 * エクスポート
 */
class AdminBulkManage
{
    private $pages = array(
        'bulk_manage' => array(
            'title' => '記事一覧取得',
            'sub' => false
        ),
        'import_post_type' => array(
            'title' => 'インポート',
            'sub' => 'bulk_manage'
        ),
        'bulk_manage_tax' => array(
            'title' => 'タクソノミー一覧取得',
            'sub' => false
        ),
        'import_tax' => array(
            'title' => 'インポート',
            'sub' => 'bulk_manage_tax'
        ),
    );

    public function __construct()
    {
        add_action('admin_menu', array( $this, 'add_menu'));
        if (!empty($_POST['export']) && $_POST['export'] == 'table') {
            ob_start();
            add_action('after_setup_theme', array($this, 'set_export_page'));
        }
    }

    /**
     * メインページの追加
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
     * サブページの追加
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
     * 管理ページのデフォルト設定
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
     * エクスポートテーブル
     *
     * @return void
     */
    public function set_export_page($th_array = array())
    {
        $table_data = '';
        if (!empty($_POST['bulk_manage'])) {
            $table_data = $this->post_type_table();
        } elseif (!empty($_POST['bulk_manage_tax'])) {
            $table_data = $this->terms_table();
        }

        //記事一覧を取得
        print <<< EOT
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8">
        <style>
            table {
                border-collapse: collapse;
            }
            th, td {
                padding: .5em;
                border: 1px solid;
            }
        </style>
    </head>
    <body>
        <table>
            {$table_data}
        </table>
    </body>
</html>
EOT;
    }


    /**
     * エクスポート記事テーブル一覧出力
     *
     * @return void
     */
    public function post_type_table()
    {
        $th_array = array('ID','title','slug','excerpt', 'parent', 'post_type', 'template');
        $th = '<tr>';
        foreach ($th_array as $v) {
            $th .= '<th>'.$v.'</th>';
        }
        unset($v);
        $th .= '</tr>';

        $args = array(
            'post_type' => $_POST['bulk_manage'],
            'posts_per_page' => -1
        );
        $data = get_posts($args);
        $td = '';
        foreach ($data as $v) {
            $excerpt = nl2br($v->post_excerpt);
            $template_file = get_post_meta($v->ID, '_wp_page_template', true);
            $td .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                $v->ID,
                $v->post_title,
                $v->post_name,
                $excerpt,
                $v->post_parent,
                $v->post_type,
                $template_file
            );
        }
        unset($v);
        return $th.$td;
    }


    /**
     * エクスポートtermテーブル一覧出力
     *
     * @return void
     */
    public function terms_table()
    {
        $th_array = array('ID','name','slug', 'parent', 'taxonomy');
        $th = '<tr>';
        foreach ($th_array as $v) {
            $th .= '<th>'.$v.'</th>';
        }
        unset($v);
        $th .= '</tr>';

        $td = '';
        $data = get_terms($_POST['bulk_manage_tax'], array('hide_empty' => false));
        if (!empty($data) && !is_wp_error($data)) {
            foreach ($data as $v) {
                $td .= sprintf(
                    '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                    $v->term_id,
                    $v->name,
                    $v->slug,
                    $v->parent,
                    $_POST['bulk_manage_tax']
                );
            }
            unset($v);
        }
        return $th.$td;
    }


    /**
     * 記事一覧ページ設定
     *
     * @return void
     */
    public function bulk_manage_setting()
    {
        $args = array(
            'public' => true
        );
        $post_types = get_post_types($args, 'names', 'and');

        $select = '<select name="' . $_GET['page'] . '">';
        foreach ($post_types as $post_type) {
            $select .= '<option>' . $post_type . '</option>';
        }
        unset($post_type);
        $select .= '<select>';

        $url = site_url();

        $html = <<< EOT
        <form action="{$url}/wp-admin/admin.php" method="post" target="_blank">
            <table class="form-table">
                <tr>
                    <th>投稿タイプを選択</td>
                    <td>{$select}</td>
                </td>
            </table>
            <p class="submit">
                <input type="submit" class="button button-primary" value="記事を取得">
            </p>
            <input type="hidden" name="export" value="table">
        </form>
EOT;
        $this->set_basic_setting($html);
    }


    /**
     * 記事のインポート
     *
     * @return void
     */
    public function import_post_type_setting()
    {
        if (!empty($_GET['import_id']) && !empty($_GET['import_num'])) {
            $url = sprintf('https://spreadsheets.google.com/feeds/list/%s/%s/public/values?alt=json', $_GET['import_id'], $_GET['import_num']);
            $json = file_get_contents($url);
            if ($json) {
                $json = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
                $json = json_decode($json, true);
                foreach ($json as $value) {
                    if (!empty($value['entry']) && is_array($value['entry'])) {
                        foreach ($value['entry'] as $v) {
                            $id = $v['gsx$id']['$t'];
                            $title = $v['gsx$title']['$t'];
                            $slug = $v['gsx$slug']['$t'];
                            $excerpt = $v['gsx$excerpt']['$t'];
                            $parent = $v['gsx$parent']['$t'];
                            $post_type = $v['gsx$posttype']['$t'];

                            $my_post = array(
                                'post_title' => $title,
                                'post_name' => $slug,
                                'post_excerpt' => $excerpt,
                                'post_parent' => $parent,
                                'post_type' => $post_type,
                                'post_status' => 'publish'
                            );
                            if ($id) {
                                $my_post['ID'] = $id;
                            }
                            $id = wp_insert_post($my_post);
                            $template_file = $v['gsx$template']['$t'];
                            if ($id && $template_file) {
                                $this->update_template($id, $template_file);
                            }
                        }
                    }
                    unset($v);
                }
                unset($value);
            }
        }
        $url = site_url();

        $html = <<< EOT
        <form action="{$url}/wp-admin/admin.php" method="get">
            <table class="form-table">
                <tr>
                    <th>シートID</td>
                    <td><input class="regular-text" name="import_id" type="text"></td>
                </tr>
                <tr>
                    <th>シート番号</td>
                    <td><input class="regular-text" name="import_num" type="text"></td>
                </tr>
            </table>
            <p class="submit"><input type="submit" class="button button-primary" value="インポート"></p>
            <input type="hidden" name="page" value="{$_GET['page']}">
        </form>
EOT;
        $this->set_basic_setting($html);
    }


    /**
     * テンプレートの更新
     *
     * @param [type] $id
     * @param [type] $template_file
     * @return void
     */
    public function update_template($id, $template_file)
    {
        update_post_meta($id, '_wp_page_template', $template_file);
    }


    /**
     * タクソノミーの設定
     *
     * @return void
     */
    public function bulk_manage_tax_setting()
    {
        $args = array(
            'public' => true
        );
        $post_types = get_post_types($args, 'names', 'and');
        
        $tax_array = array();
        foreach ($post_types as $v) {
            $tax_array = array_merge($tax_array, get_object_taxonomies($v));
        }
        unset($v);

        $select = '<select name="' . $_GET['page'] . '">';
        foreach ($tax_array as $post_type) {
            $select .= '<option>' . $post_type . '</option>';
        }
        unset($post_type);
        $select .= '<select>';
        $url = site_url();

        $html = <<< EOT
        <form action="{$url}/wp-admin/admin.php" method="post" target="_blank">
            <table class="form-table">
                <tr>
                    <th>タクソノミーを選択</td>
                    <td>{$select}</td>
                </td>
            </table>
            <p class="submit">
                <input type="submit" class="button button-primary" value="タームを取得">
            </p>
            <input type="hidden" name="export" value="table">
        </form>
EOT;
        $this->set_basic_setting($html);
    }


    /**
     * taxのインポート
     *
     * @return void
     */
    public function import_tax_setting()
    {
        if (!empty($_GET['import_id']) && !empty($_GET['import_num'])) {
            $url = sprintf('https://spreadsheets.google.com/feeds/list/%s/%s/public/values?alt=json', $_GET['import_id'], $_GET['import_num']);
            $json = file_get_contents($url);
            if ($json) {
                $json = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
                $json = json_decode($json, true);
                foreach ($json as $value) {
                    if (!empty($value['entry']) && is_array($value['entry'])) {
                        foreach ($value['entry'] as $v) {
                            $id = $v['gsx$id']['$t'];
                            $name = $v['gsx$name']['$t'];
                            $slug = $v['gsx$slug']['$t'];
                            $parent = $v['gsx$parent']['$t'];
                            $tax = $v['gsx$taxonomy']['$t'];
                            if ($id) {
                                $a = wp_update_term($id, $tax, array(
                                    'name' => $name,
                                    'slug' => $slug,
                                    'parent' => $parent
                                ));
                            } else {
                                wp_insert_term(
                                    $name,
                                    $tax,
                                    array(
                                      'slug' => $slug,
                                      'parent'=> $parent
                                    )
                                );
                            }
                        }
                    }
                    unset($v);
                }
                unset($value);
            }
        }
        $url = site_url();

        $html = <<< EOT
        <form action="{$url}/wp-admin/admin.php" method="get">
            <table class="form-table">
                <tr>
                    <th>シートID</td>
                    <td><input class="regular-text" name="import_id" type="text"></td>
                </tr>
                <tr>
                    <th>シート番号</td>
                    <td><input class="regular-text" name="import_num" type="text"></td>
                </tr>
            </table>
            <p class="submit"><input type="submit" class="button button-primary" value="インポート"></p>
            <input type="hidden" name="page" value="{$_GET['page']}">
        </form>
EOT;
        $this->set_basic_setting($html);
    }
}
