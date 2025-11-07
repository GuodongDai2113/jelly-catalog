(function ($) {
  "use strict";

  class JellyCatalogCategoryEditor {
    constructor() {
      const $body = $("body");
      if (!$body.hasClass("post-type-product")) {
        return;
      }

      // 初始化产品分类图片功能
      this.initProductCategoryImageModule();

      // 初始化产品分类描述功能
      this.initProductCategoryDescriptionModule();

      // 初始化分类缩略图功能
      this.initProductCategoryThumbnailModule();

      // 初始化分类横幅图功能
      this.initProductCategoryBannerModule();

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

    /**
     * 初始化产品分类缩略图模块
     */
    initProductCategoryThumbnailModule() {
      const self = this;

      // 添加分类页面的缩略图选择器
      $(document).on("click", "#thumbnail_id_button", function (e) {
        e.preventDefault();
        self.openMediaUploader("thumbnail");
      });

      // 编辑分类页面的缩略图选择器
      $(document).on(
        "click",
        "#thumbnail_id_container .button.select-thumbnail",
        function (e) {
          e.preventDefault();
          const container = $(this).closest("#thumbnail_id_container");
          self.openMediaUploader("thumbnail", container);
        }
      );

      // 移除缩略图功能
      $(document).on(
        "click",
        "#thumbnail_id_container .button.remove-thumbnail, #remove-thumbnail",
        function (e) {
          e.preventDefault();
          const container = $(this).closest("#thumbnail_id_container");
          if (container.length) {
            // 编辑页面
            container.find(".thumbnail-preview").hide();
            container.find("#thumbnail_id").val("");
            container.find(".button.remove-thumbnail").hide();
            container.find(".button.select-thumbnail").show();
          } else {
            // 添加页面
            $("#thumbnail_id").val("");
            $(".thumbnail-preview").hide();
            $(".thumbnail-preview img").attr("src", "");
            $(".remove-thumbnail").hide();
          }
        }
      );
    }

    /**
     * 初始化产品分类横幅图模块
     */
    initProductCategoryBannerModule() {
      const self = this;

      // 添加分类页面的横幅图选择器
      $(document).on("click", "#banner_id_button", function (e) {
        e.preventDefault();
        self.openMediaUploader("banner");
      });

      // 编辑分类页面的横幅图选择器
      $(document).on(
        "click",
        "#banner_id_container .button.select-banner",
        function (e) {
          e.preventDefault();
          const container = $(this).closest("#banner_id_container");
          self.openMediaUploader("banner", container);
        }
      );

      // 移除横幅图功能
      $(document).on(
        "click",
        "#banner_id_container .button.remove-banner, #remove-banner",
        function (e) {
          e.preventDefault();
          const container = $(this).closest("#banner_id_container");
          if (container.length) {
            // 编辑页面
            container.find(".banner-preview").hide();
            container.find("#banner_id").val("");
            container.find(".button.remove-banner").hide();
            container.find(".button.select-banner").show();
          } else {
            // 添加页面
            $("#banner_id").val("");
            $(".banner-preview").hide();
            $(".banner-preview img").attr("src", "");
            $(".remove-banner").hide();
          }
        }
      );
    }

    /**
     * 打开媒体上传器
     * @param {string} type - 媒体类型 (thumbnail|banner)
     * @param {jQuery} container - 容器元素
     */
    openMediaUploader(type, container = null) {
      const isEditPage = container !== null;

      let frame = wp.media({
        multiple: false,
      });

      frame.on("select", function () {
        const attachment = frame.state().get("selection").first().toJSON();

        if (isEditPage) {
          // 编辑页面处理
          const inputField = container.find(`.${type}-id-input`);
          const preview = container.find(`.${type}-preview`);
          const img = preview.find("img");
          const removeBtn = container.find(`.button.remove-${type}`);
          const selectBtn = container.find(`.button.select-${type}`);

          inputField.val(attachment.id);
          img.attr("src", attachment.url);
          preview.show();
          removeBtn.show();
          selectBtn.hide();
        } else {
          // 添加页面处理
          const fieldId = type === "thumbnail" ? "#thumbnail_id" : "#banner_id";
          const previewClass =
            type === "thumbnail" ? ".thumbnail-preview" : ".banner-preview";
          const removeClass =
            type === "thumbnail" ? ".remove-thumbnail" : ".remove-banner";

          $(fieldId).val(attachment.id);
          $(previewClass).show();
          $(`${previewClass} img`).attr("src", attachment.url);
          $(removeClass).show();
        }
      });

      frame.open();
    }
  }

  $(document).ready(() => {
    window.JellyCatalogCategoryEditor = new JellyCatalogCategoryEditor();
  });
})(jQuery);
