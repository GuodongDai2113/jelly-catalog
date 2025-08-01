jQuery(document).ready(function ($) {
  // 图片排序
  $("#jelly_product_images_container .product_images").sortable({
    items: "li.image",
    cursor: "move",
    scrollSensitivity: 40,
    forcePlaceholderSize: true,
    forceHelperSize: false,
    helper: "clone",
    opacity: 0.65,
    placeholder: "ui-sortable-placeholder",
    start: function (event, ui) {
      ui.placeholder.height(ui.item.height());
      ui.placeholder.width(ui.item.width());
    },
    stop: function (event, ui) {
      ui.item.removeAttr("style");
      product_gallery_update_images();
    },
  });

  // 删除图片
  $(document).on(
    "click",
    "#jelly_product_images_container .image a.delete",
    function () {
      $(this).closest("li.image").remove();
      product_gallery_update_images();
      return false;
    }
  );

  // 添加图片
  $(document).on("click", ".jelly_add_product_images a", function (e) {
    e.preventDefault();

    var product_gallery_frame;
    var $el = $(this);
    var deleteText = $el.data("delete") || "Delete image";

    // 创建媒体框架
    if (product_gallery_frame) {
      product_gallery_frame.open();
      return;
    }

    product_gallery_frame = wp.media({
      title: $el.data("choose"),
      button: {
        text: $el.data("update"),
      },
      library: {
        type: [
          "image/jpeg",
          "image/jpg",
          "image/png",
          "image/gif",
          "image/webp",
        ],
      },
      states: [
        new wp.media.controller.Library({
          title: $el.data("choose"),
          filterable: "all",
          multiple: true,
          library: wp.media.query({
            type: [
              "image/jpeg",
              "image/jpg",
              "image/png",
              "image/gif",
              "image/webp",
            ],
          }),
        }),
      ],
    });

    // 当选择图片时
    product_gallery_frame.on("select", function () {
      var selection = product_gallery_frame.state().get("selection");

      selection.map(function (attachment) {
        attachment = attachment.toJSON();

        if (attachment.id) {
          $("#jelly_product_images_container .product_images").append(
            '<li class="image" data-attachment_id="' +
              attachment.id +
              '">' +
              '<img src="' +
              attachment.sizes.thumbnail.url +
              '" alt="" />' +
              '<ul class="actions">' +
              '<li><a href="#" class="delete" title="' +
              deleteText +
              '">X</a></li>' +
              "</ul>" +
              "</li>"
          );
        }
      });

      product_gallery_update_images();
    });

    product_gallery_frame.open();
  });

  // 更新隐藏字段中的图片ID
  function product_gallery_update_images() {
    var attachment_ids = "";

    $("#jelly_product_images_container .product_images li.image").each(
      function () {
        var attachment_id = $(this).attr("data-attachment_id");
        attachment_ids = attachment_ids + attachment_id + ",";
      }
    );

    $("#jelly_product_image_gallery").val(attachment_ids.slice(0, -1)); // 移除末尾的逗号
  }

  // 添加新的问题
  function getFaqItemTemplate(
    index,
    questionLabel = "Question:",
    answerLabel = "Answer:",
    removeLabel = "Remove FAQ"
  ) {
    return `
          <div class="faq-item">
              <div class="faq-item__question">
                  <label for="product_faqs[${index}][question]">${questionLabel}</label>
                  <input
                      class="faq-item__question-input"
                      type="text"
                      id="product_faqs[${index}][question]"
                      name="product_faqs[${index}][question]"
                  />
              </div>
              <div class="faq-item__answer">
                  <label for="product_faqs[${index}][answer]">${answerLabel}</label>
                  <textarea
                      class="faq-item__answer-input"
                      id="product_faqs[${index}][answer]"
                      name="product_faqs[${index}][answer]"
                  ></textarea>
              </div>
              <div class="faq-item__remove">
                  <button type="button" class="button remove-item">${removeLabel}</button>
              </div>
          </div>
        `;
  }

  // 初始化产品FAQ
  function initProductFAQ() {
    const faqContainer = $(".jelly-faq-wrapper");

    const faqLabels = {
      question: "Question:",
      answer: "Answer:",
      remove: "Remove FAQ",
    };

    $("#jelly-add-faq").on("click", function () {
      const index = faqContainer.find(".faq-item").length;
      const html = getFaqItemTemplate(
        index,
        faqLabels.question,
        faqLabels.answer,
        faqLabels.remove
      );
      faqContainer.append(html);
    });

    faqContainer.on("click", ".faq-item__remove .remove-item", function () {
      $(this).closest(".faq-item").remove();
    });
  }

  initProductFAQ();

  function initFeaturedImageValidation() {
    const $ = jQuery;

    const isValidImage = (attachment) => {
      if (!attachment || !attachment.width || !attachment.height) return false;

      const w = attachment.width;
      const h = attachment.height;
      const ratio = w / h;

      return ratio === 1 && w >= 300 && h >= 300 && w <= 1200 && h <= 1200;
    };

    const applyGrayOverlay = (attachments) => {
      $(".attachments .attachment").each(function (index) {
        const $thumb = $(this);
        const attachment = attachments[index];
        const data = attachment.toJSON();

        if (!isValidImage(data)) {
          $thumb.addClass("disabled-featured");
          $thumb.removeClass("selected"); // 取消可能的选中
        }
      });
    };

    // 替换 Featured Image 的媒体选择器（#postimagediv）
    const original = wp.media.featuredImage.frame;

    wp.media.featuredImage.frame = function () {
      const frame = original();

      frame.on("open", function () {
        const attachments = frame.content.get().collection.models;

        const tryApply = setInterval(() => {
          if (attachments.length === 0 || attachments.length === 1) return;
          applyGrayOverlay(attachments);
          clearInterval(tryApply);
        }, 200);
      });

      frame.on("select", function () {
        const selection = frame.state().get("selection").first().toJSON();

        if (!isValidImage(selection)) {
          // alert('The selected image is invalid (must be 1:1 and 300–1200px).');
          return;
        }

        // 正常设置 featured image
        wp.media.featuredImage.set(selection.id);
      });

      return frame;
    };

    // 阻止用户点击灰色图像
    $(document).on(
      "click",
      ".attachments .attachment.disabled-featured",
      function (e) {
        // e.preventDefault();
        // e.stopPropagation();
      }
    );
  }

  // if (typeof wp !== "undefined" && wp.media && $("#postimagediv").length) {
  //   initFeaturedImageValidation();
  // }
});
