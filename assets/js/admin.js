jQuery(document).ready(function($) {
    // 图片排序
    $('#product_images_container .product_images').sortable({
        items: 'li.image',
        cursor: 'move',
        scrollSensitivity: 40,
        forcePlaceholderSize: true,
        forceHelperSize: false,
        helper: 'clone',
        opacity: 0.65,
        placeholder: 'ui-sortable-placeholder',
        start: function(event, ui) {
            ui.placeholder.height(ui.item.height());
            ui.placeholder.width(ui.item.width());
        },
        stop: function(event, ui) {
            ui.item.removeAttr('style');
            product_gallery_update_images();
        }
    });

    // 删除图片
    $(document).on('click', '#product_images_container .image a.delete', function() {
        $(this).closest('li.image').remove();
        product_gallery_update_images();
        return false;
    });

    // 添加图片
    $(document).on('click', '.add_product_images a', function(e) {
        e.preventDefault();

        var product_gallery_frame;
        var $el = $(this);
        var deleteText = $el.data('delete') || 'Delete image';

        // 创建媒体框架
        if (product_gallery_frame) {
            product_gallery_frame.open();
            return;
        }

        product_gallery_frame = wp.media({
            title: $el.data('choose'),
            button: {
                text: $el.data('update'),
            },
            library: {
                type: ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']
            },
            states: [
                new wp.media.controller.Library({
                    title: $el.data('choose'),
                    filterable: 'all',
                    multiple: true,
                    library: wp.media.query({
                        type: ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']
                    })
                })
            ]
        });

        // 当选择图片时
        product_gallery_frame.on('select', function() {
            var selection = product_gallery_frame.state().get('selection');

            selection.map(function(attachment) {
                attachment = attachment.toJSON();

                if (attachment.id) {
                    $('#product_images_container .product_images').append(
                        '<li class="image" data-attachment_id="' + attachment.id + '">' +
                        '<img src="' + attachment.sizes.thumbnail.url + '" alt="" />' +
                        '<ul class="actions">' +
                        '<li><a href="#" class="delete" title="' + deleteText + '">X</a></li>' +
                        '</ul>' +
                        '</li>'
                    );
                }
            });

            product_gallery_update_images();
        });

        product_gallery_frame.open();
    });

    // 更新隐藏字段中的图片ID
    function product_gallery_update_images() {
        var attachment_ids = '';

        $('#product_images_container .product_images li.image').each(function() {
            var attachment_id = $(this).attr('data-attachment_id');
            attachment_ids = attachment_ids + attachment_id + ',';
        });

        $('#product_image_gallery').val(attachment_ids.slice(0, -1)); // 移除末尾的逗号
    }
});