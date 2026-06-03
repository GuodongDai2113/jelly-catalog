(function ($) {
  "use strict";

  /**
   * 初始化滚动辅助方法，供产品详情导航复用。
   *
   * @return {void}
   */
  function initScrollHelpers() {
    const helpers = (window.JellyCatalog = window.JellyCatalog || {});

    if (helpers.scrollHelpersReady) {
      return;
    }

    helpers.scrollHelpersReady = true;
    helpers.scrollCompleteTimer = helpers.scrollCompleteTimer || 0;

    helpers.cancelScrollTransition = function () {
      if (!helpers.scrollCompleteTimer) {
        return;
      }

      window.clearTimeout(helpers.scrollCompleteTimer);
      helpers.scrollCompleteTimer = 0;
    };

    helpers.scrollToPosition = function (targetTop, options = {}) {
      const { duration = 400, onComplete = null } = options;
      const $window = $(window);
      const maxScroll = Math.max($(document).height() - $window.height(), 0);
      const safeTargetTop = Math.min(Math.max(targetTop, 0), maxScroll);
      const prefersReducedMotion = window.matchMedia(
        "(prefers-reduced-motion: reduce)"
      ).matches;
      const complete = function () {
        helpers.scrollCompleteTimer = 0;

        if (typeof onComplete === "function") {
          onComplete();
        }
      };

      helpers.cancelScrollTransition();
      window.scrollTo({
        top: window.scrollY || window.pageYOffset || 0,
        behavior: "auto",
      });

      if (prefersReducedMotion || duration <= 0) {
        window.scrollTo({
          top: safeTargetTop,
          behavior: "auto",
        });
        complete();
        return;
      }

      window.scrollTo({
        top: safeTargetTop,
        behavior: "smooth",
      });

      if (typeof onComplete === "function") {
        helpers.scrollCompleteTimer = window.setTimeout(complete, duration + 80);
      }
    };

    helpers.scrollToTarget = function ($target, options = {}) {
      if (!$target.length) {
        return;
      }

      const { offset = 0, duration = 400, onComplete = null } = options;
      const targetTop = ($target.offset()?.top || 0) - offset;

      helpers.scrollToPosition(targetTop, {
        duration,
        onComplete,
      });
    };
  }

  /**
   * 初始化产品详情导航。
   *
   * @param {jQuery|string|Element} scope 当前初始化范围。
   * @return {void}
   */
  function initProductDetailNav(scope) {
    initScrollHelpers();

    const helpers = window.JellyCatalog || {};
    const $window = $(window);
    const $header = $(".site-header").first();
    const $scope = scope ? $(scope) : $(document);
    const $navs = $scope
      .find("[data-jc-product-detail-nav], [data-product-detail-nav]")
      .addBack("[data-jc-product-detail-nav], [data-product-detail-nav]");

    if (!$navs.length) {
      return;
    }

    const getHeaderOffset = function () {
      return ($header.length && $header.outerHeight()) || 0;
    };

    $navs.each(function (index) {
      const $nav = $(this);

      if ($nav.data("jcProductDetailNavReady")) {
        return;
      }

      $nav.data("jcProductDetailNavReady", true);

      const $section = $nav.closest(".jc-product-detail, .product-detail");
      const $sections = $section
        .find("[data-jc-product-detail-sections], [data-product-detail-sections]")
        .first();
      const namespace = `.jcProductDetailNav${Date.now()}${index}`;
      let rafId = 0;

      if (!$section.length || !$sections.length) {
        return;
      }

      const $headings = $sections.find("h2");

      if (!$headings.length) {
        return;
      }

      $nav.empty();

      $headings.each(function (headingIndex) {
        const $heading = $(this);
        let headingId = $heading.attr("id");

        if (!headingId) {
          headingId = `jc-product-detail-heading-${index}-${headingIndex}`;
          $heading.attr("id", headingId);
        }

        const $link = $("<a></a>", {
          class: `jc-product-detail__tab${
            headingIndex === 0 ? " is-active" : ""
          }`,
          href: `#${headingId}`,
          "data-jc-product-detail-link": "",
          "aria-current": headingIndex === 0 ? "location" : null,
        });

        $("<span></span>", {
          class: "jc-product-detail__tab-label",
          text: $heading.text(),
        }).appendTo($link);

        $nav.append($link);
      });

      const $links = $nav.find("[data-jc-product-detail-link]");
      const linkMap = $links
        .map(function () {
          const $link = $(this);
          const href = $link.attr("href") || "";

          if (href.charAt(0) !== "#") {
            return null;
          }

          const $target = $(href);

          if (!$target.length) {
            return null;
          }

          return {
            $link,
            $target,
          };
        })
        .get();

      if (!linkMap.length) {
        return;
      }

      const scrollNavIntoView = function ($link, immediate = false) {
        const navElement = $nav.get(0);
        const linkElement = $link.get(0);

        if (!navElement || !linkElement) {
          return;
        }

        const targetLeft =
          linkElement.offsetLeft -
          (navElement.clientWidth - linkElement.offsetWidth) / 2;
        const maxScroll = Math.max(
          navElement.scrollWidth - navElement.clientWidth,
          0
        );
        const safeTargetLeft = Math.min(Math.max(targetLeft, 0), maxScroll);

        try {
          navElement.scrollTo({
            left: safeTargetLeft,
            behavior: immediate ? "auto" : "smooth",
          });
        } catch (error) {
          navElement.scrollLeft = safeTargetLeft;
        }
      };

      const setActiveLink = function ($activeLink) {
        const $current = $links.filter(".is-active").first();

        if ($current.length && $current.get(0) === $activeLink.get(0)) {
          return;
        }

        $links.removeClass("is-active").removeAttr("aria-current");
        $activeLink.addClass("is-active").attr("aria-current", "location");
        scrollNavIntoView($activeLink);
      };

      const getScrollOffset = function () {
        return getHeaderOffset() + ($nav.outerHeight() || 0) + 24;
      };

      const syncOffsets = function () {
        const headerOffset = getHeaderOffset();
        const navHeight = $nav.outerHeight() || 0;
        const sectionElement = $section.get(0);

        if (!sectionElement) {
          return;
        }

        sectionElement.style.setProperty(
          "--jc-product-detail-sticky-offset",
          `${headerOffset}px`
        );
        sectionElement.style.setProperty(
          "--jc-product-detail-scroll-offset",
          `${headerOffset + navHeight + 24}px`
        );
      };

      const syncStickyState = function () {
        const navRect = $nav.get(0)?.getBoundingClientRect();

        if (!navRect) {
          return;
        }

        $nav.toggleClass("is-sticky", navRect.top <= getHeaderOffset() + 1);
      };

      const syncActiveSection = function () {
        const scrollTop = ($window.scrollTop() || 0) + getScrollOffset();
        let $activeLink = linkMap[0].$link;

        linkMap.forEach(function (item) {
          const targetTop = item.$target.offset()?.top || 0;

          if (scrollTop >= targetTop) {
            $activeLink = item.$link;
          }
        });

        setActiveLink($activeLink);
      };

      const render = function () {
        syncOffsets();
        syncStickyState();
        syncActiveSection();
      };

      const requestRender = function () {
        if (rafId) {
          return;
        }

        rafId = window.requestAnimationFrame(function () {
          rafId = 0;
          render();
        });
      };

      $links.on("click", function (event) {
        const $link = $(this);
        const href = $link.attr("href") || "";
        const $target = href.charAt(0) === "#" ? $(href) : $();

        if (!$target.length) {
          return;
        }

        event.preventDefault();
        setActiveLink($link);

        if (typeof helpers.scrollToTarget === "function") {
          helpers.scrollToTarget($target, {
            offset: getScrollOffset(),
            duration: 400,
            onComplete: requestRender,
          });
          return;
        }

        requestRender();
      });

      $window.on(
        `scroll${namespace} resize${namespace} load${namespace}`,
        requestRender
      );
      scrollNavIntoView(linkMap[0].$link, true);
      requestRender();
    });
  }

  /**
   * 初始化通用 tabs 行为。
   *
   * @param {jQuery|string|Element} scope 当前初始化范围。
   * @return {void}
   */
  function initProductTabs(scope) {
    const $scope = scope ? $(scope) : $(document);
    const $groups = $scope
      .find("[data-jc-product-tabs], [data-product-tabs]")
      .addBack("[data-jc-product-tabs], [data-product-tabs]");

    if (!$groups.length) {
      return;
    }

    $groups.each(function () {
      const $group = $(this);

      if ($group.data("jcProductTabsReady")) {
        return;
      }

      $group.data("jcProductTabsReady", true);

      const $buttons = $group.find("[data-tab-target]");
      const $panels = $group.find("[data-tab-panel]");

      if (!$buttons.length || !$panels.length) {
        return;
      }

      const activateTab = function (target) {
        $buttons.each(function () {
          const $button = $(this);
          const isActive = $button.data("tab-target") === target;

          $button.toggleClass("is-active", isActive);
          $button.attr("aria-selected", isActive ? "true" : "false");
          $button.attr("tabindex", isActive ? "0" : "-1");
        });

        $panels.each(function () {
          const $panel = $(this);
          const isActive = $panel.data("tab-panel") === target;

          $panel.toggleClass("is-active", isActive);
          $panel.prop("hidden", !isActive);
        });
      };

      activateTab(
        $buttons.filter(".is-active").data("tab-target") ||
          $buttons.first().data("tab-target")
      );

      $buttons.on("click", function () {
        activateTab($(this).data("tab-target"));
      });

      $buttons.on("keydown", function (event) {
        if (event.key !== "ArrowLeft" && event.key !== "ArrowRight") {
          return;
        }

        event.preventDefault();

        const currentIndex = $buttons.index(this);
        const direction = event.key === "ArrowRight" ? 1 : -1;
        const nextIndex = (currentIndex + direction + $buttons.length) % $buttons.length;
        const $nextButton = $buttons.eq(nextIndex);

        activateTab($nextButton.data("tab-target"));
        $nextButton.trigger("focus");
      });
    });
  }

  /**
   * 初始化产品详情相关交互。
   *
   * @param {jQuery|string|Element} scope 当前初始化范围。
   * @return {void}
   */
  function initProductContent(scope) {
    initProductDetailNav(scope);
    initProductTabs(scope);
  }

  $(function () {
    initProductContent(document);
  });

  $(window).on("elementor/frontend/init", function () {
    if (!window.elementorFrontend || !window.elementorFrontend.hooks) {
      return;
    }

    window.elementorFrontend.hooks.addAction(
      "frontend/element_ready/jc-product-content.default",
      initProductContent
    );
  });
})(jQuery);
