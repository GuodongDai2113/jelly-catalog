(function ($) {
  "use strict";

  class JellyCatalogCategoryEditor {
    constructor() {
      const $body = $("body");
      if (
        !$body.hasClass("post-type-product") ||
        !$body.hasClass("edit-tags-php")
      ) {
        return;
      }

      // 初始化产品分类图片功能
      this.initProductCategoryImageModule();

      // 初始化产品分类描述功能
      this.initProductCategoryDescriptionModule();

      console.log("Jelly Catalog Category Editor Initialized");

    }

    /**
     * 初始化产品分类图片模块
     */
    initProductCategoryImageModule() {
      if (!$(".taxonomy-product_cat").length) {
        return;
      }

      $(".column-thumb, .column-jc-thumb").on("click", function () {
        let frame;
        if (frame) {
          frame.open();
          return;
        }

        frame = wp.media({
          multiple: false,
        });

        frame.on(
          "select",
          function () {
            const attachment = frame.state().get("selection").first().toJSON();
            $(this).find("img").attr("src", attachment.url);
            const categoryId = $(this).parent().attr("id").replace("tag-", "");
            $.ajax({
              url: jc_ajax.ajax_url,
              type: "POST",
              data: {
                action: "update_product_category_image",
                category_id: categoryId,
                image_id: attachment.id,
                nonce: jc_ajax.nonce,
              },
              success: (response) => {},
            });
          }.bind(this)
        );
        frame.open();
      });
    }

    /**
     * 初始化产品分类描述模块
     */
    initProductCategoryDescriptionModule() {
      if (!$(".taxonomy-product_cat").length) {
        return;
      }

      $(".wp-list-table").on("dblclick", ".column-description", function () {
        const cell = $(this);
        // 保留原始 HTML
        const source = cell.html();
        // 排除 .screen-reader-text 和 aria-hidden="true" 的内容
        const description = cell
          .clone()
          .find('.screen-reader-text, [aria-hidden="true"]')
          .remove()
          .end()
          .text()
          .trim();
        const termId = cell.closest("tr").attr("id").replace("tag-", "");

        // 检查是否已经处于编辑状态
        if (cell.find("textarea").length > 0) {
          return;
        }

        // 创建编辑器元素
        const editor = $('<div class="jc-description-editor"></div>');
        const textarea = $(
          '<textarea class="description-textarea" rows="4" cols="40"></textarea>'
        );
        const actions = $('<div class="description-actions"></div>');
        const saveBtn = $(
          ".inline-edit-save .save",
          $(".inline-edit-row")
        ).clone();
        const cancelBtn = $(
          ".inline-edit-save .cancel",
          $(".inline-edit-row")
        ).clone();

        saveBtn.on("click", function (e) {
          e.preventDefault();
          const updatedDescription = textarea.val();
          $.ajax({
            url: jc_ajax.ajax_url,
            type: "POST",
            data: {
              action: "update_product_category_description",
              term_id: termId,
              description: updatedDescription,
              nonce: jc_ajax.nonce,
            },
            success: function (response) {
              if (response.success) {
                // 更新显示的描述
                cell.html("<p>" + updatedDescription + "</p>");
              }
            },
          });
        });

        cancelBtn.on("click", function (e) {
          e.preventDefault();
          cell.html(source);
        });

        // 设置文本域的值
        textarea.val(description);

        // 设置保存按钮的属性
        saveBtn.attr("data-term-id", termId);

        // 组装编辑器
        editor.append(textarea, actions);
        actions.append(cancelBtn, saveBtn);

        // 将编辑器插入到当前单元格
        cell.html(editor);

        // 聚焦到文本域
        textarea.focus();
      });
    }
  }

  $(document).ready(() => {
    window.JellyCatalogCategoryEditor = new JellyCatalogCategoryEditor();
  });
})(jQuery);
