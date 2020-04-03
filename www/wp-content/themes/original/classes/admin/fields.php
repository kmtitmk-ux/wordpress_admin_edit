<?php

$AdminFields = new AdminFields();

/**
 * カスタムフィールド関連のclass
 */
class AdminFields
{
    private $fields = array(
        'text_test' => array(
            'name' => 'テキストフィールド',
            'post_type' => array('post'),
            'editor' => false,
            'restrict' => false,
            'sanitize' => true,
        ),
        'textarea_test' => array(
            'name' => 'テキストエリアフィールド',
            'post_type' => array('post'),
            'editor' => false,
            'restrict' => false,
            'sanitize' => false,
        ),
        'wysiwyg_textarea' => array(
            'name' => 'WYSIWYGのテキストフィールド',
            'post_type' => array('page'),
            'editor' => true,
            'restrict' => array('sample-page'),
            'sanitize' => false,
        ),
        'checkbox_test' => array(
            'name' => 'チェックボックスのフィールド',
            'post_type' => array('post'),
            'editor' => false,
            'restrict' => false,
            'sanitize' => false,
        ),
        'radio_test' => array(
            'name' => 'ラジオボタンのフィールド',
            'post_type' => array('post'),
            'editor' => false,
            'restrict' => false,
            'sanitize' => false,
        ),
        /*'フィールドのキー' => array(
            'name' => 'フィールドの名前',
            'post_type' => array('投稿タイプ'),
            'editor' => true,//wysiwygを使うかどうか
            'restrict' => array('sample-page'),//ページを限定するかどうか
            'sanitize' => false,//htmlタグを除去するかどうか
        )*/
    );


    public function __construct()
    {
        add_action('add_meta_boxes', array( $this, 'add_meta_box'));
        add_action('save_post', array( $this, 'custom_fields_posts_save'));
    }


    /**
     * カスタムフィールドのカスタマイズ（フック:add_meta_boxes）
    *
    * @return void
    */
    public function add_meta_box()
    {
        global $post;
        foreach ($this->fields as $k => $v) {
            if (!$v['restrict']) {
                add_meta_box($k, $v['name'], array($this, $k.'_call_back'), $v['post_type']);
            } elseif (in_array($post->post_name, $v['restrict'])) {
                add_meta_box($k, $v['name'], array($this, $k.'_call_back'), $v['post_type']);
            }
        }
        unset($v, $k);
    }


    /**
     * カスタムフィールドの保存処理（フック:save_post）
    *
    * @param [type] $post_id
    * @return void
    */
    public function custom_fields_posts_save($post_id)
    {
        foreach ($this->fields as $k => $v) {

            //postデータがない場合はスキップ
            if (!isset($_POST[$k])) {
                continue;
            }

            //noneceのチェック
            if (!isset($_POST[$k.'_nonce'])) {
                return $post_id;
            } else {
                $nonce = $_POST[$k.'_nonce'];
                if (! wp_verify_nonce($nonce, $k)) {
                    return $post_id;
                }
            }

            //自動保存ルーチンかどうかチェック
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return $post_id;
            }

            //編集権限のチェック
            if ($_POST['post_type'] == $v['post_type']) {
                if (! current_user_can('edit_'.$v['post_type'], $post_id)) {
                    return $post_id;
                }
            }

            //htmlタグを除去
            if ($v['sanitize']) {
                $mydata = sanitize_text_field($_POST[$k]);
            } else {
                $mydata = $_POST[$k];
            }

            update_post_meta($post_id, $k, $mydata);
        }
        unset($k, $v);
    }


    /**
     * 配列でカスタムフィールドを保存する場合の処理
    *
    * @param [string] $key
    * @return void
    */
    private function set_array_save($key)
    {
        $mydata = array();
        foreach ($_POST[$key] as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $k2 => $v2) {
                    $mydata[$k][$k2] = $v2;
                }
                unset($k2, $v2);
            } else {
                $mydata[$k] = $v;
            }
        }
        unset($k, $v);
        return $mydata;
    }


    /**
     * テキストのカスタムフィールド設定（フック:add_meta_box）
    *
    * @param [type] $post
    * @param [type] $metabox
    * @return void
    */
    public function text_test_call_back($post, $metabox)
    {
        $save_data = get_post_meta($post->ID, $metabox['id'], true);
        printf('<label><input class="regular-text" name="%s" type="text" value="%s"></label>', $metabox['id'], $save_data);
        wp_nonce_field($metabox['id'], $metabox['id'].'_nonce');
    }


    /**
     * テキストエリアのカスタムフィールド設定（フック:add_meta_box）
    *
    * @param [type] $post
    * @param [type] $metabox
    * @return void
    */
    public function textarea_test_call_back($post, $metabox)
    {
        $save_data = get_post_meta($post->ID, $metabox['id'], true);
        printf('<label><textarea class="large-text code" name="%s" rows="10" cols="50">%s</textarea></label>', $metabox['id'], $save_data);
        wp_nonce_field($metabox['id'], $metabox['id'].'_nonce');
    }


    /**
     * WYSIWYGのカスタムフィールド設定（フック:add_meta_box）
    *
    * @param [type] $post
    * @param [type] $metabox
    * @return void
    */
    public function wysiwyg_textarea_call_back($post, $metabox)
    {
        $save_data = get_post_meta($post->ID, $metabox['id'], true);
        wp_editor($save_data, $metabox['id'].'_editor', array('textarea_name' => $metabox['id']));
        wp_nonce_field($metabox['id'], $metabox['id'].'_nonce');
    }


    /**
     * チェックボックスのカスタムフィールド設定（フック:add_meta_box）
    *
    * @param [type] $post
    * @param [type] $metabox
    * @return void
    */
    public function checkbox_test_call_back($post, $metabox)
    {
        printf('<input type="hidden" name="%s" value="">', $metabox['id']);
        $save_data = get_post_meta($post->ID, $metabox['id'], true);
        $save_data = is_array($save_data)? $save_data: array();
        $checkbox = array('test1', 'test2', 'test3');
        foreach ($checkbox as $v) {
            $checkbox = '';
            if (in_array($v, $save_data)) {
                $checkbox = ' checked';
            }
            print <<< EOT
            <p>
                <label>
                    <input type="checkbox" name="{$metabox['id']}[]" value="{$v}"{$checkbox}>{$v}
                </label>
            </p>
EOT;
        }
        unset($v);
        wp_nonce_field($metabox['id'], $metabox['id'].'_nonce');
    }


    /**
    * ラジオボタンのカスタムフィールド設定（フック:add_meta_box）
    *
    * @param [type] $post
    * @param [type] $metabox
    * @return void
    */
    public function radio_test_call_back($post, $metabox)
    {
        $save_data = get_post_meta($post->ID, $metabox['id'], true);
        $checkbox = array('test1', 'test2', 'test3');
        foreach ($checkbox as $v) {
            $checkbox = '';
            if ($v == $save_data) {
                $checkbox = ' checked';
            }
            print <<< EOT
            <p>
                <label>
                    <input type="radio" name="{$metabox['id']}" value="{$v}"{$checkbox}>{$v}
                </label>
            </p>
EOT;
        }
        unset($v);
        wp_nonce_field($metabox['id'], $metabox['id'].'_nonce');
    }
}
