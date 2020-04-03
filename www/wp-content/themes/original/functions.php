<?php
//classesの取得
foreach (glob(TEMPLATEPATH."/classes/*.php") as $file) {
    require_once $file;
}
unset($file);
$CommonClass = new CommonClass();

//head内（ヘッダー）から不要なコード削除
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'index_rel_link');
remove_action('wp_head', 'parent_post_rel_link', 10, 0);
remove_action('wp_head', 'start_post_rel_link', 10, 0);
remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
remove_action('wp_head', 'feed_links', 2);
remove_action('wp_head', 'feed_links_extra', 3);
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('wp_head', 'rest_output_link_wp_head');

//dns-prefetch削除
add_filter('wp_resource_hints', 'remove_dns_prefetch', 10, 2);
function remove_dns_prefetch($hints, $relation_type)
{
    if ('dns-prefetch' === $relation_type) {
        return array_diff(wp_dependencies_unique_hosts(), $hints);
    }
    return $hints;
}

//authorの無効化
add_action('temlate_redirect', function () {
    if (is_author()) {
        wp_redirect(home_url());
        exit;
    }
});

//抜粋追加
add_post_type_support('page', 'excerpt');
//アイキャッチ追加
add_theme_support('post-thumbnails', array( 'post', 'page' ));

/**
 * ファイル更新日取得
 *
 * @param [type] $update
 * @param [type] $themes
 * @return void
 */
function update_file_date($update=null, $themes=null)
{
    if (!$themes) {
        $update = $_SERVER['DOCUMENT_ROOT'] . $update;
    }
    if (file_exists($update)) {
        $update = date('YmdHi', filemtime($update));
        $default_date = new DateTime($update);
        $default_date->setTimeZone(new DateTimeZone('Asia/Tokyo'));
        $update = $default_date->format('YmdHi');
        return $update;
    }
}