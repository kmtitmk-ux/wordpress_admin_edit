<?php

$AdminFieldsApply = new AdminFieldsApply();

/**
 * カスタムフィールドの出力応用
 */
 class AdminFieldsApply
{
    private $fields = array(
        'welfare' => array(
            'name' => '福利厚生',
            'post_type' => array('page'),
            'context' => 'normal',
            'editor' => false,
            'restrict' => array('about')
        ),
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
        $args = array();
        foreach ($this->fields as $k => $v) {
            if (!isset($_POST[$k])) {
                continue;
            }
                        
            //noneceがない
            if (!isset($_POST[$k.'_nonce'])) {
                return $post_id;
            }

            $nonce = $_POST[$k.'_nonce'];
            if (! wp_verify_nonce($nonce, $k)) {
                return $post_id;
            }

            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return $post_id;
            }

            if ($_POST['post_type'] == $v['post_type']) {
                if (! current_user_can('edit_'.$v['post_type'], $post_id)) {
                    return $post_id;
                }
            }

            //データの整理
            if (is_array($_POST[$k])) {
                $mydata = $this->set_array_save($k);
            } else {
                if ($v['editor']) {
                    $mydata = $_POST[$k];
                } else {
                    $mydata = sanitize_text_field($_POST[$k]);
                }
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
     * インタビューの基本カスタムフィールド設定（フック:add_meta_box）
     *
     * @param [type] $post
     * @param [type] $metabox
     * @return void
     */
    public function interview_basic_call_back($post, $metabox)
    {
        //アップローダー設定
        $save_data = get_post_meta($post->ID, $metabox['id'], true);
        $html_array = array();
        $img_array = array(
            'kvImg' => 'メイン画像（PC）',
            'kvImgSp' => 'メイン画像（スマホ）',
            'archiveImg' => 'アーカイブ画像（PC）',
            'archiveImgSp' => 'アーカイブ画像（スマホ）',
            'sliderImg' => 'スライダー画像'
        );
        foreach ($img_array as $k => $v) {
            $args = array(
                'name' => $v,
                'key' => $metabox['id'],
                'key_count' => false,
                'key_sub' => $k,
                'img_id' => !empty($save_data[$k]) ? $save_data[$k] : '',
                'remove' => false
            );
            $html_array[] = $this->set_uploads_field($args);
        }
        unset($k, $v);


        //その他のフィールド
        $catchphrase = !empty($save_data['catchphrase']) ? $save_data['catchphrase'] : '';
        $year = !empty($save_data['year']) ? $save_data['year'] : '';
        $jobOther = !empty($save_data['jobOther']) ? $save_data['jobOther'] : array();


        //チェックボックス設定
        $terms = get_terms('job_type', array('hide_empty' => false));
        $checkbox = '';
        foreach ($terms as $v) {
            $checked = '';
            if (in_array($v->term_id, $jobOther)) {
                $checked = 'checked';
            }
            $checkbox .= sprintf('<label><input name="%s[jobOther][]" type="checkbox" value="%s" %s>%s</label>&emsp;', $metabox['id'], $v->term_id, $checked, $v->name);
        }
        unset($v);
        
        print <<< EOT
        <div class="add_field mt-5 mb-5 pb-5 border-0">
            {$html_array[0]}
            {$html_array[1]}
            {$html_array[2]}
            {$html_array[3]}
            {$html_array[4]}
            <div class="d-flex flex-wrap mb-4">
                <div class="col-12 col-md-3">
                    <label>キャッチフレーズ</label>
                </div>
                <div class="col-12 col-md-4">
                    <input class="regular-text" name="interview_basic[catchphrase]" type="text" value="">
                </div>
            </div>
            <div class="d-flex flex-wrap mb-4">
                <div class="col-12 col-md-3">
                    <label>入社年</label>
                </div>
                <div class="col-12 col-md-4">
                    <input class="regular-text" name="interview_basic[year]" type="text" value="">
                </div>
            </div>
        </div>
EOT;
        wp_nonce_field($metabox['id'], $metabox['id'].'_nonce');
    }


    /**
     * インタビューのセクション1カスタムフィールド設定（フック:add_meta_box）
     *
     * @param [type] $post
     * @param [type] $metabox
     * @return void
     */
    public function interview_section1_call_back($post, $metabox)
    {
        //アップロードフィールド
        $args = array(
            'name' => '画像',
            'key' => $metabox['id'],
            'key_count' => false,
            'key_sub' => 'img',
            'img_id' => !empty($save_data['img']) ? $save_data['img'] : '',
            'remove' => false
        );
        $img_html = $this->set_uploads_field($args);

        print <<< EOT
        <div class="mt-5 mb-5 pb-5 border-0">
            {$img_html}
        </div>
EOT;

        //qa
        $qa = !empty($save_data['qa']) ? $save_data['qa'] : array(array('title' => '', 'content' => ''));
        $key = $metabox['id'].'[qa]';
        $this->set_multi_field($key, $qa);

        wp_nonce_field($metabox['id'], $metabox['id'].'_nonce');
    }


    /**
     * インタビューのセクション2カスタムフィールド設定（フック:add_meta_box）
     *
     * @param [type] $post
     * @param [type] $metabox
     * @return void
     */
    public function interview_section2_call_back($post, $metabox)
    {
        $save_data = get_post_meta($post->ID, $metabox['id'], true);

        //アップロードフィールド
        $args = array(
            'name' => '画像',
            'key' => $metabox['id'],
            'key_count' => false,
            'key_sub' => 'img',
            'img_id' => !empty($save_data['img']) ? $save_data['img'] : '',
            'remove' => false
        );
        $img_html = $this->set_uploads_field($args);

        print <<< EOT
        <div class="mt-5 mb-5 pb-5 border-0">
            {$img_html}
        </div>
EOT;

        //qa
        $qa = !empty($save_data['qa']) ? $save_data['qa'] : array(array('title' => '', 'content' => ''));
        $key = $metabox['id'].'[qa]';
        $this->set_multi_field($key, $qa);

        wp_nonce_field($metabox['id'], $metabox['id'].'_nonce');
    }


    /**
     * インタビューのセクション3カスタムフィールド設定（フック:add_meta_box）
     *
     * @param [type] $post
     * @param [type] $metabox
     * @return void
     */
    public function interview_section3_call_back($post, $metabox)
    {
        $save_data = get_post_meta($post->ID, $metabox['id'], true);

        //アップロードフィールド
        $args = array(
            'name' => '画像',
            'key' => $metabox['id'],
            'key_count' => false,
            'key_sub' => 'img',
            'img_id' => !empty($save_data['img']) ? $save_data['img'] : '',
            'remove' => false
        );
        $img_html = $this->set_uploads_field($args);

        //qa
        $title = !empty($save_data['qa'][0]['title'])? $save_data['qa'][0]['title']: '';
        $content = !empty($save_data['qa'][0]['content'])? $save_data['qa'][0]['content']: '';

        print <<< EOT
        <div class="add_field mt-5 mb-5 pb-5">
            {$img_html}
        </div>
        <div class="add_field d-flex align-items-start flex-wrap mt-4 mb-4 pb-4 border-0">
            <div class="d-flex col-12 col-md-3 mb-3">
                <input type="text" name="interview_section2[qa][0][title]" class="regular-text" value="">
            </div>
            <textarea class="large-text code col-12 col-md-8" name="interview_section2[qa][0][content]" rows="10" cols="50"></textarea>
        </div>
EOT;

        //qa
        $qa = !empty($save_data['qa']) ? $save_data['qa'] : array(array('title' => '', 'content' => ''));
        $key = $metabox['id'].'[qa]';
        wp_nonce_field($metabox['id'], $metabox['id'].'_nonce');
    }


    /**
     * 福利厚生のカスタムフィールド設定（フック:add_meta_box）
     *
     * @param [type] $post
     * @param [type] $metabox
     * @return void
     */
    public function welfare_call_back($post, $metabox)
    {
        $save_data = get_post_meta($post->ID, $metabox['id'], true);
        $save_data = (is_array($save_data))?$save_data :array(null);
        $this->set_multi_field($metabox['id'], $save_data);
        wp_nonce_field($metabox['id'], $metabox['id'].'_nonce');
    }


    /**
     * 社員リスト
     *
     * @param [type] $post
     * @param [type] $metabox
     * @return void
     */
    public function member_list_call_back($post, $metabox)
    {
        $save_data = get_post_meta($post->ID, $metabox['id'], true);
        if (!is_array($save_data)) {
            $save_data = array('');
        }
        $this->set_multi_img_field($metabox['id'], $save_data, array('イニシャル', '職種', '入社年'));
        wp_nonce_field($metabox['id'], $metabox['id'].'_nonce');
    }


    /**
     * フィールドが追加できるカスタムフィールドの設定
     *
     * @param string $key
     * @param array $data
     * @return void
     */
    private function set_multi_field($key='', $data=array(null))
    {
        $html = '';
        $c =  0;
        foreach ($data as $v) {
            $v['title'] = !empty($v['title'])? $v['title']: '';
            $v['content'] = !empty($v['content'])? $v['content']: '';
            $html .= <<< EOT
            <div class="add_field d-flex align-items-start flex-wrap mt-4 mb-4 pb-4 border-0">
                <div class="d-flex col-12 mb-3">
                    <button type="button" class="remove mr-3">
                        <svg aria-hidden="true" role="img" focusable="false" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
                            <path d="M14.95 6.46L11.41 10l3.54 3.54-1.41 1.41L10 11.42l-3.53 3.53-1.42-1.42L8.58 10 5.05 6.47l1.42-1.42L10 8.58l3.54-3.53z"></path>
                        </svg>
                    </button>
                    <input type="text" name="{$key}[{$c}][title]" class="regular-text" value="{$v['title']}">
                </div>
                <textarea class="large-text code" name="{$key}[{$c}][content]" rows="10" cols="50">{$v['content']}</textarea>
            </div>
EOT;
            $c ++;
        }
        unset($v);

        print <<< EOT
        {$html}
        <button type="button" class="add">
            <svg aria-hidden="true" role="img" focusable="false" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
                <path d="M10 1c-5 0-9 4-9 9s4 9 9 9 9-4 9-9-4-9-9-9zm0 16c-3.9 0-7-3.1-7-7s3.1-7 7-7 7 3.1 7 7-3.1 7-7 7zm1-11H9v3H6v2h3v3h2v-3h3V9h-3V6z"></path>
            </svg>
        </button>
EOT;
    }


    /**
     * 画像アップローダー有りでフィールドが追加できるカスタムフィールド
     *
     * @param string $key
     * @param array $data
     * @param array $item
     * @return void
     */
    private function set_multi_img_field($key='', $data=array(''), $items=array(''))
    {
        $c = 0;
        foreach ($data as $v) {

            //アップロードフィールド
            $args = array(
                'name' => '画像',
                'key' => $key,
                'key_count' => $c,
                'key_sub' => 'img',
                'img_id' => $v['img'],
                'remove' => true
            );
            $img_html = $this->set_uploads_field($args);

            //その他の項目
            $item_html = '';
            foreach ($items as $item) {
                $v[$item] = !empty($v[$item]) ? $v[$item] : '' ;
                $item_html .= <<< EOT
                <div class="d-flex flex-wrap mb-4">
                    <div class="d-flex align-items-start col-12 col-md-2"><label>{$item}</label></div>
                    <div class="d-flex align-items-start col-12 col-md-9"><input class="regular-text" name="{$key}[{$c}][{$item}]" type="text" value="{$v[$item]}"></div>
                </div>
EOT;
            }
            unset($item);

            print <<< EOT
            <div class="add_field mt-5 mb-5 pb-5" data-field-name="{$key}">
                {$img_html}
                {$item_html}
            </div>
EOT;
            $c ++;
        }
        unset($v);

        //追加ボタン
        print <<< EOT
        <button type="button" class="add">
            <svg aria-hidden="true" role="img" focusable="false" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
                <path d="M10 1c-5 0-9 4-9 9s4 9 9 9 9-4 9-9-4-9-9-9zm0 16c-3.9 0-7-3.1-7-7s3.1-7 7-7 7 3.1 7 7-3.1 7-7 7zm1-11H9v3H6v2h3v3h2v-3h3V9h-3V6z"></path>
            </svg>
        </button>
EOT;
    }


    /**
     * 画像のアップロードフィールド設定
     *
     * @param array $args
     * @return void
     */
    private function set_uploads_field($args = array())
    {
        $img_id = !empty($args['img_id'])? $args['img_id']: '';

        //削除ボタン
        if ($args['remove']) {
            $remove = <<< EOT
                <button type="button" class="remove mr-3">
                    <svg aria-hidden="true" role="img" focusable="false" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
                        <path d="M14.95 6.46L11.41 10l3.54 3.54-1.41 1.41L10 11.42l-3.53 3.53-1.42-1.42L8.58 10 5.05 6.47l1.42-1.42L10 8.58l3.54-3.53z"></path>
                    </svg>
                </button>
EOT;
        } else {
            $remove = '';
        }

        //name設定
        if (!empty($args['key_count']) || $args['key_count'] === 0) {
            $name = $args['key'].'['.$args['key_count'].']['.$args['key_sub'].']';
        } else {
            $name = $args['key'].'['.$args['key_sub'].']';
        }

        //ボタンの中の画像
        $imgBtn = wp_get_attachment_image($img_id, 'full', 0, array('class' => ''))
        ? '<button class="media-add" type="button">' . wp_get_attachment_image($img_id, 'full', 0, array('class' => '')) . '</button>'
        : '<button class="button media-add" type="button">'.$args['name'].'を設定</button>';
        $imgOther = wp_get_attachment_image_src($img_id)? '': 'hidden';

        //html
        $html = <<< EOT
        <div class="d-flex flex-wrap mb-4">
            <div class="col-12 col-md-3">
                {$remove}
                <label>{$args['name']}</label>
            </div>
            <div class="media-wrap col-12 col-md-4">
                {$imgBtn}
                <input class="media-input" name="{$name}" type="hidden" value="{$img_id}">
                <div class="media-other mt-3 {$imgOther}">
                    <button class="button media-add other" type="button">{$args['name']}を置換</button>
                    <p class="media-remove">{$args['name']}を削除</p>
                </div>
            </div>
        </div>
EOT;
        return $html;
    }
}