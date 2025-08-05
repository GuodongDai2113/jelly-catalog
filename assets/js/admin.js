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
      this.initProductFAQ();
      this.initProductDescriptionModule();
      this.initProductCategoryImageModule();
    }

    /**
     * 初始化产品图片画廊模块
     */
    initProductGallery() {
      const container = $("#jelly_product_images_container .product_images");
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
      $(document).on(
        "click",
        "#jelly_product_images_container .image a.delete",
        (e) => {
          e.preventDefault();
          $(e.currentTarget).closest("li.image").remove();
          this.updateGalleryImages();
        }
      );

      // 添加图片
      $(document).on("click", ".jelly_add_product_images a", (e) => {
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
            $("#jelly_product_images_container .product_images").append(`
              <li class="image" data-attachment_id="${attachment.id}">
                <img src="${attachment.sizes.thumbnail.url}" alt="" />
                <ul class="actions">
                  <li><a href="#" class="delete" title="${deleteText}">X</a></li>
                </ul>
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
      const attachmentIds = $(
        "#jelly_product_images_container .product_images li.image"
      )
        .map(function () {
          return $(this).data("attachment_id");
        })
        .get()
        .join(",");

      $("#jelly_product_image_gallery").val(attachmentIds);
    }

    /**
     * 初始化产品FAQ模块
     */
    initProductFAQ() {
      const faqContainer = $(".jelly-faq-wrapper");

      $("#jelly-add-faq").on("click", () => {
        const index = faqContainer.find(".faq-item").length;
        const html = this.getFaqItemTemplate(index);
        faqContainer.append(html);
      });

      faqContainer.on("click", ".faq-item__remove .remove-item", function () {
        $(this).closest(".faq-item").remove();
      });
    }

    /**
     * 获取FAQ模板
     */
    getFaqItemTemplate(index) {
      return `
        <div class="faq-item">
          <div class="faq-item__question">
            <label for="product_faqs[${index}][question]">Question:</label>
            <input
              class="faq-item__question-input"
              type="text"
              id="product_faqs[${index}][question]"
              name="product_faqs[${index}][question]"
            />
          </div>
          <div class="faq-item__answer">
            <label for="product_faqs[${index}][answer]">Answer:</label>
            <textarea
              class="faq-item__answer-input"
              id="product_faqs[${index}][answer]"
              name="product_faqs[${index}][answer]"
            ></textarea>
          </div>
          <div class="faq-item__remove">
            <button type="button" class="button remove-item">Remove FAQ</button>
          </div>
        </div>
      `;
    }

    /**
     * 初始化产品描述模块
     */
    initProductDescriptionModule() {
      const productWrap = $(".post-type-product #wp-content-wrap");

      if (productWrap.length) {
        this.wrapProductDescription(productWrap);
        this.addShortDescriptionTips();
        this.addContentDescriptionTips();
      }
    }

    /**
     * 包装产品描述区域
     */
    wrapProductDescription(productWrap) {
      const content = $('<div class="jelly-product-description"></div>');
      productWrap.wrap(content);
    }

    /**
     * 添加短描述提示
     */
    addShortDescriptionTips() {
      const tipsShort = `
        <div class="edit-description">
          <p class="edit-title"><span class="dashicons dashicons-info-outline"></span>编写须知：</p>
          <ol>
            <li>短描述中应自然包含用户搜索频率较高的<code>关键词</code>或<code>长尾关键词</code></li>
            <li>重要关键词尽量放在开头，提高关键词权重</li>
            <li>建议在<code>100–160</code>字之间，足够提供价值但不过度冗长</li>
            <li>语言简洁明了，避免使用过多专业术语</li>
            <li>强调产品独特优势，如材质、性能、价格、使用场景等</li>
          </ol>
        </div>
      `;
      $(".post-type-product #postexcerpt .inside").append(tipsShort);
    }

    /**
     * 添加内容描述提示
     */
    addContentDescriptionTips() {
      const tipsContent = `
        <div class="edit-description">
          <p class="edit-title"><span class="dashicons dashicons-info-outline"></span>编写须知：</p>
          <ol>
            <li>详情中建议使用相关关键词，次数不少5次，平均分布于文字页面。</li>
            <li>详情中的代词全部替换为关键词，即可以用具体名称的坚决不用“它”这类词。</li>
            <li>详情每段不超过<code>500</code>个字符，超过进行分段，全文建议最低<code>1000</code>个字符数以上。</li>
            <li>详情保证原创，切勿直接从网络摘抄，网站内页面相似度不能超过<code>30%</code>。</li>
          </ol>
        </div>
      `;
      $(".post-type-product .jelly-product-description").append(tipsContent);
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
