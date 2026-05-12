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
      const adminBarHeight =
        $(this.config.adminBarSelector).outerHeight() || 0;

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
      this.$navList = $shell.find("." + navListClass.split(" ").join(".")).first();
      this.$navSecondary = $shell
        .find("." + navSecondaryClass.split(" ").join("."))
        .first();
      this.$content = $shell.find("." + contentClass.split(" ").join(".")).first();
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

    appendNavButton(sectionId, label) {
      if (!label || !this.$navList.length) {
        return;
      }

      if (this.$navList.find(`[data-target="${sectionId}"]`).length) {
        return;
      }

      this.$navList.append(`
        <button
          type="button"
          class="${this.config.navButtonClass}"
          data-target="${sectionId}"
        >
          ${label}
        </button>
      `);
    }

    initSectionNavigation(sections) {
      const navSections = (sections || []).filter((section) => section.navLabel);

      if (!this.$nav.length || !this.$navList.length || !navSections.length) {
        this.$nav.hide();
        return;
      }

      this.$nav.show();
      this.$navList.empty();

      navSections.forEach((section) => {
        this.appendNavButton(section.id, section.navLabel);
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
    }

    scrollToSection(sectionId) {
      const $section = this.getSectionElement(sectionId);

      if (!$section.length) {
        return;
      }

      this.setActiveNav(sectionId);

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

    observeSections(sectionIds = null) {
      if (this.sectionObserver) {
        this.sectionObserver.disconnect();
      }

      if (!("IntersectionObserver" in window) || !this.$content.length) {
        return;
      }

      this.sectionObserver = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (!entry.isIntersecting) {
              return;
            }

            const sectionId = this.resolveSectionId(entry.target.id);
            this.setActiveNav(sectionId);
          });
        },
        {
          root: null,
          rootMargin: `-${this.getScrollOffset()}px 0px -55% 0px`,
          threshold: 0.15,
        },
      );

      const $sections =
        Array.isArray(sectionIds) && sectionIds.length
          ? $(sectionIds.map((sectionId) => "#" + this.getSectionDomId(sectionId)).join(","))
          : this.$content.find(this.config.sectionSelector);

      $sections.each((_, section) => {
        this.sectionObserver.observe(section);
      });
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

      const $scope = settings.scope instanceof $ ? settings.scope : $(settings.scope);

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
        [
          "click",
          "mousedown",
          "dblclick",
          "keydown",
        ]
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
