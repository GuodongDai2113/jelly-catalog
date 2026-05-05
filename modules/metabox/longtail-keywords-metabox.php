<?php

/**
 * includes\metabox\class-jc-longtail-keywords-metabox.php
 *
 * 长尾关键词生成器 Metabox
 *
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created: 2026.03.27 08:32
 */

namespace Jelly_Catalog\Modules\Metabox;

if (!defined('ABSPATH')) {
    exit;
} // 禁止直接访问

class Longtail_Keywords_Metabox
{
    public function __construct()
    {
        add_action('add_meta_boxes', [$this, 'add_keyword_generator_metabox']);
    }

    /**
     * 添加关键词生成器 metabox
     */
    public function add_keyword_generator_metabox()
    {
        add_meta_box(
            'longtail_keywords_metabox',
            __('Long-tail Keyword', 'jelly-catalog'),
            [$this, 'render_keyword_generator_metabox'],
            'product',
            'normal',
            'default'
        );
    }

    /**
     * 渲染关键词生成器 metabox 内容
     */
    public function render_keyword_generator_metabox($post)
    {
        ?>
<div class="jc-longtail-keywords-container">
    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row">
                    <label for="jc_base_keyword"><?php _e('Basic Keywords', 'jelly-catalog'); ?></label>
                </th>
                <td>
                    <input type="text" id="jc_base_keyword" name="jc_base_keyword" value="" size="50"
                        placeholder="<?php _e('Please enter basic keywords', 'jelly-catalog'); ?>">
                    <p class="description">
                        <?php _e('Input a basic keyword to generate various long-tail variants.', 'jelly-catalog'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <?php _e('Generated Long-tail Keywords', 'jelly-catalog'); ?>
                </th>
                <td>
                    <div id="jc_generated_keywords_container">
                        <ul id="jc_generated_keywords_list"></ul>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // 定义后缀数组
    var suffixes = [
        'Manufacturer',
        'Factory',
        'Supplier',
        'Price List',
        'Catalog',
        'Vendor',
        'Wholesaler',
        'Dealer',
        'Distributor',
        'Company',
        'Exporter',
        'Importer',
        'Wholesale',
        'Import',
        'Export',
        'China',
        'Bulk',
        'Sourcing',
        'OEM',
        'ODM',
        'Customize',
        'Service',
        'Private Label',
        'White Label'
    ];

    // 生成长尾关键词的函数
    function generateKeywords(baseKeyword) {
        var generatedKeywords = [];

        // 添加原始关键词
        if (baseKeyword.trim() !== '') {
            generatedKeywords.push(baseKeyword.trim());
        }

        // 添加后缀
        $.each(suffixes, function(index, suffix) {
            if (baseKeyword.trim() !== '') {
                generatedKeywords.push(baseKeyword.trim() + ' ' + suffix);
            }
        });

        return generatedKeywords;
    }

    // 更新显示的关键词列表
    function updateKeywordsList() {
        var baseKeyword = $('#jc_base_keyword').val();
        var generatedKeywords = generateKeywords(baseKeyword);

        var listHtml = '';
        $.each(generatedKeywords, function(index, keyword) {
            listHtml += '<li>' + keyword + '</li>';
        });

        $('#jc_generated_keywords_list').html(listHtml);

        // 同时更新文本框内容
        var allKeywordsSet = new Set(); // 使用Set确保唯一性

        // 添加生成的关键词
        generatedKeywords.forEach(function(kw) {
            if (kw.trim() !== '') {
                allKeywordsSet.add(kw.trim());
            }
        });
    }

    // 监听基础关键词输入框的变化
    $('#jc_base_keyword').on('input', function() {
        updateKeywordsList();
    });

    // 页面加载完成后立即执行一次更新
    updateKeywordsList();
});
</script>
<?php
    }
}
