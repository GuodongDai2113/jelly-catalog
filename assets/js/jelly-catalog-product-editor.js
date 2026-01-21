(function ($) {
  "use strict";

  /**
   * 产品编辑器
   * 负责将后台产品编辑页面的 metabox 整理成更友好的编辑体验
   */
  class JellyCatalogProductEditor {
    constructor() {
      // 仅在产品编辑页执行

      const $body = $("body");
      if (!$body.hasClass("post-type-product")) {
        return;
      }

      this.metaboxIds = [
        "postexcerpt",
        "postdivrich",
        "product_catdiv",
        "tagsdiv-product_tag",
        "acf-group_product_field",
        "product_faq_metabox",
        "product_attributes_metabox",
        "product_videourl_metabox",
        "rank_math_metabox",
      ];

      // 复用的媒体选择框实例
      this.productGalleryFrame = null;

      console.log("Jelly Catalog Editor Initialized");
      this.init();
    }

    /**
     * 核心初始化流程
     */
    init() {
      this.resetProductEditor();
      this.initCharacterCount();
      // this.initRepeater();
      this.initProductGallery();
    }

    /**
     * 重构 metabox 布局为标签页
     */
    resetProductEditor() {
      const titlediv = $("#poststuff #titlediv");

      if (!titlediv.length) return;

      const tabContainer = this.createTabContainer();
      titlediv.after(tabContainer);

      this.generateTabs(this.metaboxIds, tabContainer);
      this.appendElementorControls();
      this.bindTabEvents();

      this.moveTitleDescription();
    }
    moveTitleDescription() {
      const $titleDescription = $(".title-description").first();
      const $titleField = $("#title");

      if ($titleDescription.length && $titleField.length) {
        $titleField.after($titleDescription);
      }
    }
    /**
     * 创建标签页容器骨架
     */
    createTabContainer() {
      return $(`
        <div class="jc-tabs">
          <div class="nav-tab-wrapper"></div>
          <div class="tab-content"></div>
        </div>
      `);
    }

    /**
     * 根据 metabox 列表生成标签页内容
     */
    generateTabs(metaboxIds, tabContainer) {
      const navWrapper = tabContainer.find(".nav-tab-wrapper");
      const contentWrapper = tabContainer.find(".tab-content");

      metaboxIds.forEach((metaboxId, index) => {
        const metabox = $("#" + metaboxId);
        if (!metabox.length) return;

        const tabTitle =
          metaboxId === "postdivrich"
            ? jc_product_editor_data.postdivrich
            : metabox.find("h2.hndle").text() || metaboxId;

        const tabLink = $(`
          <a href="#" class="nav-tab ${
            index === 0 ? "nav-tab-active" : ""
          }" data-tab="${metaboxId}">
            ${tabTitle}
          </a>
        `);
        navWrapper.append(tabLink);

        const tabPane = $(`
          <div class="tab-pane ${
            index === 0 ? "active" : ""
          }" id="tab-${metaboxId}"></div>
        `);

        const metaboxContent =
          metaboxId === "postdivrich"
            ? metabox
            : metabox.find(".inside").children();

        const description = $(`#tab-panel-${metaboxId}_help .edit-description`);

        if (description.length && metaboxContent.length) {
          tabPane.append(description);
        }

        tabPane.append(metaboxContent);
        contentWrapper.append(tabPane);

        if (metaboxId !== "postdivrich") {
          metabox.remove();
        }
      });
    }

    /**
     * 嵌入 Elementor 切换按钮与编辑器
     */
    appendElementorControls() {
      const elementorEditor = $("#elementor-editor");
      const elementorSwitch = $("#elementor-switch-mode");
      if (!elementorEditor.length || !elementorSwitch.length) return;

      const content = $("#tab-postdivrich");
      content.append(elementorSwitch);
      content.append(elementorEditor);
    }

    /**
     * 标签页点击切换逻辑
     */
    bindTabEvents() {
      $(".jc-tabs").on("click", ".nav-tab", (e) => {
        e.preventDefault();
        const $tab = $(e.currentTarget);
        const tabId = $tab.data("tab");

        $tab.siblings(".nav-tab").removeClass("nav-tab-active");
        $tab.addClass("nav-tab-active");

        $(".tab-pane").removeClass("active");
        $("#tab-" + tabId).addClass("active");

        if (tabId === "postdivrich") {
          setTimeout(this.resizeEditorToolbar, 0);
        }
      });
    }

    /**
     * 调整富文本工具栏宽度
     */
    resizeEditorToolbar() {
      const editorContainer = $("#wp-content-editor-container");
      const editorTools = $("#wp-content-editor-tools");
      const mceToolbarGrp = $(".mce-toolbar-grp");

      if (!editorContainer.length || !editorTools.length) return;

      const width = editorContainer.width();
      editorTools.css("width", width);
      mceToolbarGrp.css("width", width);
    }

    /**
     * 初始化短描述字符统计
     */
    initCharacterCount() {
      this.bindTinyMceCountListener();
      this.bindTextareaCountListener();
      this.syncInitialCharacterCount();
    }

    /**
     * 监听 TinyMCE 内容变化
     */
    bindTinyMceCountListener() {
      $(document).on("tinymce-editor-init", () => {
        const editor =
          typeof tinymce !== "undefined" ? tinymce.get("excerpt") : null;
        if (!editor) return;

        editor.on("keyup paste cut", () => {
          this.updateCharacterCountDisplay(
            this.getExcerptLengthFromEditor(editor)
          );
        });
      });
    }

    /**
     * 监听纯文本域内容变化
     */
    bindTextareaCountListener() {
      $(document).on("keyup paste cut", "#excerpt", (e) => {
        this.updateCharacterCountDisplay($(e.currentTarget).val().length);
      });
    }

    /**
     * 页面加载后同步一次默认计数
     */
    syncInitialCharacterCount() {
      setTimeout(() => {
        const editor =
          typeof tinymce !== "undefined" ? tinymce.get("excerpt") : null;
        if (editor) {
          this.updateCharacterCountDisplay(
            this.getExcerptLengthFromEditor(editor)
          );
          return;
        }

        const $textarea = $("#excerpt");
        if ($textarea.length) {
          this.updateCharacterCountDisplay($textarea.val().length);
        }
      }, 1000);
    }

    /**
     * 读取 TinyMCE 纯文本长度
     */
    getExcerptLengthFromEditor(editor) {
      return editor.getContent({ format: "text" }).length;
    }

    /**
     * 更新计数标签文字与颜色
     */
    updateCharacterCountDisplay(count) {
      const $countElement = $("#excerpt-character-count");
      $countElement.text(count);

      if (count > 160) {
        $countElement.css("color", "#ff4d4f");
      } else if (count > 130) {
        $countElement.css("color", "#faad14");
      } else {
        $countElement.css("color", "#52c41a");
      }
    }

    /**
     * 初始化 repeater 相关逻辑
     */
    initRepeater() {
      this.prepareExistingRepeaters();
      this.bindRepeaterEvents();
    }

    /**
     * 为已有 repeater 添加操作按钮
     */
    prepareExistingRepeaters() {
      $(".jc-repeater-wrapper").each((_, wrapper) => {
        const $wrapper = $(wrapper);
        this.addDeleteButtons($wrapper);
        this.ensureAddButton($wrapper);
      });
    }

    /**
     * 绑定 repeater 的增删事件
     */
    bindRepeaterEvents() {
      $(document).on("click", ".repeater-item .remove-repeater", (e) => {
        e.preventDefault();
        $(e.currentTarget).closest(".repeater-item").remove();
      });

      $(document).on("click", ".repeater-add-new", (e) => {
        e.preventDefault();
        const $wrapper = $(e.currentTarget).closest(".jc-repeater-wrapper");
        this.addNewRepeaterItem($wrapper);
      });
    }

    /**
     * 为旧数据项补齐删除按钮
     */
    addDeleteButtons(repeaterWrapper) {
      repeaterWrapper.find(".repeater-item").each((_, item) => {
        const $item = $(item);
        if ($item.find(".remove-repeater").length) return;

        $item.append(`
          <button type="button" class="button remove-repeater" title="Delete item">
            <span class="dashicons dashicons-trash"></span>
          </button>
        `);
      });
    }

    /**
     * 确保容器内存在“新增”按钮
     */
    ensureAddButton(repeaterWrapper) {
      if (repeaterWrapper.find(".repeater-add-new").length) return;

      repeaterWrapper.append(`
        <div class="repeater-add-wrapper">
          <button type="button" class="button button-primary repeater-add-new">
            <span class="dashicons dashicons-plus"></span>
            Add New Item
          </button>
        </div>
      `);
    }

    /**
     * 新增一个 repeater 条目
     */
    addNewRepeaterItem(repeaterWrapper) {
      const key = repeaterWrapper.data("key");
      const itemCount = repeaterWrapper.find(".repeater-item").length;
      const $newItem = this.buildRepeaterItem(key, itemCount);

      if (!$newItem) return;

      repeaterWrapper.find(".repeater-add-wrapper").before($newItem);
    }

    /**
     * 构建指定类型的 repeater DOM
     */
    buildRepeaterItem(key, index) {
      const builders = {
        product_faqs: () => this.buildFaqItem(index),
        product_attributes: () => this.buildAttributeItem(index),
      };

      const builder = builders[key];
      if (!builder) return null;

      const $item = $(`
        <div class="repeater-item">
          <span class="item-title">${index}</span>
        </div>
      `);

      builder().appendTo($item);

      $item.append(`
        <button type="button" class="button remove-repeater" title="Delete item">
          <span class="dashicons dashicons-trash"></span>
        </button>
      `);

      return $item;
    }

    /**
     * 构建 FAQ 字段结构
     */
    buildFaqItem(index) {
      return $(`
        <div>
          <div class="repeater-item__key">
            <label for="product_faqs[${index}][name]">Question:</label>
            <input class="repeater-item__key-input" type="text" id="product_faqs[${index}][name]" name="product_faqs[${index}][name]" value="" />
          </div>
          <div class="repeater-item__value">
            <label for="product_faqs[${index}][value]">Answer:</label>
            <textarea class="repeater-item__value-input" id="product_faqs[${index}][value]" name="product_faqs[${index}][value]"></textarea>
          </div>
        </div>
      `);
    }

    /**
     * 构建属性输入结构
     */
    buildAttributeItem(index) {
      return $(`
        <div>
          <div class="repeater-item__key">
            <label for="product_attributes[${index}][name]">Name:</label>
            <input class="repeater-item__key-input" type="text" id="product_attributes[${index}][name]" name="product_attributes[${index}][name]" value="" />
          </div>
          <div class="repeater-item__value">
            <label for="product_attributes[${index}][value]">Value:</label>
            <input class="repeater-item__value-input" type="text" id="product_attributes[${index}][value]" name="product_attributes[${index}][value]" value="" />
          </div>
        </div>
      `);
    }

    /**
     * 初始化产品画廊排序与按钮
     */
    initProductGallery() {
      const container = $("#jc-gallery .product-images");
      if (!container.length) return;

      this.setupGallerySorting(container);
      this.bindGalleryEvents(container);
    }

    /**
     * 启用图片拖拽排序
     */
    setupGallerySorting(container) {
      container.sortable({
        items: "li.image",
        cursor: "move",
        scrollSensitivity: 40,
        forcePlaceholderSize: true,
        helper: "clone",
        opacity: 0.65,
        placeholder: "ui-sortable-placeholder",
        start: (_, ui) => {
          ui.placeholder.height(ui.item.height());
          ui.placeholder.width(ui.item.width());
        },
        stop: () => {
          container.find("li.image").removeAttr("style");
          this.updateGalleryImages();
        },
      });
    }

    /**
     * 绑定画廊增删逻辑
     */
    bindGalleryEvents(container) {
      container.on("click", "a.delete", (e) => {
        e.preventDefault();
        $(e.currentTarget).closest("li.image").remove();
        this.updateGalleryImages();
      });

      $(document).on("click", ".jc-add-image a", (e) => {
        e.preventDefault();
        this.openMediaFrame(e.currentTarget);
      });
    }

    /**
     * 打开媒体库并处理选中图片
     */
    openMediaFrame(button) {
      const $button = $(button);
      const deleteText = $button.data("delete") || "Delete image";

      if (this.productGalleryFrame) {
        this.productGalleryFrame.open();
        return;
      }

      this.productGalleryFrame = wp.media({
        title: $button.data("choose"),
        button: { text: $button.data("update") },
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

      this.productGalleryFrame.on("select", () => {
        const selection = this.productGalleryFrame.state().get("selection");

        selection.map((attachment) => {
          const data = attachment.toJSON();
          if (!data.id) return;

          $("#jc-gallery .product-images").append(`
            <li class="image" data-attachment_id="${data.id}">
              <img src="${data.sizes.thumbnail.url}" alt="" />
              <ul class="actions">
                <li><a href="#" class="delete" title="${deleteText}"></a></li>
              </ul>
            </li>
          `);
        });

        this.updateGalleryImages();
      });

      this.productGalleryFrame.open();
    }

    /**
     * 将当前画廊图片 ID 同步回隐藏字段
     */
    updateGalleryImages() {
      const ids = [];
      $("#jc-gallery .product-images li.image").each((_, item) => {
        const id = $(item).data("attachment_id");
        if (id) ids.push(id);
      });

      $("#product_image_gallery").val(ids.join(",")).trigger("change");
    }
  }

  $(document).ready(() => {
    window.JellyCatalogProductEditor = new JellyCatalogProductEditor();
  });
})(jQuery);
