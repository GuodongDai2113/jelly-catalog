(function ($) {
  "use strict";

  class ProductManager {
    constructor() {
      if (!$(".post-type-product").length) {
        return;
      }

      // 初始化所有功能模块
      this.initModules();
    }

    /**
     * 初始化所有功能模块
     */
    initModules() {
      this.initProductGallery();
      // this.initProductFAQ();
      this.initProductCategoryImageModule();
      // this.resetProductEditor();
    }

    /**
     * 初始化产品图片画廊模块
     */
    initProductGallery() {
      const container = $("#jc-gallery .product-images");
      if (!container.length) {
        return;
      }
      // 图片排序
      container.sortable({
        items: "li.image",
        cursor: "move",
        scrollSensitivity: 40,
        forcePlaceholderSize: true,
        helper: "clone",
        opacity: 0.65,
        placeholder: "ui-sortable-placeholder",
        start: function (event, ui) {
          ui.placeholder.height(ui.item.height());
          ui.placeholder.width(ui.item.width());
        },
        stop: function () {
          container.find("li.image").removeAttr("style");
          this.updateGalleryImages();
        }.bind(this),
      });

      // 删除图片
      $(document).on("click", "#jc-gallery .image a.delete", (e) => {
        e.preventDefault();
        $(e.currentTarget).closest("li.image").remove();
        this.updateGalleryImages();
      });

      // 添加图片
      $(document).on("click", ".jc-add-image a", (e) => {
        e.preventDefault();
        this.openMediaFrame(e.currentTarget);
      });
    }

    /**
     * 打开媒体框架以选择图片
     */
    openMediaFrame(button) {
      let productGalleryFrame;
      const $el = $(button);
      const deleteText = $el.data("delete") || "Delete image";

      if (productGalleryFrame) {
        productGalleryFrame.open();
        return;
      }

      productGalleryFrame = wp.media({
        title: $el.data("choose"),
        button: { text: $el.data("update") },
        library: {
          type: [
            "image/jpeg",
            "image/jpg",
            "image/png",
            "image/gif",
            "image/webp",
          ],
        },
        multiple: true,
      });

      productGalleryFrame.on("select", () => {
        const selection = productGalleryFrame.state().get("selection");

        selection.map((attachment) => {
          attachment = attachment.toJSON();
          if (attachment.id) {
            $("#jc-gallery .product-images").append(`
              <li class="image" data-attachment_id="${attachment.id}">
                <img src="${attachment.sizes.thumbnail.url}" alt="" />
                <a href="#" class="actions delete" title="${deleteText}"></a>
              </li>
            `);
          }
        });

        this.updateGalleryImages();
      });

      productGalleryFrame.open();
    }

    /**
     * 更新隐藏字段中的图片ID
     */
    updateGalleryImages() {
      const attachmentIds = $("#jc-gallery .product-images li.image")
        .map(function () {
          return $(this).data("attachment_id");
        })
        .get()
        .join(",");

      $("#product_image_gallery").val(attachmentIds);
    }

    /**
     * 初始化产品FAQ模块
     */
    initProductFAQ() {
      const faqContainer = $(".jelly-kv-wrapper");

      $("add-faq").on("click", () => {
        const index = faqContainer.find(".kv-item").length;
        const html = this.getFaqItemTemplate(index);
        faqContainer.append(html);
      });

      faqContainer.on("click", ".kv-item__remove .remove-item", function () {
        $(this).closest(".kv-item").remove();
      });
    }

    /**
     * 获取FAQ模板
     */
    getFaqItemTemplate(index) {
      return `
        <div class="kv-item">
          <div class="kv-item__key">
            <label for="product_faqs[${index}][question]">Question:</label>
            <input
              class="kv-item__key-input"
              type="text"
              id="product_faqs[${index}][question]"
              name="product_faqs[${index}][question]"
            />
          </div>
          <div class="kv-item__value">
            <label for="product_faqs[${index}][answer]">Answer:</label>
            <textarea
              class="kv-item__value-input"
              id="product_faqs[${index}][answer]"
              name="product_faqs[${index}][answer]"
            ></textarea>
          </div>
          <div class="kv-item__remove">
            <button type="button" class="button remove-item">Remove FAQ</button>
          </div>
        </div>
      `;
    }

    /**
     * 初始化产品分类图片模块
     */
    initProductCategoryImageModule() {
      $(".taxonomy-product_cat .column-thumb").on("click", function () {
        let frame;
        if (frame) {
          frame.open();
          return;
        }

        frame = wp.media({
          title: "选择或上传图片",
          button: { text: "使用此图片" },
          multiple: false,
        });

        frame.on(
          "select",
          function () {
            const attachment = frame.state().get("selection").first().toJSON();
            $(this).find("img").attr("src", attachment.url);
            const categoryId = $(this).parent().attr("id").replace("tag-", "");
            this.updateCategoryImage(categoryId, attachment.id);
          }.bind(this)
        );
        frame.open();
      });
    }

    /**
     * 更新分类图片
     */
    updateCategoryImage(categoryId, imageId) {
      $.ajax({
        url: jelly_ajax_object.ajax_url,
        type: "POST",
        data: {
          action: "update_category_image",
          category_id: categoryId,
          image_id: imageId,
          nonce: jelly_ajax_object.nonce,
        },
        success: (response) => {
          if (response.success) {
            jellyShowSuccess("分类图片更新成功");
          } else {
            jellyShowError("分类图片更新失败");
          }
        },
        error: () => {
          jellyShowError("请求失败，请重试");
        },
      });
    }


  }

  // 初始化 ProductManager 类
  $(document).ready(() => {
    new ProductManager();
  });
})(jQuery);
