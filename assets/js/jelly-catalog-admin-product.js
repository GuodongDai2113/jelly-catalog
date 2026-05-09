(function ($) {
  "use strict";

  /**
   * 产品编辑器
   * 负责将后台产品编辑页面的 metabox 整理成更友好的编辑体验
   */
  const EDITOR_IDS = {
    content: "content",
  };

  const SELECTORS = {
    body: "body",
    titleDiv: "#poststuff #titlediv",
    adminBar: "#wpadminbar",
    editorNavList: ".jc-product-editor__nav-list",
    editorNavButton: ".jc-product-editor__nav-button",
    editorNavSecondary: ".jc-product-editor__nav-secondary",
    loadingOverlay: ".jc-admin-loading-overlay",
    editorContent: ".jc-product-editor__content",
    sidebarContainer: "#postbox-container-1",
    sidebarSortables:
      "#postbox-container-1 #side-sortables, #postbox-container-1 .meta-box-sortables",
    submitBox: "#submitdiv",
    postbox: ".postbox",
    postboxHandle: ".postbox .hndle, .postbox .handlediv",
    postboxInside: ".postbox .inside",
    postboxHandleActions: ".postbox .handle-actions, .postbox .handlediv",
    metaBoxSortables: ".meta-box-sortables",
    gallery: "#jc-gallery .product-images",
    galleryItem: "li.image",
    galleryAddButton: ".jc-add-image a",
    galleryField: "#product_image_gallery",
    productDownload: "#jc-product-download",
    productDownloadField: "#product_file",
    productDownloadPreview: ".jc-product-download-preview",
    productDownloadName: ".jc-product-download-name",
    productDownloadMeta: ".jc-product-download-meta",
    productDownloadSelect: ".jc-product-download-select",
    productDownloadRemove: ".jc-product-download-remove",
    elementorEditor: "#elementor-editor",
    elementorSwitch: "#elementor-switch-mode",
  };

  const TINY_MCE_LAYOUT_DELAY = 50;
  const EDITOR_READY_DELAY = 420;
  const POST_LAYOUT_LOADING_DELAY = 500;
  const PRODUCT_SECTION_IDS = {
    summary: "summary",
    details: "details",
    extras: "extras",
    seo: "seo",
  };

  /**
   * 产品编辑页布局配置
   * 负责定义 summary/details/seo 连续分区中的内容顺序
   */
  const PRODUCT_EDITOR_LAYOUT = [
    {
      id: PRODUCT_SECTION_IDS.summary,
      navLabel: "Summary",
      sectionClass: "jc-product-section-summary",
      columns: [
        {
          className: "jc-product-pane__media",
          blocks: [
            { type: "metabox", id: "postimagediv" },
            { type: "metabox", id: "product-images" },
            { type: "metabox", id: "product_videourl_metabox" },
          ],
        },
        {
          className: "jc-product-pane__main",
          blocks: [
            { type: "title" },
            { type: "metabox", id: "product_sku_metabox" },
            { type: "metabox", id: "postexcerpt" },
            { type: "metabox", id: "product_attributes_metabox" },
            { type: "metabox", id: "product_download_metabox" },
          ],
        },
      ],
    },
    {
      id: PRODUCT_SECTION_IDS.details,
      navLabel: "Details",
      sectionClass: "jc-product-section-details",
      columns: [
        {
          className: "jc-product-pane__full",
          blocks: [
            {
              type: "metabox",
              id: "postdivrich",
              wrapper: "postbox",
              wrapperClass: "jc-layout-postbox",
              fallbackTitle: "Product Details",
            },
            { type: "metabox", id: "product_faq_metabox" },
          ],
        },
      ],
    },
    {
      id: PRODUCT_SECTION_IDS.extras,
      sectionClass: "jc-product-section-extras",
      columns: [
        {
          className: "jc-product-pane__full",
          blocks: [{ type: "metabox", id: "longtail_keywords_metabox" }],
        },
      ],
    },
    {
      id: PRODUCT_SECTION_IDS.seo,
      navLabel: "SEO",
      sectionClass: "jc-product-section-seo",
      columns: [
        {
          className: "jc-product-pane__full",
          blocks: [{ type: "metabox", id: "rank_math_metabox" }],
        },
      ],
    },
  ];

  class JellyCatalogProductEditor {
    constructor() {
      // 仅在产品编辑页执行

      if (!this.isProductEditScreen()) {
        return;
      }

      // 复用的媒体选择框实例
      this.productGalleryFrame = null;
      this.productDownloadFrame = null;
      this.postboxLockHandler = null;
      this.postboxObserver = null;
      this.sectionObserver = null;

      this.init();
    }

    isProductEditScreen() {
      return $(SELECTORS.body).hasClass("post-type-product");
    }

    /**
     * 核心初始化流程
     */
    init() {
      this.startLoadingState();
      this.resetProductEditor();
      this.lockPostboxes();
      this.initProductGallery();
      this.initProductDownload();
      this.fixTinyMCELayout(EDITOR_IDS.content);
      this.finalizeInitialLayout();
    }

    /**
     * 重构 metabox 布局为连续滑动分区
     */
    resetProductEditor() {
      const $titleDiv = $(SELECTORS.titleDiv);

      if (!$titleDiv.length) return;

      const $editorContainer = this.createEditorContainer();
      $titleDiv.after($editorContainer);

      const renderedSections = this.generateSections(
        PRODUCT_EDITOR_LAYOUT,
        $editorContainer
      );
      this.moveSidebarMetaboxes();
      this.appendElementorControls();
      this.initSectionNavigation(renderedSections, $editorContainer);
      this.moveNavigationMetabox($editorContainer);
    }

    /**
     * 创建编辑器布局容器骨架
     */
    createEditorContainer() {
      return $(`
        <div class="jc-product-editor">
          <aside class="jc-product-editor__nav" aria-label="Product section navigation">
            <div class="jc-product-editor__nav-card postbox">
              <div class="postbox-header jc-product-editor__nav-header">
                <div class="jc-product-editor__nav-heading">
                  <h2 class="hndle">Menu</h2>
                </div>
              </div>
              <div class="inside">
                <div class="jc-product-editor__nav-list"></div>
              </div>
            </div>
            <div class="jc-product-editor__nav-secondary"></div>
          </aside>
          <div class="jc-product-editor__content"></div>
        </div>
      `);
    }

    getSectionId(sectionId) {
      return "section-" + sectionId;
    }

    getSection(sectionId) {
      return $("#" + this.getSectionId(sectionId));
    }

    /**
     * 标记编辑页进入布局加载状态
     */
    startLoadingState() {
      const $body = $(SELECTORS.body);

      $body.addClass("jc-product-editor-is-loading");
      $body.addClass("is-jc-loading");
    }

    /**
     * 结束编辑页加载状态并展示最终布局
     */
    finishLoadingState() {
      const $body = $(SELECTORS.body);
      const $loadingOverlay = $(SELECTORS.loadingOverlay).first();

      $body.removeClass("jc-product-editor-is-loading");
      $body.removeClass("is-jc-loading");

      $loadingOverlay.addClass("is-leaving");

      window.setTimeout(() => {
        $loadingOverlay.remove();
      }, 220);
    }

    /**
     * 清理默认焦点，避免 TinyMCE 初始化时将页面拉到中部
     */
    blurEditorFocus() {
      const activeElement = document.activeElement;

      if (activeElement && typeof activeElement.blur === "function") {
        activeElement.blur();
      }

      if (!window.tinymce || !Array.isArray(window.tinymce.editors)) {
        return;
      }

      window.tinymce.editors.forEach((editor) => {
        if (!editor || typeof editor.getBody !== "function") {
          return;
        }

        const body = editor.getBody();

        if (body && typeof body.blur === "function") {
          body.blur();
        }
      });
    }

    /**
     * 将初始化后的视口强制拉回顶部
     */
    resetInitialViewport() {
      const resetDelays = [0, 60, 180, 320];

      resetDelays.forEach((delay) => {
        window.setTimeout(() => {
          this.blurEditorFocus();
          window.scrollTo(0, 0);
          document.documentElement.scrollTop = 0;
          document.body.scrollTop = 0;
        }, delay);
      });
    }

    /**
     * 在布局和编辑器刷新后完成首屏展示
     */
    finalizeInitialLayout() {
      window.setTimeout(() => {
        this.resetInitialViewport();
        window.requestAnimationFrame(() => {
          window.requestAnimationFrame(() => {
            window.setTimeout(() => {
              this.finishLoadingState();
            }, POST_LAYOUT_LOADING_DELAY);
          });
        });
      }, EDITOR_READY_DELAY);
    }

    /**
     * 获取分区内部承载内容的首列容器
     */
    getSectionPane(sectionId) {
      const $section = this.getSection(sectionId);
      if (!$section.length) return $();

      return $section.children().first();
    }

    /**
     * 按需创建一个空分区，用于承载运行时追加的扩展内容
     */
    ensureSectionPane(sectionId, sectionClass, insertBeforeSectionId) {
      const $existingPane = this.getSectionPane(sectionId);
      if ($existingPane.length) {
        return $existingPane;
      }

      const $contentWrapper = $(SELECTORS.editorContent).first();
      if (!$contentWrapper.length) return $();

      const $section = $(`
        <section class="jc-product-section ${sectionClass}" id="${this.getSectionId(
        sectionId
      )}">
          <div class="jc-product-pane__full"></div>
        </section>
      `);
      const $insertBeforeSection = insertBeforeSectionId
        ? this.getSection(insertBeforeSectionId)
        : $();

      if ($insertBeforeSection.length) {
        $insertBeforeSection.before($section);
      } else {
        $contentWrapper.append($section);
      }

      return $section.children().first();
    }

    /**
     * 获取布局块的 postbox 标题
     */
    getBlockTitle(block) {
      if (window.jc_product_i18n && window.jc_product_i18n[block.id]) {
        return window.jc_product_i18n[block.id];
      }

      return block.fallbackTitle || block.id;
    }

    /**
     * 将标题模块包装进布局块，便于参与网格排版
     */
    buildTitleBlock() {
      const $titleDiv = $(SELECTORS.titleDiv);
      if (!$titleDiv.length) return $();

      return $(
        '<div class="jc-product-block jc-product-block-title"></div>'
      ).append($titleDiv);
    }

    /**
     * 将 metabox 包装进布局块，但不改变其原有样式与交互
     */
    buildMetaboxBlock(block) {
      const metaboxId = block.id;
      const $metabox = $("#" + metaboxId);
      if (!$metabox.length) return $();

      const $block = $('<div class="jc-product-block"></div>');

      if (block.wrapper === "postbox") {
        const $wrapper = $(`
          <div class="postbox ${block.wrapperClass || ""}">
            <div class="postbox-header">
              <h2 class="hndle">${this.getBlockTitle(block)}</h2>
            </div>
            <div class="inside"></div>
          </div>
        `);

        $wrapper.find(".inside").append($metabox);
        return $block.append($wrapper);
      }

      return $block.append($metabox);
    }

    /**
     * 获取需要插入布局块中的 DOM 节点
     */
    getLayoutBlock(block) {
      if (block.type === "title") {
        return this.buildTitleBlock();
      }

      if (block.type === "metabox") {
        return this.buildMetaboxBlock(block);
      }

      return $();
    }

    /**
     * 构建单个分区中的列布局
     */
    buildSectionColumns(columns, $section) {
      let hasVisibleBlock = false;

      columns.forEach((column) => {
        const $column = $(`<div class="${column.className}"></div>`);

        column.blocks.forEach((block) => {
          const $block = this.getLayoutBlock(block);
          if ($block.length) {
            $column.append($block);
            hasVisibleBlock = true;
          }
        });

        if ($column.children().length) {
          $section.append($column);
        }
      });

      return hasVisibleBlock;
    }

    /**
     * 根据布局配置生成连续内容分区
     */
    generateSections(layoutSections, $editorContainer) {
      const $contentWrapper = $editorContainer.find(SELECTORS.editorContent);
      const renderedSections = [];

      layoutSections.forEach((section) => {
        const $section = $(`
          <section class="jc-product-section ${
            section.sectionClass
          }" id="${this.getSectionId(section.id)}"></section>
        `);

        const hasVisibleBlock = this.buildSectionColumns(
          section.columns,
          $section
        );

        if (!hasVisibleBlock) {
          return;
        }

        $contentWrapper.append($section);
        renderedSections.push({
          id: section.id,
          navLabel: section.navLabel || "",
        });
      });

      return renderedSections;
    }

    /**
     * 构建左侧分区导航按钮
     */
    buildSectionNavigationItem(section) {
      return $(`
        <button type="button" class="jc-product-editor__nav-button" data-target="${section.id}">
          ${section.navLabel}
        </button>
      `);
    }

    /**
     * 计算滚动定位时需要避开的顶部高度
     */
    getScrollOffset() {
      const adminBarHeight = $(SELECTORS.adminBar).outerHeight() || 0;

      return adminBarHeight + 24;
    }

    /**
     * 平滑滚动到指定分区
     */
    scrollToSection(sectionId) {
      const $section = this.getSection(sectionId);
      if (!$section.length) return;

      this.setActiveSectionNav(sectionId);

      const targetTop = $section.offset().top - this.getScrollOffset();

      window.scrollTo({
        top: Math.max(targetTop, 0),
        behavior: "smooth",
      });
    }

    /**
     * 根据当前可见分区更新左侧导航激活态
     */
    setActiveSectionNav(sectionId) {
      $(SELECTORS.editorNavButton).removeClass("is-active");
      $(SELECTORS.editorNavButton)
        .filter(`[data-target="${sectionId}"]`)
        .addClass("is-active");
    }

    /**
     * 初始化分区导航与滚动监听
     */
    initSectionNavigation(renderedSections, $editorContainer) {
      const navSections = renderedSections.filter(
        (section) => section.navLabel
      );
      const $navList = $editorContainer.find(SELECTORS.editorNavList);
      const $nav = $navList.closest(".jc-product-editor__nav");

      if (!$navList.length || !navSections.length) {
        $nav.hide();
        return;
      }

      $nav.show();
      $navList.empty();

      navSections.forEach((section) => {
        $navList.append(this.buildSectionNavigationItem(section));
      });

      $(document)
        .off("click.jcSectionNav", SELECTORS.editorNavButton)
        .on("click.jcSectionNav", SELECTORS.editorNavButton, (e) => {
          const sectionId = $(e.currentTarget).data("target");
          if (!sectionId) return;

          this.scrollToSection(sectionId);
        });

      this.setActiveSectionNav(navSections[0].id);
      this.observeSectionNavigation(navSections);
    }

    /**
     * 将产品前后跳转 metabox 移动到左侧菜单下方
     */
    moveNavigationMetabox($editorContainer) {
      const $navSecondary = $editorContainer.find(SELECTORS.editorNavSecondary);
      const $navigationMetabox = $("#product_navigation_metabox");

      if (!$navSecondary.length || !$navigationMetabox.length) {
        return;
      }

      $navSecondary.append($navigationMetabox);
    }

    /**
     * 使用 IntersectionObserver 同步导航高亮
     */
    observeSectionNavigation(navSections) {
      if (this.sectionObserver) {
        this.sectionObserver.disconnect();
      }

      if (!("IntersectionObserver" in window)) {
        return;
      }

      this.sectionObserver = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (!entry.isIntersecting) return;

            const sectionId = entry.target.id.replace("section-", "");
            this.setActiveSectionNav(sectionId);
          });
        },
        {
          root: null,
          rootMargin: `-${this.getScrollOffset()}px 0px -55% 0px`,
          threshold: 0.15,
        }
      );

      navSections.forEach((section) => {
        const sectionElement = document.getElementById(
          this.getSectionId(section.id)
        );
        if (!sectionElement) return;

        this.sectionObserver.observe(sectionElement);
      });
    }

    /**
     * 将指定 metabox 放回右侧原生侧栏
     */
    moveSidebarMetaboxes() {
      const $sidebarSortables = $(SELECTORS.sidebarSortables).first();
      const $sidebarContainer = $(SELECTORS.sidebarContainer).first();
      const $categoryMetabox = $("#product_catdiv");
      const $tagMetabox = $("#tagsdiv-product_tag");
      const $submitBox = $(SELECTORS.submitBox).first();

      if ($sidebarSortables.length) {
        if (
          $submitBox.length &&
          $submitBox.parent()[0] === $sidebarSortables[0]
        ) {
          if ($categoryMetabox.length) {
            $submitBox.after($categoryMetabox);
          }

          if ($tagMetabox.length) {
            if ($categoryMetabox.length) {
              $categoryMetabox.after($tagMetabox);
            } else {
              $submitBox.after($tagMetabox);
            }
          }

          return;
        }

        if ($categoryMetabox.length) {
          $sidebarSortables.append($categoryMetabox);
        }

        if ($tagMetabox.length) {
          $sidebarSortables.append($tagMetabox);
        }

        return;
      }

      if ($sidebarContainer.length) {
        if ($categoryMetabox.length) {
          $sidebarContainer.append($categoryMetabox);
        }

        if ($tagMetabox.length) {
          $sidebarContainer.append($tagMetabox);
        }
      }
    }

    /**
     * 嵌入 Elementor 切换按钮与编辑器
     */
    appendElementorControls() {
      const $elementorEditor = $(SELECTORS.elementorEditor);
      const $elementorSwitch = $(SELECTORS.elementorSwitch);
      if (!$elementorEditor.length || !$elementorSwitch.length) return;

      const $extrasPane = this.ensureSectionPane(
        PRODUCT_SECTION_IDS.extras,
        "jc-product-section-extras",
        PRODUCT_SECTION_IDS.seo
      );
      const $detailsPane = this.getSectionPane(PRODUCT_SECTION_IDS.details);
      const $editorMetabox = $("#postdivrich");
      const $longtailMetabox = $("#longtail_keywords_metabox");

      if ($extrasPane.length && $longtailMetabox.length) {
        $longtailMetabox.after($elementorSwitch, $elementorEditor);
        return;
      }

      if ($extrasPane.length) {
        $extrasPane.append($elementorSwitch, $elementorEditor);
        return;
      }

      if ($detailsPane.length && !$editorMetabox.length) {
        $detailsPane.append($elementorSwitch, $elementorEditor);
        return;
      }

      if ($editorMetabox.length) {
        $editorMetabox.after($elementorSwitch, $elementorEditor);
      }
    }

    /**
     * 禁用 WordPress 默认 metabox 的拖拽与折叠能力，固定编辑页布局
     */
    lockPostboxes() {
      $(SELECTORS.postbox).removeClass("closed");
      $(SELECTORS.postboxInside).show();

      $(SELECTORS.metaBoxSortables).each((_, container) => {
        const $container = $(container);

        if ($container.hasClass("ui-sortable")) {
          try {
            $container.sortable("destroy");
          } catch (e) {}
        }
      });

      $(document).off(".jcLockPostbox");
      $(document).on(
        "click.jcLockPostbox mousedown.jcLockPostbox dblclick.jcLockPostbox keydown.jcLockPostbox",
        SELECTORS.postboxHandle,
        (e) => {
          if (
            e.type === "keydown" &&
            e.key !== "Enter" &&
            e.key !== " " &&
            e.key !== "Spacebar"
          ) {
            return;
          }

          e.preventDefault();
          e.stopImmediatePropagation();
        }
      );

      if (this.postboxLockHandler) {
        document.removeEventListener("click", this.postboxLockHandler, true);
        document.removeEventListener(
          "mousedown",
          this.postboxLockHandler,
          true
        );
        document.removeEventListener("dblclick", this.postboxLockHandler, true);
      }

      this.postboxLockHandler = (e) => {
        const target = e.target;
        if (!(target instanceof Element)) return;

        if (!target.closest(SELECTORS.postboxHandle)) return;

        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
      };

      document.addEventListener("click", this.postboxLockHandler, true);
      document.addEventListener("mousedown", this.postboxLockHandler, true);
      document.addEventListener("dblclick", this.postboxLockHandler, true);

      if (this.postboxObserver) {
        this.postboxObserver.disconnect();
      }

      this.postboxObserver = new MutationObserver(() => {
        $(SELECTORS.postbox).removeClass("closed");
        $(SELECTORS.postboxInside).show();
      });

      $(SELECTORS.postbox).each((_, postbox) => {
        this.postboxObserver.observe(postbox, {
          attributes: true,
          attributeFilter: ["class"],
        });
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

    /**
     * 初始化产品下载文件选择。
     */
    initProductDownload() {
      const $container = this.getProductDownloadContainer();
      if (!$container.length) return;

      $(document).on("click", SELECTORS.productDownloadSelect, (e) => {
        e.preventDefault();
        this.openProductDownloadFrame(e.currentTarget);
      });

      $(document).on("click", SELECTORS.productDownloadRemove, (e) => {
        e.preventDefault();
        this.clearProductDownload();
      });
    }

    getProductDownloadContainer() {
      return $(SELECTORS.productDownload);
    }

    openProductDownloadFrame(button) {
      const $button = $(button);

      if (!window.wp || !wp.media) return;

      if (this.productDownloadFrame) {
        this.productDownloadFrame.open();
        return;
      }

      this.productDownloadFrame = wp.media({
        title: $button.data("choose") || "Select Product File",
        button: {
          text: $button.data("update") || "Use this file",
        },
        multiple: false,
      });

      this.productDownloadFrame.on("select", () => {
        const attachment = this.productDownloadFrame
          .state()
          .get("selection")
          .first();

        if (!attachment) return;

        this.updateProductDownload(attachment.toJSON());
      });

      this.productDownloadFrame.open();
    }

    updateProductDownload(attachment) {
      const $container = this.getProductDownloadContainer();
      if (!$container.length || !attachment || !attachment.id) return;

      const meta = [attachment.filesizeHumanReadable, attachment.mime]
        .filter(Boolean)
        .join(" / ");
      const fileName =
        attachment.filename || attachment.title || attachment.name || "";

      $container
        .find(SELECTORS.productDownloadField)
        .val(attachment.id)
        .trigger("change");
      $container.find(SELECTORS.productDownloadName).text(fileName);
      $container.find(SELECTORS.productDownloadMeta).text(meta);
      $container.find(SELECTORS.productDownloadPreview).removeClass("is-empty");
      $container.find(SELECTORS.productDownloadRemove).show();
    }

    clearProductDownload() {
      const $container = this.getProductDownloadContainer();
      if (!$container.length) return;

      const emptyText = $container.data("no-file") || "No file selected";

      $container.find(SELECTORS.productDownloadField).val("").trigger("change");
      $container.find(SELECTORS.productDownloadName).text(emptyText);
      $container.find(SELECTORS.productDownloadMeta).text("");
      $container.find(SELECTORS.productDownloadPreview).addClass("is-empty");
      $container.find(SELECTORS.productDownloadRemove).hide();
    }

    getGalleryContainer() {
      return $(SELECTORS.gallery);
    }

    /**
     * 将新图片插入到“新增图片”入口之前，保持入口始终位于末尾
     */
    insertGalleryImage($container, markup) {
      const $addTrigger = $container.find(".jc-add-image").first();

      if ($addTrigger.length) {
        $addTrigger.before(markup);
        return;
      }

      $container.append(markup);
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

          this.insertGalleryImage(
            $galleryContainer,
            `
            <li class="image" data-attachment_id="${data.id}">
              <img src="${data.sizes.thumbnail.url}" alt="" />
              <a href="#" class="actions delete" title="${deleteText}"></a>
            </li>
          `
          );
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
