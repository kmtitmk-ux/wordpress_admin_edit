import "bootstrap/dist/css/bootstrap-grid.min.css";
//import 'bootstrap';


/**
 * 関数の実行
 */
if (hook_suffix == 'post.php' || hook_suffix == 'post-new.php') {
    media_uploads_field()
    add_field()
}


/**
 * カスタムフィールドのメディアアップローダー設定
 */
function media_uploads_field() {

    //画像設定
    jQuery(document).on('click', '.media-add', function (e) {
        var media_uploader;
        var _this = jQuery(this)
        e.preventDefault()
        if (media_uploader) {
            media_uploader.open()
            return
        }

        //メディアアップローダーの設定
        media_uploader = wp.media({
            title: "画像",
            library: { type: "image" },
            button: { text: "画像を設定" },
            multiple: false
        });

        //画像選択後の処理
        media_uploader.on("select", function () {
            var images = media_uploader.state().get("selection")
            images.each(function (file) {
                if (!_this.hasClass('other')) {
                    _this.removeClass('button')
                }
                let wrap = _this.parents('div.media-wrap')
                wrap.children('.media-add').removeClass('button').html('<img src="' + file.attributes.url + '" alt="">')
                wrap.children('.media-input').val(file.id)
                wrap.children('.media-other').removeClass('hidden')
                return false
            });
        });

        media_uploader.open();
    });

    //画像削除
    jQuery(document).on('click', '.media-remove', function () {
        let wrap = jQuery(this).parents('div.media-wrap')
        wrap.children('.media-add').addClass('button')
        wrap.children('.media-other ').addClass('hidden')
        wrap.children('.media-input').val('')
        var text = jQuery(this).html().replace('削除', '設定')
        wrap.children('.media-add').html(text)
    });
}


//フィールドの追加削除
function add_field() {
    if (jQuery('.add_field').length) {

        //追加
        jQuery(document).on('click', '.add_field + .add', function () {
            let field = jQuery(this).prev('.add_field')
            let add_html = field.prop('outerHTML')
            field.after(add_html)

            //name属性振り直し
            var use_fields_array = jQuery(this).parent('.inside').find('.add_field')
            var last = use_fields_array.length
            use_fields_array.each(function (i, v) {
                var input_array = jQuery(this).find('input, textarea')
                input_array.each(function () {
                    var name = jQuery(this).attr('name')
                    name = name.replace(/\[[0-9]{1,}?\]/, '[' + i + ']')
                    jQuery(this).attr('name', name)
                })
                if (last == i + 1) {
                    jQuery(this).find('input, textarea').val('');
                    jQuery(this).find('.media-other').addClass('hidden')

                    //画像ボタン修正
                    var btn = jQuery(this).find('.media-add:not(.button)')
                    if (btn.length) {
                        var text = jQuery(this).find('.media-other').find('button').text().replace('置換', '設定')
                        btn.addClass('button')
                        btn.html(text)
                    }
                }
            })
        })

        //削除
        jQuery(document).on('click', '.add_field button.remove', function () {
            var use_fields_array = jQuery(this).parents('.inside')
            var field = jQuery(this).parents('.add_field')
            if (use_fields_array.find('.add_field').length != 1) {
                field.remove()
            }

            //削除のフィールド
            use_fields_array = use_fields_array.find('.add_field')

            //name属性振り直し
            use_fields_array.each(function (i, v) {
                var input_array = jQuery(this).find('input, textarea')
                input_array.each(function () {
                    var name = jQuery(this).attr('name')
                    name = name.replace(/\[[0-9]{1,}?\]/, '[' + i + ']')
                    jQuery(this).attr('name', name);
                })
            })
        })
    }
}