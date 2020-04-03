<?php
    get_header();

    //サイト内検索フォーム
    get_search_form();

    //ループ
    print '<ul>';
    if (have_posts()) {
        while (have_posts()) {
            the_post();
            printf('<li><a href="%s">%s</a></li>', get_the_permalink(), get_the_title());
        }
    } else {
        print '<li>投稿はありません。</li>';
    }
    print '</ul>';

    get_footer();