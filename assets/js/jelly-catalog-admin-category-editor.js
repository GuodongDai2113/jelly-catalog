(function ($) {
  "use strict";

  const BaseEditor = window.JellyCatalogAdminEditor?.BaseEditor;
  const SharedBaseEditor = BaseEditor || class {};

  const SELECTORS = {
    body: "body",
    form: "#edittag",
    formTable: ".form-table",
    submitActions: ".submit, .edit-tag-actions",
    shell: ".jc-term-editor",
    navList: ".jc-term-editor__nav-list",
    navButton: ".jc-term-editor__nav-button",
    aside: ".jc-term-editor__aside",
    content: ".jc-term-editor__content",
  };

  const SECTION_IDS = {
    overview: "overview",
    content: "content",
    faq: "faq",
    seo: "seo",
    additional: "additional",
  };

  /**
   * 分类描述字段的兼容选择器，适配默认结构和 Rank Math 改写结构。
   */
  const TERM_DESCRIPTION_ROW_SELECTORS = [
    ".term-description-wrap",
    ".rank-math-term-description-wrap",
    "#description",
  ];

  /**
   * 分类编辑器中需要参与进度判断的富文本编辑器。
   */
  const CATEGORY_PROGRESS_EDITORS = {
    whyChoose: "category_why_choose",
    advantages: "category_advantages",
  };

  class JellyCatalogCategoryManager {
    constructor() {
      this.$body = $(SELECTORS.body);

      if (!this.isProductCategoryScreen()) {
        return;
      }

      this.categoryListImageFrame = null;
      this.activeCategoryImageCell = null;

      this.initProductCategoryImageModule();
      this.initProductCategoryDescriptionModule();
      this.initProductCategoryThumbnailModule();
      this.initProductCategoryBannerModule();
    }

    isProductCategoryScreen() {
      return this.$body.hasClass("taxonomy-product_cat");
    }

    isCategoryListScreen() {
      return (
        this.isProductCategoryScreen() && this.$body.hasClass("edit-tags-php")
      );
    }

    isCategoryEditScreen() {
      const taxonomyValue =
        $(SELECTORS.form).find('input[name="taxonomy"]').val() ||
        new URLSearchParams(window.location.search).get("taxonomy") ||
        "";

      return (
        this.isProductCategoryScreen() &&
        this.$body.hasClass("term-php") &&
        taxonomyValue === "product_cat"
      );
    }

    showNotice(message, type = "info") {
      jellyCore.showMessage(message, type);
    }

    showBlockLoading(target, text) {
      return jellyCore.showLoading({
        target,
        text,
      });
    }

    getCategoryIdFromCell(cell) {
      return (cell.closest("tr").attr("id") || "").replace("tag-", "");
    }

    renderCategoryListImageMarkup(imageUrl) {
      if (!imageUrl) {
        return `
          <div class="jc-thumbnail-wrap">
            <span class="dashicons dashicons-format-image"></span>
          </div>
        `;
      }

      return `
        <div class="jc-thumbnail-wrap">
          <img src="${imageUrl}" alt="Thumbnail" class="wp-post-image" height="48" width="48" />
        </div>
      `;
    }

    updateCategoryListImagePreview(cell, attachment) {
      const imageUrl =
        attachment?.sizes?.thumbnail?.url || attachment?.url || "";

      cell.html(this.renderCategoryListImageMarkup(imageUrl));
    }

    initProductCategoryImageModule() {
      if (!this.isCategoryListScreen()) {
        return;
      }

      $(document).on("click", ".column-thumb, .column-jc-thumb", (event) => {
        event.preventDefault();

        if (!window.wp || !wp.media) {
          this.showNotice("Media library is unavailable.", "error");
          return;
        }

        this.activeCategoryImageCell = $(event.currentTarget);

        if (!this.categoryListImageFrame) {
          this.categoryListImageFrame = wp.media({
            multiple: false,
          });

          this.categoryListImageFrame.on("select", () => {
            this.handleCategoryListImageSelection();
          });
        }

        this.categoryListImageFrame.open();
      });
    }

    handleCategoryListImageSelection() {
      const cell = this.activeCategoryImageCell;
      const selection = this.categoryListImageFrame
        ?.state()
        .get("selection")
        .first();

      if (!cell || !cell.length || !selection) {
        return;
      }

      const attachment = selection.toJSON();
      const categoryId = this.getCategoryIdFromCell(cell);
      const loader = this.showBlockLoading(cell[0], "Saving category image...");

      if (!categoryId || !attachment?.id) {
        loader.close();
        this.showNotice(
          "Unable to determine the selected category image.",
          "error",
        );
        return;
      }

      $.ajax({
        url: jc_ajax.ajax_url,
        type: "POST",
        data: {
          action: "update_product_category_image",
          category_id: categoryId,
          image_id: attachment.id,
          nonce: jc_ajax.nonce,
        },
        success: (response) => {
          loader.close();

          if (!response?.success) {
            this.showNotice(
              response?.data || "Failed to update category image.",
              "error",
            );
            return;
          }

          this.updateCategoryListImagePreview(cell, attachment);
          this.showNotice(
            response?.data || "Category image updated successfully.",
            "success",
          );
        },
        error: () => {
          loader.close();
          this.showNotice("Network error. Please try again.", "error");
        },
      });
    }

    initProductCategoryDescriptionModule() {
      if (!this.isCategoryListScreen()) {
        return;
      }

      $(".wp-list-table").on("dblclick", ".column-description", (event) => {
        const cell = $(event.currentTarget);
        const source = cell.html();
        const description = cell
          .clone()
          .find('.screen-reader-text, [aria-hidden="true"]')
          .remove()
          .end()
          .text()
          .trim();
        const termId = this.getCategoryIdFromCell(cell);

        if (cell.find("textarea").length > 0) {
          return;
        }

        const editor = $('<div class="jc-description-editor"></div>');
        const textarea = $(
          '<textarea class="description-textarea" rows="4" cols="40"></textarea>',
        );
        const actions = $('<div class="description-actions"></div>');
        const saveBtn = $(".inline-edit-save .save", $(".inline-edit-row")).clone();
        const cancelBtn = $(
          ".inline-edit-save .cancel",
          $(".inline-edit-row"),
        ).clone();

        saveBtn.on("click", (clickEvent) => {
          clickEvent.preventDefault();

          const updatedDescription = textarea.val();
          const loader = this.showBlockLoading(
            editor[0],
            "Saving category description...",
          );

          $.ajax({
            url: jc_ajax.ajax_url,
            type: "POST",
            data: {
              action: "update_product_category_description",
              term_id: termId,
              description: updatedDescription,
              nonce: jc_ajax.nonce,
            },
            success: (response) => {
              loader.close();

              if (response?.success) {
                cell.html("<p>" + updatedDescription + "</p>");
                this.showNotice(
                  response.data || "Description updated successfully.",
                  "success",
                );
                return;
              }

              this.showNotice(
                response?.data || "Failed to update description.",
                "error",
              );
            },
            error: () => {
              loader.close();
              this.showNotice("Network error. Please try again.", "error");
            },
          });
        });

        cancelBtn.on("click", (clickEvent) => {
          clickEvent.preventDefault();
          cell.html(source);
        });

        textarea.val(description);
        saveBtn.attr("data-term-id", termId);
        editor.append(textarea, actions);
        actions.append(cancelBtn, saveBtn);
        cell.html(editor);
        textarea.focus();
      });
    }

    initProductCategoryThumbnailModule() {
      if (!this.isProductCategoryScreen()) {
        return;
      }

      $(document).on("click", "#thumbnail_id_button", (event) => {
        event.preventDefault();
        this.openMediaUploader("thumbnail");
      });

      $(document).on(
        "click",
        "#thumbnail_id_container .button.select-thumbnail",
        (event) => {
          event.preventDefault();
          const container = $(event.currentTarget).closest(
            "#thumbnail_id_container",
          );
          this.openMediaUploader("thumbnail", container);
        },
      );

      $(document).on(
        "click",
        "#thumbnail_id_container .button.remove-thumbnail, #remove-thumbnail",
        (event) => {
          event.preventDefault();
          const container = $(event.currentTarget).closest(
            "#thumbnail_id_container",
          );

          if (container.length) {
            container.find(".thumbnail-preview").hide();
            container.find("#thumbnail_id").val("").trigger("change");
            container.find(".button.remove-thumbnail").hide();
            container.find(".button.select-thumbnail").show();
            return;
          }

          $("#thumbnail_id").val("").trigger("change");
          $(".thumbnail-preview").hide();
          $(".thumbnail-preview img").attr("src", "");
          $(".remove-thumbnail").hide();
        },
      );
    }

    initProductCategoryBannerModule() {
      if (!this.isProductCategoryScreen()) {
        return;
      }

      $(document).on("click", "#banner_id_button", (event) => {
        event.preventDefault();
        this.openMediaUploader("banner");
      });

      $(document).on(
        "click",
        "#banner_id_container .button.select-banner",
        (event) => {
          event.preventDefault();
          const container = $(event.currentTarget).closest("#banner_id_container");
          this.openMediaUploader("banner", container);
        },
      );

      $(document).on(
        "click",
        "#banner_id_container .button.remove-banner, #remove-banner",
        (event) => {
          event.preventDefault();
          const container = $(event.currentTarget).closest("#banner_id_container");

          if (container.length) {
            container.find(".banner-preview").hide();
            container.find("#banner_id").val("").trigger("change");
            container.find(".button.remove-banner").hide();
            container.find(".button.select-banner").show();
            return;
          }

          $("#banner_id").val("").trigger("change");
          $(".banner-preview").hide();
          $(".banner-preview img").attr("src", "");
          $(".remove-banner").hide();
        },
      );
    }

    openMediaUploader(type, container = null) {
      if (!window.wp || !wp.media) {
        this.showNotice("Media library is unavailable.", "error");
        return;
      }

      const isEditPage = container !== null;
      const frame = wp.media({
        multiple: false,
      });

      frame.on("select", () => {
        const attachment = frame.state().get("selection").first().toJSON();

        if (isEditPage) {
          const inputField = container.find(`.${type}-id-input`);
          const preview = container.find(`.${type}-preview`);
          const img = preview.find("img");
          const removeBtn = container.find(`.button.remove-${type}`);
          const selectBtn = container.find(`.button.select-${type}`);

          inputField.val(attachment.id).trigger("change");
          img.attr("src", attachment.url);
          preview.show();
          removeBtn.show();
          selectBtn.hide();
          return;
        }

        const fieldId = type === "thumbnail" ? "#thumbnail_id" : "#banner_id";
        const previewClass =
          type === "thumbnail" ? ".thumbnail-preview" : ".banner-preview";
        const removeClass =
          type === "thumbnail" ? ".remove-thumbnail" : ".remove-banner";

        $(fieldId).val(attachment.id).trigger("change");
        $(previewClass).show();
        $(`${previewClass} img`).attr("src", attachment.url);
        $(removeClass).show();
      });

      frame.open();
    }
  }

  class JellyCatalogCategoryEditorLayout extends SharedBaseEditor {
    constructor() {
      super({
        bodySelector: SELECTORS.body,
        loadingBodyClass: "jc-term-editor-is-loading",
        loadingTextKey: "category_editor_loading",
        loadingText: "Organizing category editor...",
        navButtonSelector: SELECTORS.navButton,
        navButtonClass:
          "jc-term-editor__nav-button jc-editor-layout__nav-button",
        sectionSelector: ".jc-term-editor__section",
        sectionIdPrefix: "jc-term-editor-section-",
        initialReadyDelay: 320,
        postLayoutLoadingDelay: 420,
      });

      this.$body = this.$body || $(SELECTORS.body);
      this.$form = $(SELECTORS.form).first();
      this.$formTable = this.getPrimaryFormTable();
      this.pendingObserver = null;
      this.$aside = $();

      if (
        !BaseEditor ||
        !this.$body.length ||
        !this.$form.length ||
        !this.isProductCategoryScreen() ||
        !this.$formTable.length
      ) {
        return;
      }

      if (this.$form.data("jcCategoryEditorReady")) {
        return;
      }

      this.$form.data("jcCategoryEditorReady", true);
      window.jcCategoryEditorLayoutState = "ready";

      this.init();
    }

    getPrimaryFormTable() {
      const $directTable = this.$form.children(SELECTORS.formTable).first();

      if ($directTable.length) {
        return $directTable;
      }

      return this.$form.find(SELECTORS.formTable).first();
    }

    isProductCategoryScreen() {
      const taxonomyValue =
        this.$form.find('input[name="taxonomy"]').val() ||
        new URLSearchParams(window.location.search).get("taxonomy") ||
        "";

      return (
        this.$body.hasClass("taxonomy-product_cat") &&
        this.$body.hasClass("term-php") &&
        taxonomyValue === "product_cat"
      );
    }

    getI18nValue(key, fallback) {
      return this.getI18n(key, fallback);
    }

    init() {
      this.startLoadingState();
      this.reorderTermDescriptionWrap();
      this.buildLayout();
      this.lockPostboxes({
        scope: this.$shell,
      });
      this.decorateControls();
      this.fixEditorLayout();
      this.finalizeInitialLayout(() => {
        this.collectDeferredFields();
        this.lockPostboxes({
          scope: this.$shell,
        });
        this.fixEditorLayout();
      });
    }

    buildLayout() {
      const shell = this.createEditorShell({
        shellClass: "jc-term-editor jc-editor-layout",
        navClass: "jc-term-editor__nav jc-editor-layout__nav",
        navListClass: "jc-term-editor__nav-list jc-editor-layout__nav-list",
        navSecondaryClass:
          "jc-term-editor__aside jc-editor-layout__nav-secondary",
        contentClass: "jc-term-editor__content",
        navButtonClass:
          "jc-term-editor__nav-button jc-editor-layout__nav-button",
        navAriaLabel: "Category section navigation",
        menuTitle: this.getI18nValue("category_editor_menu", "Menu"),
      });

      this.$formTable.before(shell.$shell);
      this.$aside = shell.$navSecondary;

      this.moveSubmitActions();

      const sections = [
        {
          id: SECTION_IDS.overview,
          navLabel: this.getI18nValue("category_editor_overview", "Overview"),
          cards: [
            {
              title: this.getI18nValue("category_editor_basics", "Basics"),
              rows: this.collectBasicRows(),
            },
            {
              title: this.getI18nValue("category_editor_media", "Media"),
              rows: this.collectRows([
                "#thumbnail_id",
                "#thumbnail_id_container",
                "#banner_id",
                "#banner_id_container",
              ]),
            },
          ],
        },
        {
          id: SECTION_IDS.content,
          navLabel: this.getI18nValue("category_editor_content", "Content"),
          cards: [
            {
              title: this.getI18nValue(
                "category_editor_page_content",
                "Page Content",
              ),
              rows: this.collectRows([
                "#category_h1_title",
                "#category_why_choose_title",
                "#category_why_choose",
                "#category_advantages",
              ]),
            },
          ],
        },
        {
          id: SECTION_IDS.faq,
          navLabel: this.getI18nValue("category_editor_faq", "FAQ"),
          cards: [
            {
              title: this.getI18nValue("product_cat_faqs", "Category FAQs"),
              rows: this.collectRows(["#product_cat_faqs_container"]),
            },
          ],
        },
      ];

      const renderedSections = [];

      sections.forEach((section) => {
        const $section = this.buildSection(section);

        if (!$section.length) {
          return;
        }

        this.$content.append($section);
        renderedSections.push({
          id: section.id,
          navLabel: section.navLabel,
        });
      });

      this.initSectionNavigation(renderedSections);
      this.collectRemainingTableRows();
      this.preserveDetachedFormTableInputs();
      this.$formTable.remove();
      this.collectDeferredFields();
      this.observePendingNodes();
      this.registerSectionProgressTracking();
    }

    reorderTermDescriptionWrap() {
      const $parentWrap = this.$form.children(".term-parent-wrap").first();
      const $descriptionWrap = this.getTermDescriptionWrap();

      if (
        !$parentWrap.length ||
        !$descriptionWrap.length ||
        $descriptionWrap.prev()[0] === $parentWrap[0]
      ) {
        return;
      }

      $parentWrap.after($descriptionWrap);
    }

    /**
     * 获取分类描述字段所在行，兼容 Rank Math 改写后的描述字段容器。
     */
    getTermDescriptionWrap() {
      return this.$form
        .children(".term-description-wrap, .rank-math-term-description-wrap")
        .first();
    }

    buildSection(section) {
      const cardElements = [];

      section.cards.forEach((cardConfig) => {
        const $card = this.buildTableCard(cardConfig.title, cardConfig.rows);

        if ($card.length) {
          cardElements.push($card[0]);
        }
      });

      if (!cardElements.length) {
        return $();
      }

      const $section = $(`
        <section
          class="jc-term-editor__section"
          id="${this.getSectionDomId(section.id)}"
        >
          <div class="jc-term-editor__section-grid"></div>
        </section>
      `);

      $section.find(".jc-term-editor__section-grid").append(cardElements);
      return $section;
    }

    buildTableCard(title, rows) {
      if (!rows.length) {
        return $();
      }

      const $card = this.buildPostbox(title);
      const $table = $(`
        <table class="form-table jc-term-editor__table" role="presentation">
          <tbody></tbody>
        </table>
      `);
      const $tbody = $table.find("tbody");

      rows.each((_, row) => {
        $tbody.append(row);
      });

      $card.find(".inside").append($table);
      return $card;
    }

    buildExternalCard(title, nodes, cardClass = "") {
      if (nodes.length === 1) {
        const $node = $(nodes[0]);

        if ($node.hasClass("postbox")) {
          if (cardClass) {
            $node.addClass(cardClass);
          }

          return $node;
        }
      }

      const $card = this.buildBlockCard(title, cardClass);
      $card.find(".jc-term-editor__block-card-content").append(nodes);
      return $card;
    }

    collectRows(fieldSelectors) {
      const rows = [];
      const seenRows = new Set();

      fieldSelectors.forEach((fieldSelector) => {
        this.$formTable.find(fieldSelector).each((_, field) => {
          const row = $(field).closest("tr.form-field")[0];

          if (!row || seenRows.has(row)) {
            return;
          }

          seenRows.add(row);
          $(row).attr("data-jc-category-bound", "true");
          rows.push(row);
        });
      });

      return $(rows);
    }

    /**
     * 收集 Basics 区块字段，确保分类描述始终进入基础信息卡片。
     */
    collectBasicRows() {
      const $baseRows = this.collectRows(["#name", "#slug", "#parent"]);
      const rows = $baseRows.get();
      const seenRows = new Set(rows);

      TERM_DESCRIPTION_ROW_SELECTORS.forEach((fieldSelector) => {
        this.$form.find(fieldSelector).each((_, field) => {
          const row = $(field).closest("tr.form-field")[0] || $(field)[0];

          if (!row || seenRows.has(row)) {
            return;
          }

          seenRows.add(row);
          $(row).attr("data-jc-category-bound", "true");
          rows.push(row);
        });
      });

      return $(rows);
    }

    getOrCreateSection(sectionId, navLabel) {
      let $section = this.getSectionElement(sectionId);

      if ($section.length) {
        return $section;
      }

      $section = $(`
        <section
          class="jc-term-editor__section"
          id="${this.getSectionDomId(sectionId)}"
        >
          <div class="jc-term-editor__section-grid"></div>
        </section>
      `);

      this.$content.append($section);
      this.appendNavButton(sectionId, navLabel);
      this.initSectionNavigation(
        this.$navList
          .find(SELECTORS.navButton)
          .map((_, button) => ({
            id: $(button).data("target"),
            navLabel:
              $(button)
                .find(".jc-editor-layout__nav-label")
                .text()
                .trim() || $(button).text().trim(),
          }))
          .get(),
      );

      return $section;
    }

    appendSectionCard(sectionId, navLabel, $card) {
      if (!$card.length) {
        return;
      }

      const $section = this.getOrCreateSection(sectionId, navLabel);
      $section.find(".jc-term-editor__section-grid").append($card);

      if (sectionId === SECTION_IDS.additional) {
        this.moveSectionToBottom(sectionId);
      }
    }

    moveSectionToBottom(sectionId) {
      const $section = this.getSectionElement(sectionId);

      if (!$section.length || !$section.parent().length) {
        return;
      }

      $section.parent().append($section);
    }

    collectDeferredFields() {
      const externalNodes = [];
      const seoNodes = [];

      this.$form.children().each((_, child) => {
        const $child = $(child);

        if (
          child === this.$shell[0] ||
          child === this.$formTable[0] ||
          $child.is("input[type='hidden'], script, style") ||
          $child.hasClass("hidden") ||
          $child.attr("data-jc-category-shell-child") === "true" ||
          $child.closest(SELECTORS.shell).length
        ) {
          return;
        }

        if (
          $child.is(".submit, .edit-tag-actions") ||
          $child.find(".submit, .edit-tag-actions").length
        ) {
          return;
        }

        const identity = `${child.id} ${child.className}`.toLowerCase();

        if (identity.includes("rank-math") || identity.includes("seo")) {
          seoNodes.push(child);
          return;
        }

        externalNodes.push(child);
      });

      if (seoNodes.length) {
        const $seoCard = this.buildExternalCard(
          this.getI18nValue("category_editor_seo", "SEO"),
          seoNodes,
          "jc-term-editor__card--external",
        );

        this.appendSectionCard(
          SECTION_IDS.seo,
          this.getI18nValue("category_editor_seo", "SEO"),
          $seoCard,
        );
      }

      if (externalNodes.length) {
        const $externalCard = this.buildExternalCard(
          this.getI18nValue(
            "category_editor_additional_fields",
            "Additional Fields",
          ),
          externalNodes,
          "jc-term-editor__card--external",
        );

        this.appendSectionCard(
          SECTION_IDS.additional,
          "",
          $externalCard,
        );
      }

      this.moveSectionToBottom(SECTION_IDS.additional);
    }

    collectRemainingTableRows() {
      const $remainingRows = this.$formTable
        .find("tr.form-field")
        .filter((_, row) => !row.hasAttribute("data-jc-category-bound"));

      if (!$remainingRows.length) {
        return;
      }

      $remainingRows.attr("data-jc-category-bound", "true");

      const $miscCard = this.buildTableCard(
        this.getI18nValue(
          "category_editor_additional_fields",
          "Additional Fields",
        ),
        $remainingRows,
      );

      this.appendSectionCard(
        SECTION_IDS.additional,
        "",
        $miscCard,
      );
    }

    /**
     * 保留原始表格中未被卡片搬运的隐藏字段，避免删除 form-table 时丢失 nonce。
     */
    preserveDetachedFormTableInputs() {
      const $hiddenInputs = this.$formTable
        .find("input[type='hidden']")
        .filter((_, input) => !$(input).closest("tr.form-field").length);

      if (!$hiddenInputs.length) {
        return;
      }

      const $stash = $('<div class="jc-term-editor__hidden-inputs" hidden></div>');
      $hiddenInputs.each((_, input) => {
        $stash.append(input);
      });

      this.$form.prepend($stash);
    }

    moveSubmitActions() {
      const $actions = this.$form.children(SELECTORS.submitActions).first();

      if (!$actions.length) {
        return;
      }

      $actions.attr("data-jc-category-shell-child", "true");

      const $actionsCard = this.buildPostbox(
        this.getI18nValue("category_editor_actions", "Actions"),
        "jc-term-editor__actions-card",
      );

      $actionsCard.find(".inside").append($actions);
      this.$aside.append($actionsCard);
    }

    decorateControls() {
      this.$form
        .find("#thumbnail_id_button, #banner_id_button")
        .addClass("jelly-button");

      this.$form
        .find(".remove-thumbnail, .remove-banner")
        .addClass("jelly-button error");

      this.$form
        .find(".submit .button-primary, .edit-tag-actions .button-primary")
        .addClass("jelly-button primary");
    }

    observePendingNodes() {
      if (!("MutationObserver" in window)) {
        return;
      }

      if (this.pendingObserver) {
        this.pendingObserver.disconnect();
      }

      this.pendingObserver = new MutationObserver(() => {
        this.collectDeferredFields();
        this.lockPostboxes({
          scope: this.$shell,
        });
        this.fixEditorLayout();
      });

      this.pendingObserver.observe(this.$form[0], {
        childList: true,
        subtree: false,
      });
    }

    fixEditorLayout() {
      this.refreshTinyMCE();
    }

    /**
     * 注册分类编辑器每个 section 的完成度规则。
     */
    registerSectionProgressTracking() {
      this.setSectionProgressDefinitions({
        [SECTION_IDS.overview]: {
          items: [
            {
              key: "description",
              isComplete: () => this.hasFieldValue("#description"),
            },
            {
              key: "thumbnail",
              isComplete: () => this.hasFieldValue("#thumbnail_id"),
            },
          ],
        },
        [SECTION_IDS.content]: {
          editorIds: [
            CATEGORY_PROGRESS_EDITORS.whyChoose,
            CATEGORY_PROGRESS_EDITORS.advantages,
          ],
          items: [
            {
              key: "h1Title",
              isComplete: () => this.hasFieldValue("#category_h1_title"),
            },
            {
              key: "whyChooseTitle",
              isComplete: () =>
                this.hasFieldValue("#category_why_choose_title"),
            },
            {
              key: "whyChoose",
              isComplete: () =>
                this.hasEditorContent(
                  CATEGORY_PROGRESS_EDITORS.whyChoose,
                  "#category_why_choose",
                ),
            },
            {
              key: "advantages",
              isComplete: () =>
                this.hasEditorContent(
                  CATEGORY_PROGRESS_EDITORS.advantages,
                  "#category_advantages",
                ),
            },
          ],
        },
        [SECTION_IDS.faq]: {
          items: [
            {
              key: "faq",
              isComplete: () =>
                this.hasCompletedRepeaterItems(
                  "#product_cat_faqs_container",
                  ".repeater-item__key-input",
                  ".repeater-item__value-input",
                ),
            },
          ],
        },
        [SECTION_IDS.seo]: {
          items: [
            {
              key: "seo",
              isComplete: () =>
                this.hasMeaningfulFieldValue(
                  "#jc-term-editor-section-seo",
                ),
            },
          ],
        },
      });
    }
  }

  $(document).ready(() => {
    window.JellyCatalogCategoryManager = new JellyCatalogCategoryManager();
    window.jcCategoryEditorLayoutState = "booting";
    window.JellyCatalogCategoryEditorLayout =
      new JellyCatalogCategoryEditorLayout();

    if (window.jcCategoryEditorLayoutState !== "ready") {
      window.jcCategoryEditorLayoutState = "skipped";
    }
  });
})(jQuery);
