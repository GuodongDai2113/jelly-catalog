(function ($) {
  "use strict";

  const BaseEditor = window.JellyCatalogAdminEditor?.BaseEditor;
  const SharedBaseEditor = BaseEditor || class {};

  const EDITOR_IDS = {
    content: "content",
  };

  const SELECTORS = {
    body: "body",
    titleDiv: "#poststuff #titlediv",
    poststuff: "#poststuff",
    editorContent: ".jc-product-editor__content",
    sidebarContainer: "#postbox-container-1",
    sidebarSortables:
      "#postbox-container-1 #side-sortables, #postbox-container-1 .meta-box-sortables",
    submitBox: "#submitdiv",
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

  const PRODUCT_SECTION_IDS = {
    summary: "summary",
    details: "details",
    extras: "extras",
    seo: "seo",
  };

  /**
   * 产品编辑器侧边栏进度定义。
   */
  const PRODUCT_PROGRESS_EDITORS = {
    excerpt: "excerpt",
    content: "content",
  };

  const PRODUCT_EDITOR_LAYOUT = [
    {
      id: PRODUCT_SECTION_IDS.summary,
      navLabelKey: "product_editor_summary",
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
      navLabelKey: "product_editor_details",
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
      navLabelKey: "product_editor_seo",
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

  class JellyCatalogProductEditor extends SharedBaseEditor {
    constructor() {
      super({
        bodySelector: SELECTORS.body,
        loadingBodyClass: "jc-product-editor-is-loading",
        loadingTextKey: "product_editor_loading",
        loadingText: "Loading editor...",
        navButtonSelector: ".jc-product-editor__nav-button",
        navButtonClass:
          "jc-product-editor__nav-button jc-editor-layout__nav-button",
        sectionSelector: ".jc-product-section",
        sectionIdPrefix: "section-",
        initialReadyDelay: 420,
        postLayoutLoadingDelay: 500,
      });

      this.$body = this.$body || $(SELECTORS.body);

      if (!BaseEditor || !this.isProductEditScreen()) {
        return;
      }

      this.productGalleryFrame = null;
      this.productDownloadFrame = null;
      this.postboxHelpTooltips = [];

      this.init();
    }

    isProductEditScreen() {
      return this.$body.hasClass("post-type-product");
    }

    init() {
      this.startLoadingState();
      this.resetProductEditor();
      this.lockPostboxes({
        scope: $("#poststuff"),
        destroySortable: true,
        sortableSelector: SELECTORS.metaBoxSortables,
      });
      this.initPostboxHelpTooltips();
      this.initProductGallery();
      this.initProductDownload();
      this.refreshTinyMCE({
        editorId: EDITOR_IDS.content,
      });
      this.finalizeInitialLayout();
    }

    resetProductEditor() {
      const $titleDiv = $(SELECTORS.titleDiv).first();

      if (!$titleDiv.length) {
        return;
      }

      const shell = this.createEditorShell({
        shellClass: "jc-product-editor jc-editor-layout",
        navClass: "jc-product-editor__nav jc-editor-layout__nav",
        navListClass: "jc-product-editor__nav-list jc-editor-layout__nav-list",
        navSecondaryClass:
          "jc-product-editor__nav-secondary jc-editor-layout__nav-secondary",
        contentClass: "jc-product-editor__content",
        navButtonClass:
          "jc-product-editor__nav-button jc-editor-layout__nav-button",
        navAriaLabel: this.getI18n(
          "product_editor_nav_aria",
          "Product section navigation"
        ),
        menuTitle: this.getI18n("product_editor_menu", "Menu"),
      });

      $titleDiv.after(shell.$shell);

      const renderedSections = this.generateSections(
        PRODUCT_EDITOR_LAYOUT,
        shell.$shell
      );

      this.moveSidebarMetaboxes();
      this.appendElementorControls();
      this.initSectionNavigation(renderedSections);
      this.moveNavigationMetabox();
      this.registerSectionProgressTracking();
    }

    getSectionPane(sectionId) {
      const $section = this.getSectionElement(sectionId);

      if (!$section.length) {
        return $();
      }

      return $section.children().first();
    }

    ensureSectionPane(sectionId, sectionClass, insertBeforeSectionId) {
      const $existingPane = this.getSectionPane(sectionId);

      if ($existingPane.length) {
        return $existingPane;
      }

      const $contentWrapper = $(SELECTORS.editorContent).first();

      if (!$contentWrapper.length) {
        return $();
      }

      const $section = $(`
        <section class="jc-product-section ${sectionClass}" id="${this.getSectionDomId(
        sectionId
      )}">
          <div class="jc-product-pane__full"></div>
        </section>
      `);
      const $insertBeforeSection = insertBeforeSectionId
        ? this.getSectionElement(insertBeforeSectionId)
        : $();

      if ($insertBeforeSection.length) {
        $insertBeforeSection.before($section);
      } else {
        $contentWrapper.append($section);
      }

      return $section.children().first();
    }

    getBlockTitle(block) {
      return this.getI18n(block.id, block.fallbackTitle || block.id);
    }

    buildTitleBlock() {
      const $titleDiv = $(SELECTORS.titleDiv);

      if (!$titleDiv.length) {
        return $();
      }

      $titleDiv.attr("data-jc-help-id", "title");

      return $(
        '<div class="jc-product-block jc-product-block-title"></div>'
      ).append($titleDiv);
    }

    buildMetaboxBlock(block) {
      const $metabox = $("#" + block.id);

      if (!$metabox.length) {
        return $();
      }

      const $block = $('<div class="jc-product-block"></div>');

      if (block.wrapper === "postbox") {
        const $wrapper = this.buildPostbox(
          this.getBlockTitle(block),
          block.wrapperClass || ""
        );

        $wrapper.attr("data-jc-help-id", block.id);
        $wrapper.find(".inside").append($metabox);
        return $block.append($wrapper);
      }

      return $block.append($metabox);
    }

    getLayoutBlock(block) {
      if (block.type === "title") {
        return this.buildTitleBlock();
      }

      if (block.type === "metabox") {
        return this.buildMetaboxBlock(block);
      }

      return $();
    }

    buildSectionColumns(columns, $section) {
      let hasVisibleBlock = false;

      columns.forEach((column) => {
        const $column = $(`<div class="${column.className}"></div>`);

        column.blocks.forEach((block) => {
          const $block = this.getLayoutBlock(block);

          if (!$block.length) {
            return;
          }

          $column.append($block);
          hasVisibleBlock = true;
        });

        if ($column.children().length) {
          $section.append($column);
        }
      });

      return hasVisibleBlock;
    }

    generateSections(layoutSections, $editorContainer) {
      const $contentWrapper = $editorContainer
        .find(SELECTORS.editorContent)
        .first();
      const renderedSections = [];

      layoutSections.forEach((section) => {
        const $section = $(`
          <section class="jc-product-section ${
            section.sectionClass
          }" id="${this.getSectionDomId(section.id)}"></section>
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
          navLabel: section.navLabelKey
            ? this.getI18n(section.navLabelKey, section.navLabel || "")
            : section.navLabel || "",
        });
      });

      return renderedSections;
    }

    moveNavigationMetabox() {
      const $navigationMetabox = $("#product_navigation_metabox");

      if (!this.$navSecondary.length || !$navigationMetabox.length) {
        return;
      }

      this.$navSecondary.append($navigationMetabox);
    }

    /**
     * 注册产品编辑器每个 section 的完成度规则。
     */
    registerSectionProgressTracking() {
      this.setSectionProgressDefinitions({
        [PRODUCT_SECTION_IDS.summary]: {
          editorIds: [PRODUCT_PROGRESS_EDITORS.excerpt],
          items: [
            {
              key: "title",
              isComplete: () => this.hasFieldValue("#title"),
            },
            {
              key: "excerpt",
              isComplete: () =>
                this.hasEditorContent(
                  PRODUCT_PROGRESS_EDITORS.excerpt,
                  "#excerpt"
                ),
            },
            {
              key: "attributes",
              isComplete: () =>
                this.hasCompletedRepeaterItems(
                  "#product_attributes_metabox",
                  ".repeater-item__key-input",
                  ".repeater-item__value-input"
                ),
            },
            {
              key: "categories",
              isComplete: () =>
                this.hasCheckedValue("#product_catdiv input[type='checkbox']"),
            },
            {
              key: "featuredImage",
              isComplete: () => this.hasImagePreview("#postimagediv"),
            },
          ],
        },
        [PRODUCT_SECTION_IDS.details]: {
          editorIds: [PRODUCT_PROGRESS_EDITORS.content],
          items: [
            {
              key: "description",
              isComplete: () =>
                this.hasEditorContent(
                  PRODUCT_PROGRESS_EDITORS.content,
                  "#content"
                ),
            },
            {
              key: "faq",
              isComplete: () =>
                this.hasCompletedRepeaterItems(
                  "#product_faq_metabox",
                  ".repeater-item__key-input",
                  ".repeater-item__value-input"
                ),
            },
          ],
        },
        [PRODUCT_SECTION_IDS.extras]: {
          items: [
            {
              key: "longtailKeywords",
              isComplete: () => this.hasFieldValue("#jc_base_keyword"),
            },
          ],
        },
        [PRODUCT_SECTION_IDS.seo]: {
          items: [
            {
              key: "seo",
              isComplete: () =>
                this.hasMeaningfulFieldValue("#rank_math_metabox"),
            },
          ],
        },
      });
    }

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

    appendElementorControls() {
      const $elementorEditor = $(SELECTORS.elementorEditor);
      const $elementorSwitch = $(SELECTORS.elementorSwitch);

      if (!$elementorEditor.length || !$elementorSwitch.length) {
        return;
      }

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

    initPostboxHelpTooltips() {
      const $poststuff = $(SELECTORS.poststuff).first();

      if (!$poststuff.length) {
        return;
      }

      this.destroyPostboxHelpTooltips();

      this.getHelpTargets().each((_, element) => {
        $(element).removeClass("jc-postbox-has-help");
      });

      this.getHelpPanels().each((_, panel) => {
        const $panel = $(panel);
        const sourceId = this.getHelpSourceIdFromPanel(panel.id);
        const helpContent = ($panel.html() || "").trim();

        if (!sourceId || !helpContent) {
          return;
        }

        const $target = this.findHelpTarget(sourceId);

        if (!$target.length) {
          return;
        }
        // TODO：暂时取消
        // this.attachPostboxHelpTooltip($target, helpContent);
      });
    }

    getHelpTargets() {
      return $(SELECTORS.poststuff)
        .find(".postbox, [data-jc-help-id='title']")
        .filter((_, element) => {
          const $element = $(element);
          return !!($element.attr("id") || $element.attr("data-jc-help-id"));
        });
    }

    getHelpPanels() {
      return $("[id^='tab-panel-'][id$='_help']");
    }

    getHelpSourceIdFromPanel(panelId) {
      if (!panelId || !panelId.startsWith("tab-panel-")) {
        return "";
      }

      return panelId.replace(/^tab-panel-/, "").replace(/_help$/, "");
    }

    findHelpTarget(sourceId) {
      const $poststuff = $(SELECTORS.poststuff).first();

      if (!$poststuff.length || !sourceId) {
        return $();
      }

      const $mappedTarget = $poststuff
        .find(`[data-jc-help-id="${sourceId}"]`)
        .first();

      if ($mappedTarget.length) {
        return $mappedTarget;
      }

      return $poststuff.find("#" + sourceId).first();
    }

    attachPostboxHelpTooltip($postbox, helpContent) {
      if (
        !$postbox.length ||
        !helpContent ||
        !window.jellyCore ||
        typeof window.jellyCore.Tooltip !== "function"
      ) {
        return;
      }

      const tooltip = new window.jellyCore.Tooltip({
        target: $postbox[0],
        content: helpContent,
        placement: "right",
      });

      this.postboxHelpTooltips.push(tooltip);
      $postbox.addClass("jc-postbox-has-help");
    }

    destroyPostboxHelpTooltips() {
      if (!Array.isArray(this.postboxHelpTooltips)) {
        this.postboxHelpTooltips = [];
        return;
      }

      this.postboxHelpTooltips.forEach((tooltip) => {
        if (tooltip && typeof tooltip.destroy === "function") {
          tooltip.destroy();
        }
      });

      this.postboxHelpTooltips = [];
    }

    initProductDownload() {
      const $container = this.getProductDownloadContainer();

      if (!$container.length) {
        return;
      }

      $(document).on("click", SELECTORS.productDownloadSelect, (event) => {
        event.preventDefault();
        this.openProductDownloadFrame(event.currentTarget);
      });

      $(document).on("click", SELECTORS.productDownloadRemove, (event) => {
        event.preventDefault();
        this.clearProductDownload();
      });
    }

    getProductDownloadContainer() {
      return $(SELECTORS.productDownload);
    }

    openProductDownloadFrame(button) {
      const $button = $(button);

      if (!window.wp || !wp.media) {
        return;
      }

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

        if (!attachment) {
          return;
        }

        this.updateProductDownload(attachment.toJSON());
      });

      this.productDownloadFrame.open();
    }

    updateProductDownload(attachment) {
      const $container = this.getProductDownloadContainer();

      if (!$container.length || !attachment?.id) {
        return;
      }

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

      if (!$container.length) {
        return;
      }

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

    insertGalleryImage($container, markup) {
      const $addTrigger = $container.find(".jc-add-image").first();

      if ($addTrigger.length) {
        $addTrigger.before(markup);
        return;
      }

      $container.append(markup);
    }

    initProductGallery() {
      const $container = this.getGalleryContainer();

      if (!$container.length) {
        return;
      }

      this.setupGallerySorting($container);
      this.bindGalleryEvents($container);
    }

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

    bindGalleryEvents($container) {
      $container.on("click", "a.delete", (event) => {
        event.preventDefault();
        $(event.currentTarget).closest(SELECTORS.galleryItem).remove();
        this.updateGalleryImages();
      });

      $(document).on("click", SELECTORS.galleryAddButton, (event) => {
        event.preventDefault();
        this.openMediaFrame(event.currentTarget);
      });
    }

    openMediaFrame(button) {
      const $button = $(button);
      const deleteText = $button.data("delete") || "Delete image";

      if (this.productGalleryFrame) {
        this.productGalleryFrame.open();
        return;
      }

      if (!window.wp || !wp.media) {
        return;
      }

      const $galleryContainer = this.getGalleryContainer();

      this.productGalleryFrame = wp.media({
        title: $button.data("choose"),
        button: {
          text: $button.data("update"),
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
        multiple: true,
      });

      this.productGalleryFrame.on("select", () => {
        const selection = this.productGalleryFrame.state().get("selection");

        selection.each((attachment) => {
          const data = attachment.toJSON();

          if (!data.id) {
            return;
          }

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

    updateGalleryImages() {
      const ids = [];

      this.getGalleryContainer()
        .find(SELECTORS.galleryItem)
        .each((_, item) => {
          const id = $(item).data("attachment_id");

          if (id) {
            ids.push(id);
          }
        });

      $(SELECTORS.galleryField).val(ids.join(",")).trigger("change");
    }
  }

  $(document).ready(() => {
    window.JellyCatalogProductEditor = new JellyCatalogProductEditor();
  });
})(jQuery);
