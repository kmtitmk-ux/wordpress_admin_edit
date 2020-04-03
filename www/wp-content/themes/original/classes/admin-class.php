<?php

$AdminClass = new AdminClass();

/**
 * 管理画面のカスタマイズ
 */
class AdminClass
{
    //カスタム投稿タイプの設定
    private $add_post_type = array(
        'news' => array(
            'name' => 'ニュース',
            'description' => 'ニュース掲載しています。',
            'supports' => array('title', 'editor'),
        ),
        'blog' => array(
            'name' => 'ブログ',
            'description' => 'ブログを掲載しています。',
            'supports' => array('title'),
        )
    );

    //カスタムタクソノミーの設定
    private $add_taxonomies = array(
        'news_cats' => array(
            'label' => 'ニュースのカテゴリー',
            'post_type' => array('news'),
            'hierarchical' => true
        ),
        'blog_cats' => array(
            'label' => 'ブログのカテゴリー',
            'post_type' => array('blog'),
            'hierarchical' => true
        )
    );

    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'theme_name_scripts'));

        //カスタム投稿タイプ
        add_action( 'init', array($this, 'create_post_type'));

        //カスタムタクソノミー
        add_action( 'init', array($this, 'create_taxonomies'));

        //メニュー削除
        add_action('admin_menu', array($this, 'remove_menus'));

        //その他のclass実行
        foreach (glob(get_template_directory()."/classes/admin/*.php") as $file) {
            require_once $file;
        }
        unset($file);
    }


    /**
     * 外部ファイルの読み込み、
     * hook_suffixをjsに渡す（フック:admin_enqueue_scripts）
     *
     * @param [type] $hook_suffix
     * @return void
     */
    public function theme_name_scripts($hook_suffix)
    {
        $var = update_file_date(get_template_directory() . '/css/admin.css', true);
        wp_enqueue_style('admin-css', get_template_directory_uri() . '/css/admin.css');

        $var = update_file_date(get_template_directory() . '/js/admin.js', true);
        wp_enqueue_script('admin-js', get_template_directory_uri().'/js/admin.js', array('jquery'), $var, true);

        $tag = '';
        if (function_exists('wp_add_inline_script')) {
            $tag .= <<< EOT
            let hook_suffix = '{$hook_suffix}'
EOT;
            wp_add_inline_script('admin-js', $tag, 'before');
        }
    }


    /**
     * 左のメニュー削除
     *
     * @return void
     */
    public function remove_menus()
    {
        //ユーザーのロールによって変更する場合利用
        $login_user = wp_get_current_user();

        /*remove_menu_page('edit.php'); //投稿メニュー
        remove_menu_page('index.php'); //ダッシュボード
        remove_menu_page('upload.php'); //メディア
        remove_menu_page('edit.php?post_type=page'); //ページ追加
        remove_menu_page('edit-comments.php'); //コメントメニュー
        remove_menu_page('themes.php'); //外観メニュー
        remove_menu_page('plugins.php'); //プラグインメニュー
        remove_menu_page('tools.php'); //ツールメニュー
        remove_menu_page('options-general.php'); //設定メニュー*/
    }


    /**
     * カスタムタクソノミー追加
     *
     * @return void
     */
    public function create_taxonomies()
    {
        foreach ($this->add_taxonomies as $k => $v) {
            $args = array(
                'hierarchical' => $v['hierarchical'],
                'label' => $v['label'],
                'show_ui' => true,
                'query_var' => true,
                'singular_label' => $v['label'],
                'show_in_rest' => true
            );
            register_taxonomy($k, $v['post_type'], $args);
        }
        unset($k, $v);
    }


    /**
     * カスタム投稿タイプの追加
     *
     * @return void
     */
    public function create_post_type()
    {
        foreach ($this->add_post_type as $k => $v) {
            $args = array(
                'supports' => $v['supports'],
                'labels' => array(
                    'name' => $v['name'],
                    'singular_name' => $k,
                ),
                'description' => $v['description'],
                'public' => true,
                'has_archive' => true,
                'show_in_rest' => true,
            );
            register_post_type($k, $args);
        }
        unset($k,$v);
    }
}
