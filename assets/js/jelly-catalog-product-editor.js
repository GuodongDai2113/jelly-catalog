(function ($) {
  "use strict";

  /**
   * 产品编辑器
   * 负责将后台产品编辑页面的 metabox 整理成更友好的编辑体验
   */
  const METABOX_IDS = [
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

  const EDITOR_IDS = {
    content: "content",
    excerpt: "excerpt",
  };

  const SELECTORS = {
    body: "body",
    titleDiv: "#poststuff #titlediv",
    titleDescription: ".title-description",
    titleField: "#title",
    tabs: ".jc-tabs",
    tabPane: ".tab-pane",
    tabNav: ".nav-tab-wrapper",
    tabContent: ".tab-content",
    excerpt: "#excerpt",
    excerptCount: "#excerpt-character-count",
    gallery: "#jc-gallery .product-images",
    galleryItem: "li.image",
    galleryAddButton: ".jc-add-image a",
    galleryField: "#product_image_gallery",
    elementorEditor: "#elementor-editor",
    elementorSwitch: "#elementor-switch-mode",
  };

  const CHARACTER_COUNT_LIMITS = {
    warning: 130,
    danger: 160,
  };

  const CHARACTER_COUNT_COLORS = {
    ok: "#52c41a",
    warning: "#faad14",
    danger: "#ff4d4f",
  };

  const CHARACTER_COUNT_SYNC_DELAY = 1000;
  const TINY_MCE_LAYOUT_DELAY = 50;

  class JellyCatalogProductEditor {
    constructor() {
      // 仅在产品编辑页执行

      if (!this.isProductEditScreen()) {
        return;
      }

      this.metaboxIds = METABOX_IDS;

      // 复用的媒体选择框实例
      this.productGalleryFrame = null;

      console.log("Jelly Catalog Editor Initialized");
      this.init();
    }

    isProductEditScreen() {
      return $(SELECTORS.body).hasClass("post-type-product");
    }

    /**
     * 核心初始化流程
     */
    init() {
      this.resetProductEditor();
      this.initCharacterCount();
      this.initProductGallery();
    }

    /**
     * 重构 metabox 布局为标签页
     */
    resetProductEditor() {
      const $titleDiv = $(SELECTORS.titleDiv);

      if (!$titleDiv.length) return;

      const $tabContainer = this.createTabContainer();
      $titleDiv.after($tabContainer);

      this.generateTabs(this.metaboxIds, $tabContainer);
      this.appendElementorControls();
      this.bindTabEvents();

      this.moveTitleDescription();
    }
    moveTitleDescription() {
      const $titleDescription = $(SELECTORS.titleDescription).first();
      const $titleField = $(SELECTORS.titleField);

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

    getTabPaneId(metaboxId) {
      return "tab-" + metaboxId;
    }

    getTabPane(metaboxId) {
      return $("#" + this.getTabPaneId(metaboxId));
    }

    getMetaboxTitle(metaboxId, $metabox) {
      if (metaboxId === "postdivrich") {
        if (
          window.jc_product_editor_data &&
          window.jc_product_editor_data.postdivrich
        ) {
          return window.jc_product_editor_data.postdivrich;
        }

        return "Content";
      }

      const title = $metabox.find("h2.hndle").text();
      return title || metaboxId;
    }

    getMetaboxContent(metaboxId, $metabox) {
      if (metaboxId === "postdivrich") {
        return $metabox;
      }

      return $metabox.find(".inside").children();
    }

    /**
     * 根据 metabox 列表生成标签页内容
     */
    generateTabs(metaboxIds, $tabContainer) {
      const $navWrapper = $tabContainer.find(SELECTORS.tabNav);
      const $contentWrapper = $tabContainer.find(SELECTORS.tabContent);
      let hasActiveTab = false;

      metaboxIds.forEach((metaboxId) => {
        const $metabox = $("#" + metaboxId);
        if (!$metabox.length) return;

        const isActive = !hasActiveTab;
        hasActiveTab = true;

        const tabTitle = this.getMetaboxTitle(metaboxId, $metabox);
        const $tabLink = $(`
          <a href="#" class="nav-tab ${
            isActive ? "nav-tab-active" : ""
          }" data-tab="${metaboxId}">
            ${tabTitle}
          </a>
        `);
        $navWrapper.append($tabLink);

        const $tabPane = $(`
          <div class="tab-pane ${
            isActive ? "active" : ""
          }" id="${this.getTabPaneId(metaboxId)}"></div>
        `);

        const $metaboxContent = this.getMetaboxContent(metaboxId, $metabox);
        const $description = $(
          `#tab-panel-${metaboxId}_help .edit-description`
        );

        if ($description.length && $metaboxContent.length) {
          $tabPane.append($description);
        }

        $tabPane.append($metaboxContent);
        $contentWrapper.append($tabPane);

        if (metaboxId !== "postdivrich") {
          $metabox.remove();
        }
      });
    }

    /**
     * 嵌入 Elementor 切换按钮与编辑器
     */
    appendElementorControls() {
      const $elementorEditor = $(SELECTORS.elementorEditor);
      const $elementorSwitch = $(SELECTORS.elementorSwitch);
      if (!$elementorEditor.length || !$elementorSwitch.length) return;

      const $content = this.getTabPane("postdivrich");
      if (!$content.length) return;

      $content.append($elementorSwitch, $elementorEditor);
    }

    /**
     * 标签页点击切换逻辑
     */
    bindTabEvents() {
      const $tabs = $(SELECTORS.tabs);
      if (!$tabs.length) return;

      $tabs.on("click", ".nav-tab", (e) => {
        e.preventDefault();
        const $tab = $(e.currentTarget);
        const tabId = $tab.data("tab");

        $tabs.find(".nav-tab").removeClass("nav-tab-active");
        $tab.addClass("nav-tab-active");

        $tabs.find(SELECTORS.tabPane).removeClass("active");
        this.getTabPane(tabId).addClass("active");

        if (tabId === "postdivrich") {
          // 重置 TinyMCE 编辑器的大小调整功能
          this.fixTinyMCELayout(EDITOR_IDS.content);
        }
      });
    }

    refreshTinyMCEUI(editor) {
      if (!editor) return;

      try {
        editor.execCommand("mceRepaint");
      } catch (e) {}
      try {
        editor.execCommand("mceResize");
      } catch (e) {}
    }

    fixTinyMCELayout(editorId) {
      const editor = window.tinymce && tinymce.get(editorId);
      if (!editor) return;

      setTimeout(() => {
        this.refreshTinyMCEUI(editor);
        $(window).trigger("resize");
      }, TINY_MCE_LAYOUT_DELAY);
    }

    /**
     * 初始化短描述字符统计
     */
    initCharacterCount() {
      this.bindTinyMceCountListener();
      this.bindTextareaCountListener();
      this.syncInitialCharacterCount();
    }

    getExcerptEditor() {
      if (typeof tinymce === "undefined") return null;
      return tinymce.get(EDITOR_IDS.excerpt);
    }

    /**
     * 监听 TinyMCE 内容变化
     */
    bindTinyMceCountListener() {
      $(document).on("tinymce-editor-init", () => {
        const editor = this.getExcerptEditor();
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
      $(document).on("keyup paste cut", SELECTORS.excerpt, (e) => {
        this.updateCharacterCountDisplay($(e.currentTarget).val().length);
      });
    }

    /**
     * 页面加载后同步一次默认计数
     */
    syncInitialCharacterCount() {
      setTimeout(() => {
        const editor = this.getExcerptEditor();
        if (editor) {
          this.updateCharacterCountDisplay(
            this.getExcerptLengthFromEditor(editor)
          );
          return;
        }

        const $textarea = $(SELECTORS.excerpt);
        if ($textarea.length) {
          this.updateCharacterCountDisplay($textarea.val().length);
        }
      }, CHARACTER_COUNT_SYNC_DELAY);
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
      const $countElement = $(SELECTORS.excerptCount);
      $countElement
        .text(count)
        .css("color", this.getCharacterCountColor(count));
    }

    getCharacterCountColor(count) {
      if (count > CHARACTER_COUNT_LIMITS.danger) {
        return CHARACTER_COUNT_COLORS.danger;
      }
      if (count > CHARACTER_COUNT_LIMITS.warning) {
        return CHARACTER_COUNT_COLORS.warning;
      }
      return CHARACTER_COUNT_COLORS.ok;
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

    buildRepeaterRemoveButton() {
      return `
        <button type="button" class="button remove-repeater" title="Delete item">
          <span class="dashicons dashicons-trash"></span>
        </button>
      `;
    }

    /**
     * 为旧数据项补齐删除按钮
     */
    addDeleteButtons(repeaterWrapper) {
      repeaterWrapper.find(".repeater-item").each((_, item) => {
        const $item = $(item);
        if ($item.find(".remove-repeater").length) return;

        $item.append(this.buildRepeaterRemoveButton());
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

      $item.append(this.buildRepeaterRemoveButton());

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

    getGalleryContainer() {
      return $(SELECTORS.gallery);
    }

    /**
     * 初始化产品画廊排序与按钮
     */
    initProductGallery() {
      const $container = this.getGalleryContainer();
      if (!$container.length) return;

      this.setupGallerySorting($container);
      this.bindGalleryEvents($container);
    }

    /**
     * 启用图片拖拽排序
     */
    setupGallerySorting($container) {
      $container.sortable({
        items: SELECTORS.galleryItem,
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
          $container.find(SELECTORS.galleryItem).removeAttr("style");
          this.updateGalleryImages();
        },
      });
    }

    /**
     * 绑定画廊增删逻辑
     */
    bindGalleryEvents($container) {
      $container.on("click", "a.delete", (e) => {
        e.preventDefault();
        $(e.currentTarget).closest(SELECTORS.galleryItem).remove();
        this.updateGalleryImages();
      });

      $(document).on("click", SELECTORS.galleryAddButton, (e) => {
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

      if (!window.wp || !wp.media) return;

      const $galleryContainer = this.getGalleryContainer();

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

        selection.each((attachment) => {
          const data = attachment.toJSON();
          if (!data.id) return;

          $galleryContainer.append(`
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
      this.getGalleryContainer()
        .find(SELECTORS.galleryItem)
        .each((_, item) => {
          const id = $(item).data("attachment_id");
          if (id) ids.push(id);
        });

      $(SELECTORS.galleryField).val(ids.join(",")).trigger("change");
    }
  }

  $(document).ready(() => {
    window.JellyCatalogProductEditor = new JellyCatalogProductEditor();
  });
})(jQuery);
