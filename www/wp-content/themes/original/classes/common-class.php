<?php

/**
 * テーマで実行する共通calss
 */
class CommonClass
{
    public $url;
    public function __construct()
    {
        if (is_admin()) {
            return;
        }
        $this->url = home_url();
        add_action('wp_enqueue_scripts', array($this, 'theme_name_scripts'));
        add_filter('excerpt_more', array($this, 'new_excerpt_more'));
        add_action('pre_get_posts', array($this, 'custom_loop'));
    }


    /**
     * カスタムループの設定（フック:pre_get_posts）
     *
     * @param [type] $query
     * @return void
     */
    public function custom_loop($query)
    {
        if (!is_admin() && $query->is_main_query()) {
            if (is_search()) {
                //カスタムフィールドを検索に含める処理
                $query->set('post__in', $this->custom_field_search());
                $query->set('s', false);
                $query->set('post_type', 'post');
            }
        }
    }


    /**
     * カスタムフィールドを検索に含める場合
     *
     * @return void
     */
    private function custom_field_search()
    {
        //検索ワード
        $search_word = !empty($_GET['s'])? $_GET['s']: '';

        //通常検索の配列
        $search_array = $this->get_posts_id_array(array(
            'posts_per_page' => -1,
            's' => $search_word
        ));

        //カスタムフィールド検索の配列
        $meta_array = $this->get_posts_id_array(array(
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'text_test',
                    'value' => $search_word
                ),
                array(
                    'key' => 'textarea_test',
                    'value' => $search_word,
                    'compare' => 'LIKE'
                ),
                /*array(
                    'key' => '検索したいカスタムフィールドのkey',
                    'value' => $search_word
                    'compare' => 'LIKE'//部分一致の場合設定
                )*/
            )
        ));

        //配列の重複削除
        $array_id = array_merge($search_array, $meta_array);
        $array_id = array_unique($array_id);
        return array_values($array_id);
    }


    /**
     * 投稿IDを配列で返す
     *
     * @param array $args
     * @return void
     */
    public function get_posts_id_array($args = array())
    {
        $id_array = array();
        $data = get_posts($args);
        if ($data) {
            foreach ($data as $v) {
                $id_array[] = $v->ID;
            }
            unset($v);
        }
        return $id_array;
    }


    /**
     * bodyタグにclassを設定
     *
     * @return void
     */
    public function custom_body_class()
    {
        global $post;
        if (is_front_page() || is_home()) {
            print 'home';
        } elseif (is_page()) {
            print $post->post_name.'_page';
        } elseif (is_archive()) {
            $archive_data = get_queried_object();
            print $archive_data->name.'_page';
        } elseif (is_singular('information')) {
            print $post->post_type.'_page detail_page';
        } elseif (is_singular('interview')) {
            $job_type = !empty(get_the_terms($post->id, 'job_type')[0])? ' '.get_the_terms($post->id, 'job_type')[0]->slug: '';
            print $post->post_type.'_page detail' . $job_type;
        } elseif (is_404()) {
            print 'information_page detail_page';
        }
    }


    /**
     * 新卒採用関連ページのbody_classを設定
     *
     * @return void
     */
    public function new_graduate_body_class()
    {
        if (is_page()) {
            $class = '';
            global $post;
            if (strpos($post->post_name, 'attraction-') !== false) {
                $class = str_replace('attraction-', 'attraction_', $post->post_name);
            } else {
                $class = $post->post_name;
            }

            if ($post->post_name == 'new_graduate') {
                print 'new_graduate_page';
            } else {
                print 'new_graduate_page new_graduate_detail ' . $class . '_page';
            }
        }
    }


    /**
     * パンくずリスト
     *
     * @return void
     */
    public function breadcrumbList()
    {
        global $post;

        $breadcrumbList = array(
            array('path' => home_url(), 'name' => 'HOME'),
        );

        if (is_archive()) {
            $archive_data = get_queried_object();
            $breadcrumbList[] = array('path' => '/', 'name' => $archive_data->label);
        }

        if (is_page()) {
            $breadcrumbList[] = array('path' => get_the_permalink($post->ID), 'name' => get_the_title($post->ID));
        }

        if (is_single()) {
            $archive_data = get_post_type_object($post->post_type);
            $breadcrumbList[] = array('path' => get_post_type_archive_link($post->post_type), 'name' => $archive_data->label);
            $breadcrumbList[] = array('path' => get_the_permalink($post->ID), 'name' => get_the_title($post->ID));
        }
    }


    /**
     * 抜粋の省略文字変更
     * excerpt_moreのフィルターフック
     *
     * @param [type] $more
     * @return void
     */
    public function new_excerpt_more($more)
    {
        return '...';
    }


    /**
     * タイトル、ディスクリプションなどの設定
     *
     * @return void
     */
    public function the_title_desc()
    {
        $title = get_bloginfo('name');
        $desc = get_bloginfo('description');
        $img = $this->url . '/data/images/ogimage.jpg';
        $url = $this->url;
        $type = 'website';

        if (!is_home() || !is_front_page()) {
            $type = 'article';
            if (is_404()) {
                $title = '404 NOT FOUND';
                $desc = '404 NOT FOUND';
            } elseif (is_archive()) {
                $archive_data = get_queried_object();
                $title = $archive_data->label . ' | ' . $title;
                $desc = $archive_data->description;
                $url = get_the_permalink();
            } else {
                $title = get_the_title() . ' | ' . $title;
                $desc = get_the_excerpt();
                $url = get_the_permalink();

                //OGP画像
                $image_id = get_post_thumbnail_id();
                if ($image_id) {
                    $image_url = wp_get_attachment_image_src($image_id, true);
                    $img = $image_url[0];
                }
            }
        }
        print <<< EOT
<title>{$title}</title>
<meta name="description" content="{$desc}">
EOT;

        //アーカイブと404では表示させない
        if (is_404()) {
            return;
        }
        print <<< EOT
<meta property="og:site_name" content="{$title}">
<meta property="og:title" content="{$title}">
<meta property="og:description" content="{$desc}">
<meta property="og:url" content="{$url}">
<meta property="og:image" content="{$img}">
<meta property="og:type" content="{$type}">
<meta property="fb:app_id" content="">

<meta itemprop="name" content="{$title}">
<meta itemprop="url" content="{$url}">

<meta name="twitter:title" content="{$title}">
<meta name="twitter:url" content="{$url}">
<meta name="twitter:card" content="summary">
<meta name="twitter:description" content="{$desc}">

EOT;
    }


    /**
     * 外部ファイルの読み込みと条件分岐の変数設定（フック:wp_enqueue_scripts）
     *
     * @return void
     */
    public function theme_name_scripts($hook_suffix)
    {
        $this->set_libs();

        $ver = update_file_date(get_template_directory().'/js/public.js');
        wp_enqueue_script('public-js', get_template_directory_uri().'/js/public.js', array('jquery'), $ver, true);

        //wordpressの条件分岐をjsに渡す
        switch (true) {
            case (is_home() || is_front_page()):
                $page_type = 'home';
                break;
            case is_page():
                $page_type = 'page';
                break;
            case is_single():
                $page_type = 'single';
                break;
            case is_archive():
                $page_type = 'archive';
                break;
            default:
                $page_type = '';
                break;
        }

        $tag = '';
        if (function_exists('wp_add_inline_script')) {
            $tag .= <<< EOT
            let conditional_tag = '{$page_type}'
EOT;
            wp_add_inline_script('public-js', $tag, 'before');
        }
    }


    /**
     * jsライブラリの設定
     *
     * @return void
     */
    private function set_libs()
    {
        //jquery
        wp_deregister_script('jquery');
        wp_enqueue_script('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js', array(), '3.4.1', true);
    }
}
