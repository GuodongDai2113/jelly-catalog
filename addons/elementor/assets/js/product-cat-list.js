(function ($) {
  "use strict";

  /**
   * 初始化产品分类列表折叠交互。
   *
   * @param {jQuery|string|Element} scope 当前初始化范围。
   * @return {void}
   */
  function initCategoryList(scope) {
    const $scope = scope ? $(scope) : $(document);
    const $lists = $scope.find(".jc-cat-list").addBack(".jc-cat-list");

    $lists.each(function () {
      const $list = $(this);

      if ($list.data("jcCatListReady")) {
        return;
      }

      $list.data("jcCatListReady", true);

      $list.on("click", function (event) {
        const $toggle = $(event.target).closest(".jc-cat-list__toggle");

        if (!$toggle.length) {
          return;
        }

        event.preventDefault();
        event.stopPropagation();

        const $item = $toggle.closest(".jc-cat-list__item");

        if (!$item.length) {
          return;
        }

        const isExpanded = $item.hasClass("is-expanded");
        $item.toggleClass("is-expanded");
        $toggle.attr("aria-expanded", isExpanded ? "false" : "true");
      });
    });
  }

  $(function () {
    initCategoryList(document);
  });

  $(window).on("elementor/frontend/init", function () {
    if (!window.elementorFrontend || !window.elementorFrontend.hooks) {
      return;
    }

    window.elementorFrontend.hooks.addAction(
      "frontend/element_ready/product_cat_list.default",
      initCategoryList
    );
  });
})(jQuery);
