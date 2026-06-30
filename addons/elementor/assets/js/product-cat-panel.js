(function ($) {
  "use strict";

  /**
   * 同步分类节点的展开状态和子列表显示状态。
   *
   * @param {jQuery} $item 当前分类节点。
   * @param {boolean} shouldExpand 是否展开子节点。
   * @param {boolean} animate 是否启用动画。
   * @return {void}
   */
  function setExpandedState($item, shouldExpand, animate) {
    const $children = $item.children(".jc-product-category-children");
    const $toggle = $item
      .children(".jc-product-category-node__row")
      .find(".jc-product-category-node__toggle")
      .first();

    if (!$children.length) {
      return;
    }

    $item.toggleClass("is-expanded", shouldExpand);
    $toggle.attr("aria-expanded", shouldExpand ? "true" : "false");
    $children.stop(true, true);

    if (animate) {
      if (shouldExpand) {
        $children.slideDown(200);
      } else {
        $children.slideUp(200);
      }

      return;
    }

    $children.css("display", shouldExpand ? "block" : "none");
  }

  /**
   * 初始化产品分类面板的折叠交互。
   *
   * @param {jQuery|string|Element} scope 当前初始化范围。
   * @return {void}
   */
  function initProductCategoryPanel(scope) {
    const $scope = scope ? $(scope) : $(document);
    const $categories = $scope
      .find('[data-widget="jc-product-category-menu"]')
      .addBack('[data-widget="jc-product-category-menu"]');
    const expandedCategorySelector =
      "li.current-cat, li.current-cat-parent, li.current-cat-ancestor";
    const categoryToggleSelector = ".jc-product-category-node__toggle";

    if (!$categories.length) {
      return;
    }

    $categories.each(function () {
      const $category = $(this);

      $category.find(".jc-product-category-node").each(function () {
        const $item = $(this);
        const shouldExpand = $item.is(expandedCategorySelector);

        setExpandedState($item, shouldExpand, false);
      });
    });

    $categories.off("click.jcProductCategoryPanel", categoryToggleSelector);
    $categories.on(
      "click.jcProductCategoryPanel",
      categoryToggleSelector,
      function (event) {
        const $toggle = $(this);
        const $item = $toggle.closest(".jc-product-category-node");

        event.preventDefault();
        event.stopPropagation();

        if (!$item.length) {
          return;
        }

        setExpandedState($item, !$item.hasClass("is-expanded"), true);
      },
    );
  }

  $(function () {
    initProductCategoryPanel(document);
  });

  $(window).on("elementor/frontend/init", function () {
    if (!window.elementorFrontend || !window.elementorFrontend.hooks) {
      return;
    }

    window.elementorFrontend.hooks.addAction(
      "frontend/element_ready/jc-product-cat-panel.default",
      initProductCategoryPanel,
    );
  });
})(jQuery);
