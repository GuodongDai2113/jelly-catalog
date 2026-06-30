(function ($) {
  "use strict";

  let editorInstanceCounter = 0;

  class BaseEditor {
    constructor(options = {}) {
      editorInstanceCounter += 1;

      this.config = $.extend(
        true,
        {
          bodySelector: "body",
          adminBarSelector: "#wpadminbar",
          loadingBodyClass: "",
          loadingTextKey: "",
          loadingText: "Loading editor...",
          loadingId: "jc-admin-editor-loading",
          navButtonSelector: ".jc-editor-layout__nav-button",
          navButtonClass: "jc-editor-layout__nav-button",
          sectionSelector: "section",
          sectionIdPrefix: "",
          tinyMceDelay: 50,
          initialReadyDelay: 0,
          postLayoutLoadingDelay: 0,
          postboxSelector: ".postbox",
          postboxInsideSelector: ".postbox .inside",
          postboxHandleSelector:
            ".postbox .hndle, .postbox .handlediv, .postbox .handle-actions",
        },
        options,
      );

      this.$body = $(this.config.bodySelector);
      this.$shell = $();
      this.$nav = $();
      this.$navList = $();
      this.$navSecondary = $();
      this.$content = $();

      this.editorLoading = null;
      this.postboxLockHandler = null;
      this.postboxObserver = null;
      this.sectionObserver = null;
      this.sectionVisibilityState = new Map();
      this.navProgressDefinitions = {};
      this.navProgressObserver = null;
      this.navProgressEditorBindings = new Set();
      this.navProgressRefreshTimer = null;
      this.isNavScrollLocked = false;
      this.navScrollTargetId = "";
      this.navScrollUnlockTimer = null;
      this.navScrollStartedAt = 0;
      this.navScrollHandler = null;
      this.instanceNamespace = ".jcEditorInstance" + editorInstanceCounter;
    }

    getI18n(key, fallback) {
      return window.jc_product_i18n?.[key] || fallback;
    }

    getSectionDomId(sectionId) {
      return this.config.sectionIdPrefix + sectionId;
    }

    getSectionElement(sectionId) {
      return $("#" + this.getSectionDomId(sectionId));
    }

    /**
     * 获取侧边栏导航按钮节点。
     */
    getNavButton(sectionId) {
      if (!this.$navList.length) {
        return $();
      }

      return this.$navList
        .find(this.config.navButtonSelector)
        .filter(`[data-target="${sectionId}"]`)
        .first();
    }

    /**
     * 统一清洗文本值，避免空白和 HTML 干扰完成度判断。
     */
    normalizeTextValue(value) {
      return String(value ?? "")
        .replace(/\u00a0/g, " ")
        .replace(/\s+/g, " ")
        .trim();
    }

    /**
     * 将富文本内容转换为纯文本，用于判断编辑器是否已填写。
     */
    getPlainTextFromHtml(value) {
      const container = document.createElement("div");
      container.innerHTML = typeof value === "string" ? value : "";
      return this.normalizeTextValue(container.textContent || "");
    }

    /**
     * 判断普通表单字段是否存在有效值。
     */
    hasFieldValue(selector) {
      const $field = $(selector).first();

      if (!$field.length) {
        return false;
      }

      if ($field.is(":checkbox, :radio")) {
        return $field.is(":checked");
      }

      return this.normalizeTextValue($field.val()).length > 0;
    }

    /**
     * 判断富文本编辑器是否存在有效内容。
     */
    hasEditorContent(editorId, fallbackSelector = "") {
      const editor = window.tinymce?.get?.(editorId);

      if (
        editor &&
        typeof editor.getContent === "function" &&
        !editor.isHidden()
      ) {
        return this.getPlainTextFromHtml(editor.getContent()).length > 0;
      }

      if (fallbackSelector) {
        return this.hasFieldValue(fallbackSelector);
      }

      return this.hasFieldValue("#" + editorId);
    }

    /**
     * 判断复选框/单选框集合中是否存在已选项。
     */
    hasCheckedValue(selector) {
      return $(selector).filter(":checked").length > 0;
    }

    /**
     * 判断指定容器内是否已经存在图片预览。
     */
    hasImagePreview(selector) {
      return $(selector).find("img").length > 0;
    }

    /**
     * 判断 repeater 中是否至少有一项键值都已填写。
     */
    hasCompletedRepeaterItems(containerSelector, keySelector, valueSelector) {
      let hasCompletedItem = false;

      $(containerSelector)
        .find(".repeater-item")
        .each((_, item) => {
          const $item = $(item);
          const key = this.normalizeTextValue($item.find(keySelector).val());
          const value = this.normalizeTextValue(
            $item.find(valueSelector).val(),
          );

          if (key && value) {
            hasCompletedItem = true;
            return false;
          }
        });

      return hasCompletedItem;
    }

    /**
     * 判断某个区域内是否存在有意义的已填写字段。
     */
    hasMeaningfulFieldValue(scopeSelector) {
      let hasValue = false;

      $(scopeSelector)
        .find("input, textarea, select")
        .each((_, field) => {
          const $field = $(field);
          const fieldType = String($field.attr("type") || "").toLowerCase();
          const fieldName = String($field.attr("name") || "").toLowerCase();
          const fieldId = String($field.attr("id") || "").toLowerCase();

          if (
            $field.is(":disabled") ||
            fieldType === "hidden" ||
            fieldType === "button" ||
            fieldType === "submit" ||
            fieldType === "reset" ||
            fieldType === "file" ||
            fieldType === "search" ||
            fieldType === "password" ||
            fieldName.indexOf("nonce") !== -1 ||
            fieldId.indexOf("nonce") !== -1
          ) {
            return;
          }

          if ($field.is(":checkbox, :radio")) {
            if ($field.is(":checked")) {
              hasValue = true;
              return false;
            }

            return;
          }

          if (this.normalizeTextValue($field.val()).length > 0) {
            hasValue = true;
            return false;
          }
        });

      return hasValue;
    }

    resolveSectionId(sectionElementId) {
      if (
        this.config.sectionIdPrefix &&
        sectionElementId.indexOf(this.config.sectionIdPrefix) === 0
      ) {
        return sectionElementId.slice(this.config.sectionIdPrefix.length);
      }

      return sectionElementId;
    }

    startLoadingState() {
      const loadingText = this.getI18n(
        this.config.loadingTextKey,
        this.config.loadingText,
      );

      if (this.config.loadingBodyClass) {
        this.$body.addClass(this.config.loadingBodyClass);
      }

      this.$body.addClass("is-jc-loading");
      this.editorLoading = new jellyCore.Loading({
        id: this.config.loadingId,
        text: loadingText,
      });
      this.editorLoading.setText(loadingText).open();
    }

    finishLoadingState() {
      if (this.config.loadingBodyClass) {
        this.$body.removeClass(this.config.loadingBodyClass);
      }

      this.$body.removeClass("is-jc-loading");

      if (this.editorLoading) {
        this.editorLoading.close();
        this.editorLoading = null;
      }
    }

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

    finalizeInitialLayout(callback = null) {
      window.setTimeout(() => {
        if (typeof callback === "function") {
          callback();
        }

        this.resetInitialViewport();

        window.requestAnimationFrame(() => {
          window.requestAnimationFrame(() => {
            window.setTimeout(() => {
              this.finishLoadingState();
            }, this.config.postLayoutLoadingDelay);
          });
        });
      }, this.config.initialReadyDelay);
    }

    getScrollOffset() {
      const adminBarHeight = $(this.config.adminBarSelector).outerHeight() || 0;

      return adminBarHeight + 24;
    }

    refreshTinyMCE(options = {}) {
      if (!window.tinymce) {
        return;
      }

      let editors = [];

      if (Array.isArray(options.editorIds) && options.editorIds.length) {
        editors = options.editorIds
          .map((editorId) => window.tinymce.get(editorId))
          .filter(Boolean);
      } else if (options.editorId) {
        const editor = window.tinymce.get(options.editorId);
        editors = editor ? [editor] : [];
      } else if (Array.isArray(window.tinymce.editors)) {
        editors = window.tinymce.editors.filter(Boolean);
      }

      if (!editors.length) {
        return;
      }

      window.setTimeout(() => {
        editors.forEach((editor) => {
          try {
            editor.execCommand("mceRepaint");
          } catch (error) {}

          try {
            editor.execCommand("mceResize");
          } catch (error) {}
        });

        $(window).trigger("resize");
      }, options.delay || this.config.tinyMceDelay);
    }

    createEditorShell(options = {}) {
      const shellClass = options.shellClass || "jc-editor-layout";
      const navClass = options.navClass || "jc-editor-layout__nav";
      const navListClass = options.navListClass || "jc-editor-layout__nav-list";
      const navSecondaryClass =
        options.navSecondaryClass || "jc-editor-layout__nav-secondary";
      const contentClass = options.contentClass || "jc-editor-layout__content";
      const navButtonClass =
        options.navButtonClass || this.config.navButtonClass;
      const menuTitle = options.menuTitle || "Menu";
      const navAriaLabel = options.navAriaLabel || "Section navigation";

      const $shell = $(`
        <div class="${shellClass}">
          <aside class="${navClass}" aria-label="${navAriaLabel}">
            <div class="postbox">
              <div class="postbox-header">
                <h2 class="hndle">${menuTitle}</h2>
              </div>
              <div class="inside">
                <div class="${navListClass}"></div>
              </div>
            </div>
            <div class="${navSecondaryClass}"></div>
          </aside>
          <div class="${contentClass}"></div>
        </div>
      `);

      this.$shell = $shell;
      this.$nav = $shell.find("aside").first();
      this.$navList = $shell
        .find("." + navListClass.split(" ").join("."))
        .first();
      this.$navSecondary = $shell
        .find("." + navSecondaryClass.split(" ").join("."))
        .first();
      this.$content = $shell
        .find("." + contentClass.split(" ").join("."))
        .first();
      this.config.navButtonClass = navButtonClass;

      return {
        $shell: this.$shell,
        $nav: this.$nav,
        $navList: this.$navList,
        $navSecondary: this.$navSecondary,
        $content: this.$content,
      };
    }

    buildPostbox(title, extraClass = "") {
      const className = ["postbox", extraClass].filter(Boolean).join(" ");

      return $(`
        <div class="${className}">
          <div class="postbox-header">
            <h2 class="hndle">${title}</h2>
          </div>
          <div class="inside"></div>
        </div>
      `);
    }

    buildBlockCard(title, extraClass = "") {
      const $card = this.buildPostbox(title, extraClass);
      $card.find(".inside").addClass("jc-term-editor__block-card-content");
      return $card;
    }

    /**
     * 生成侧边栏导航按钮，包含编号和进度环容器。
     */
    appendNavButton(sectionId, label, index = null) {
      if (!label || !this.$navList.length) {
        return;
      }

      if (this.$navList.find(`[data-target="${sectionId}"]`).length) {
        return;
      }

      const navIndex = String(
        index || this.$navList.find(this.config.navButtonSelector).length + 1,
      ).padStart(2, "0");

      this.$navList.append(`
        <button
          type="button"
          class="${this.config.navButtonClass}"
          data-target="${sectionId}"
          data-progress-level="1"
          data-progress-status="error"
          style="
            --jc-nav-segment-1: var(--jelly-color-error);
            --jc-nav-segment-2: var(--jelly-color-border);
            --jc-nav-segment-3: var(--jelly-color-border);
            --jc-nav-segment-4: var(--jelly-color-border);
          "
        >
          <span class="jc-editor-layout__nav-indicator" aria-hidden="true">
            <span class="jc-editor-layout__nav-progress-ring"></span>
            <span class="jc-editor-layout__nav-index">${navIndex}</span>
          </span>
          <span class="jc-editor-layout__nav-label">${label}</span>
        </button>
      `);
    }

    initSectionNavigation(sections) {
      const navSections = (sections || []).filter(
        (section) => section.navLabel,
      );

      if (!this.$nav.length || !this.$navList.length || !navSections.length) {
        this.$nav.hide();
        return;
      }

      this.$nav.show();
      this.$navList.empty();

      navSections.forEach((section, index) => {
        this.appendNavButton(section.id, section.navLabel, index + 1);
      });

      this.$navList
        .off("click" + this.instanceNamespace)
        .on(
          "click" + this.instanceNamespace,
          this.config.navButtonSelector,
          (event) => {
            const sectionId = $(event.currentTarget).data("target");

            if (!sectionId) {
              return;
            }

            this.scrollToSection(sectionId);
          },
        );

      this.setActiveNav(navSections[0].id);
      this.observeSections(navSections.map((section) => section.id));
      this.queueSectionProgressRefresh();
    }

    /**
     * 在平滑滚动期间锁定导航状态，避免中途 section 抢占激活态。
     */
    lockNavDuringScroll(sectionId) {
      this.isNavScrollLocked = true;
      this.navScrollTargetId = sectionId;
      this.navScrollStartedAt = Date.now();

      if (this.navScrollUnlockTimer) {
        window.clearTimeout(this.navScrollUnlockTimer);
      }

      if (this.navScrollHandler) {
        window.removeEventListener("scroll", this.navScrollHandler);
      }

      this.navScrollHandler = () => {
        const $targetSection = this.getSectionElement(this.navScrollTargetId);

        if (!$targetSection.length) {
          this.unlockNavAfterScroll();
          return;
        }

        const distanceToTarget = Math.abs(
          ($targetSection.offset()?.top || 0) -
            this.getScrollOffset() -
            window.scrollY,
        );

        if (distanceToTarget <= 28) {
          this.unlockNavAfterScroll();
        }
      };

      window.addEventListener("scroll", this.navScrollHandler, {
        passive: true,
      });

      this.navScrollUnlockTimer = window.setTimeout(() => {
        this.unlockNavAfterScroll();
      }, 1400);
    }

    /**
     * 结束平滑滚动锁定，并恢复滚动追踪。
     */
    unlockNavAfterScroll() {
      if (this.navScrollUnlockTimer) {
        window.clearTimeout(this.navScrollUnlockTimer);
        this.navScrollUnlockTimer = null;
      }

      if (this.navScrollHandler) {
        window.removeEventListener("scroll", this.navScrollHandler);
        this.navScrollHandler = null;
      }

      this.isNavScrollLocked = false;
      this.navScrollTargetId = "";
    }

    scrollToSection(sectionId) {
      const $section = this.getSectionElement(sectionId);

      if (!$section.length) {
        return;
      }

      this.setActiveNav(sectionId);
      this.lockNavDuringScroll(sectionId);

      const targetTop = ($section.offset()?.top || 0) - this.getScrollOffset();

      window.scrollTo({
        top: Math.max(targetTop, 0),
        behavior: "smooth",
      });
    }

    setActiveNav(sectionId) {
      if (!this.$navList.length) {
        return;
      }

      this.$navList
        .find(this.config.navButtonSelector)
        .removeClass("is-active")
        .filter(`[data-target="${sectionId}"]`)
        .addClass("is-active");
    }

    /**
     * 计算当前最适合作为激活态的 section。
     */
    getBestVisibleSectionId() {
      const visibilityEntries = Array.from(
        this.sectionVisibilityState.entries(),
      )
        .filter(([, state]) => state.isIntersecting)
        .sort(([, firstState], [, secondState]) => {
          const firstDistance = Math.abs(
            firstState.top - this.getScrollOffset(),
          );
          const secondDistance = Math.abs(
            secondState.top - this.getScrollOffset(),
          );

          if (firstDistance === secondDistance) {
            return secondState.ratio - firstState.ratio;
          }

          return firstDistance - secondDistance;
        });

      return visibilityEntries[0]?.[0] || "";
    }

    observeSections(sectionIds = null) {
      if (this.sectionObserver) {
        this.sectionObserver.disconnect();
      }

      this.sectionVisibilityState.clear();

      if (!("IntersectionObserver" in window) || !this.$content.length) {
        return;
      }

      this.sectionObserver = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            const sectionId = this.resolveSectionId(entry.target.id);
            this.sectionVisibilityState.set(sectionId, {
              isIntersecting: entry.isIntersecting,
              ratio: entry.intersectionRatio,
              top: entry.boundingClientRect.top,
            });
          });

          if (this.isNavScrollLocked) {
            if (this.navScrollTargetId) {
              this.setActiveNav(this.navScrollTargetId);
            }

            return;
          }

          const bestSectionId = this.getBestVisibleSectionId();

          if (bestSectionId) {
            this.setActiveNav(bestSectionId);
          }
        },
        {
          root: null,
          rootMargin: `-${this.getScrollOffset()}px 0px -55% 0px`,
          threshold: 0.15,
        },
      );

      const $sections =
        Array.isArray(sectionIds) && sectionIds.length
          ? $(
              sectionIds
                .map((sectionId) => "#" + this.getSectionDomId(sectionId))
                .join(","),
            )
          : this.$content.find(this.config.sectionSelector);

      $sections.each((_, section) => {
        this.sectionObserver.observe(section);
      });
    }

    /**
     * 注册各 section 的完成度定义。
     */
    setSectionProgressDefinitions(definitions = {}) {
      this.navProgressDefinitions = definitions || {};

      if (
        !Object.keys(this.navProgressDefinitions).length ||
        !this.$shell.length
      ) {
        return;
      }

      this.bindSectionProgressEvents();
      this.observeSectionProgressMutations();
      this.bindTinyMCEProgressEditors(
        Object.values(this.navProgressDefinitions).flatMap(
          (definition) => definition.editorIds || [],
        ),
      );
      this.queueSectionProgressRefresh();
    }

    /**
     * 绑定表单交互事件，字段变化后刷新导航进度。
     */
    bindSectionProgressEvents() {
      if (!this.$shell.length) {
        return;
      }

      this.$shell.off("input" + this.instanceNamespace + ".progress");
      this.$shell.off("change" + this.instanceNamespace + ".progress");
      this.$shell.off("click" + this.instanceNamespace + ".progress");

      this.$shell.on(
        "input" + this.instanceNamespace + ".progress",
        "input, textarea, select",
        () => {
          this.queueSectionProgressRefresh();
        },
      );

      this.$shell.on(
        "change" + this.instanceNamespace + ".progress",
        "input, textarea, select",
        () => {
          this.queueSectionProgressRefresh();
        },
      );

      this.$shell.on(
        "click" + this.instanceNamespace + ".progress",
        "button, a, input[type='checkbox'], input[type='radio']",
        (event) => {
          if ($(event.currentTarget).is(this.config.navButtonSelector)) {
            return;
          }

          window.setTimeout(() => {
            this.queueSectionProgressRefresh();
          }, 0);
        },
      );
    }

    /**
     * 获取需要监听 DOM 变化的区域，避免监听导航本身导致循环刷新。
     */
    getSectionProgressObserveRoots() {
      return [this.$content[0], this.$navSecondary[0]].filter(Boolean);
    }

    /**
     * 监听图片、repeater、异步组件等 DOM 变化。
     */
    observeSectionProgressMutations() {
      if (this.navProgressObserver) {
        this.navProgressObserver.disconnect();
      }

      if (!("MutationObserver" in window)) {
        return;
      }

      const observerRoots = this.getSectionProgressObserveRoots();

      if (!observerRoots.length) {
        return;
      }

      this.navProgressObserver = new MutationObserver(() => {
        this.queueSectionProgressRefresh();
      });

      observerRoots.forEach((root) => {
        this.navProgressObserver.observe(root, {
          childList: true,
          subtree: true,
          attributes: true,
          characterData: true,
        });
      });
    }

    /**
     * 绑定 TinyMCE 事件，让可视化编辑器也能实时驱动进度变化。
     */
    bindTinyMCEProgressEditors(editorIds = []) {
      const uniqueEditorIds = [...new Set((editorIds || []).filter(Boolean))];

      uniqueEditorIds.forEach((editorId) => {
        if (this.navProgressEditorBindings.has(editorId)) {
          return;
        }

        const attachEditorEvents = () => {
          const editor = window.tinymce?.get?.(editorId);

          if (!editor || this.navProgressEditorBindings.has(editorId)) {
            return false;
          }

          const bindEvents = () => {
            if (this.navProgressEditorBindings.has(editorId)) {
              return;
            }

            editor.on("input change keyup SetContent", () => {
              this.queueSectionProgressRefresh();
            });

            this.navProgressEditorBindings.add(editorId);
          };

          if (editor.initialized) {
            bindEvents();
          } else {
            editor.on("init", bindEvents);
          }

          return true;
        };

        if (attachEditorEvents()) {
          return;
        }

        const bindingTimer = window.setInterval(() => {
          if (attachEditorEvents()) {
            window.clearInterval(bindingTimer);
          }
        }, 300);

        window.setTimeout(() => {
          window.clearInterval(bindingTimer);
        }, 10000);
      });
    }

    /**
     * 统一做节流刷新，避免频繁操作造成闪烁。
     */
    queueSectionProgressRefresh() {
      if (this.navProgressRefreshTimer) {
        window.clearTimeout(this.navProgressRefreshTimer);
      }

      this.navProgressRefreshTimer = window.setTimeout(() => {
        this.refreshAllSectionProgress();
      }, 80);
    }

    /**
     * 按定义刷新所有 section 的完成度状态。
     */
    refreshAllSectionProgress() {
      this.navProgressRefreshTimer = null;

      Object.entries(this.navProgressDefinitions).forEach(
        ([sectionId, definition]) => {
          const progressState = this.evaluateSectionProgress(definition);
          this.updateNavButtonProgress(sectionId, progressState);
        },
      );
    }

    /**
     * 计算单个 section 的完成度和状态等级。
     */
    evaluateSectionProgress(definition = {}) {
      const items = Array.isArray(definition.items) ? definition.items : [];
      const totalCount = items.length;
      let completedCount = 0;

      items.forEach((item) => {
        if (typeof item?.isComplete === "function" && item.isComplete()) {
          completedCount += 1;
        }
      });

      return {
        totalCount,
        completedCount,
        ...this.resolveSectionProgressStage(completedCount, totalCount),
      };
    }

    /**
     * 将完成度压缩为 4 段视觉状态。
     */
    resolveSectionProgressStage(completedCount, totalCount) {
      if (!totalCount || completedCount <= 0) {
        return {
          level: 1,
          status: "error",
        };
      }

      if (completedCount >= totalCount) {
        return {
          level: 4,
          status: "success",
        };
      }

      if (completedCount / totalCount < 0.5) {
        return {
          level: 2,
          status: "error",
        };
      }

      return {
        level: 3,
        status: "warning",
      };
    }

    /**
     * 将 section 完成度同步到导航按钮视觉状态。
     */
    updateNavButtonProgress(sectionId, progressState) {
      const $button = this.getNavButton(sectionId);

      if (!$button.length || !progressState) {
        return;
      }

      const label = this.normalizeTextValue(
        $button.find(".jc-editor-layout__nav-label").text(),
      );
      const statusColorMap = {
        error: "var(--jelly-color-error)",
        warning: "var(--jelly-color-warning)",
        success: "var(--jelly-color-success)",
      };
      const activeColor =
        statusColorMap[progressState.status] || "var(--jelly-color-primary)";
      const trackColor = "var(--jelly-color-border)";

      for (let segmentIndex = 1; segmentIndex <= 4; segmentIndex += 1) {
        $button[0].style.setProperty(
          `--jc-nav-segment-${segmentIndex}`,
          segmentIndex <= progressState.level ? activeColor : trackColor,
        );
      }

      $button.attr("data-progress-level", progressState.level);
      $button.attr("data-progress-status", progressState.status);
      $button.attr(
        "aria-label",
        `${label} (${progressState.completedCount}/${progressState.totalCount})`,
      );
    }

    lockPostboxes(options = {}) {
      const settings = $.extend(
        {
          scope: this.$shell,
          destroySortable: false,
          sortableSelector: "",
          postboxSelector: this.config.postboxSelector,
          postboxInsideSelector: this.config.postboxInsideSelector,
          postboxHandleSelector: this.config.postboxHandleSelector,
        },
        options,
      );

      const $scope =
        settings.scope instanceof $ ? settings.scope : $(settings.scope);

      if (!$scope.length) {
        return;
      }

      const $postboxes = $scope.find(settings.postboxSelector);

      if (!$postboxes.length) {
        return;
      }

      $postboxes.removeClass("closed");
      $postboxes.find(".inside").show();

      if (settings.destroySortable && settings.sortableSelector) {
        $scope.find(settings.sortableSelector).each((_, container) => {
          const $container = $(container);

          if (!$container.hasClass("ui-sortable")) {
            return;
          }

          try {
            $container.sortable("destroy");
          } catch (error) {}
        });
      }

      $(document).off(this.instanceNamespace + ".lock");
      $(document).on(
        ["click", "mousedown", "dblclick", "keydown"]
          .map((eventName) => eventName + this.instanceNamespace + ".lock")
          .join(" "),
        settings.postboxHandleSelector,
        (event) => {
          const target = event.target;

          if (!(target instanceof Element) || !$scope.has(target).length) {
            return;
          }

          if (
            event.type === "keydown" &&
            event.key !== "Enter" &&
            event.key !== " " &&
            event.key !== "Spacebar"
          ) {
            return;
          }

          event.preventDefault();
          event.stopImmediatePropagation();
        },
      );

      if (this.postboxLockHandler) {
        document.removeEventListener("click", this.postboxLockHandler, true);
        document.removeEventListener(
          "mousedown",
          this.postboxLockHandler,
          true,
        );
        document.removeEventListener("dblclick", this.postboxLockHandler, true);
      }

      this.postboxLockHandler = (event) => {
        const target = event.target;

        if (!(target instanceof Element) || !$scope.has(target).length) {
          return;
        }

        if (!target.closest(settings.postboxHandleSelector)) {
          return;
        }

        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
      };

      document.addEventListener("click", this.postboxLockHandler, true);
      document.addEventListener("mousedown", this.postboxLockHandler, true);
      document.addEventListener("dblclick", this.postboxLockHandler, true);

      if (this.postboxObserver) {
        this.postboxObserver.disconnect();
      }

      this.postboxObserver = new MutationObserver(() => {
        $scope.find(settings.postboxSelector).removeClass("closed");
        $scope.find(settings.postboxInsideSelector).show();
      });

      $postboxes.each((_, postbox) => {
        this.postboxObserver.observe(postbox, {
          attributes: true,
          attributeFilter: ["class"],
        });
      });
    }
  }

  window.JellyCatalogAdminEditor = window.JellyCatalogAdminEditor || {};
  window.JellyCatalogAdminEditor.BaseEditor = BaseEditor;
})(jQuery);
