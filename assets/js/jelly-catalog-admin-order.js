(function ($) {
  "use strict";

  const ajax = window.jc_ajax || {};
  const $body = $("body");
  const defaultMessages = {
    saving: "Saving order...",
    saved: "Order updated.",
    failed: "Failed to update order.",
    unavailable: "The sorting API is unavailable.",
  };

  /**
   * 判断当前页面是否提供 jelly-core 的 the-list 拖拽排序 API。
   *
   * @return {boolean} API 是否可用。
   */
  function hasTheListSorterApi() {
    return typeof window.jellyCore?.createTheListSorter === "function";
  }

  /**
   * 获取 WordPress AJAX 地址，优先使用插件本地化配置。
   *
   * @return {string} AJAX 请求地址。
   */
  function getAjaxUrl() {
    return ajax.ajax_url || window.ajaxurl || "";
  }

  /**
   * 从 AJAX 响应中解析可展示的提示文本。
   *
   * @param {Object} response AJAX 响应对象。
   * @param {string} fallback 默认提示文本。
   * @return {string} 最终提示文本。
   */
  function getResponseMessage(response, fallback) {
    if (typeof response?.data === "string" && response.data) {
      return response.data;
    }

    if (typeof response?.data?.message === "string" && response.data.message) {
      return response.data.message;
    }

    return fallback;
  }

  /**
   * 使用 jelly-core 的消息组件展示操作结果。
   *
   * @param {string} message 提示文本。
   * @param {string} type 提示类型。
   * @return {void}
   */
  function showMessage(message, type) {
    if (typeof window.jellyCore?.showMessage === "function") {
      window.jellyCore.showMessage(message, type);
    }
  }

  /**
   * 根据 ID 顺序把 the-list 行恢复到指定位置。
   *
   * @param {jQuery} $list WordPress 列表 tbody。
   * @param {Array<string>} ids 需要恢复的 ID 顺序。
   * @param {string} type 行 ID 前缀类型。
   * @return {void}
   */
  function restoreOrder($list, ids, type) {
    if (!$list.length || !Array.isArray(ids) || !ids.length) {
      return;
    }

    ids.forEach((id) => {
      const row = document.getElementById(type + "-" + id);

      if (row && row.parentNode === $list[0]) {
        $list.append(row);
      }
    });
  }

  /**
   * 捕获当前 the-list 中完整的行 ID 顺序。
   *
   * @param {jQuery} $list WordPress 列表 tbody。
   * @param {string} type 行 ID 前缀类型。
   * @return {Array<string>} 当前完整 ID 顺序。
   */
  function captureCurrentOrder($list, type) {
    const prefix = type + "-";

    return $list
      .children("tr")
      .map((index, row) => {
        if (typeof row.id !== "string" || row.id.indexOf(prefix) !== 0) {
          return null;
        }

        return row.id.slice(prefix.length);
      })
      .get()
      .filter(Boolean);
  }

  /**
   * 提交排序数据到 WordPress AJAX 接口。
   *
   * @param {Object} payload jelly-core 生成的排序变更数据。
   * @param {Object} sorter jelly-core 排序控制器实例。
   * @param {Object} config 当前排序页面配置。
   * @return {void}
   */
  function saveOrder(payload, sorter, config) {
    const ajaxUrl = getAjaxUrl();

    if (!ajaxUrl || !ajax.nonce) {
      restoreOrder(payload.$list, config.stableIds, payload.type);
      showMessage(defaultMessages.failed, "error");
      return;
    }

    sorter.disable();
    showMessage(defaultMessages.saving, "info");

    $.ajax({
      url: ajaxUrl,
      type: "POST",
      dataType: "json",
      data: {
        action: config.action,
        order: payload.serialized,
        nonce: ajax.nonce,
      },
    })
      .done((response) => {
        if (response?.success) {
          config.stableIds = captureCurrentOrder(payload.$list, payload.type);
          showMessage(
            getResponseMessage(response, defaultMessages.saved),
            "success",
          );
          return;
        }

        restoreOrder(payload.$list, config.stableIds, payload.type);
        showMessage(
          getResponseMessage(response, defaultMessages.failed),
          "error",
        );
      })
      .fail(() => {
        restoreOrder(payload.$list, config.stableIds, payload.type);
        showMessage(defaultMessages.failed, "error");
      })
      .always(() => {
        sorter.enable();
      });
  }

  /**
   * 使用指定配置初始化 WordPress the-list 拖拽排序。
   *
   * @param {Object} config 当前排序页面配置。
   * @return {Object|null} jelly-core 排序控制器实例或空值。
   */
  function initTheListOrder(config) {
    const $list = $("#the-list");

    if (!$list.length || !$list.find(config.handle).length) {
      return null;
    }

    if (!hasTheListSorterApi()) {
      showMessage(defaultMessages.unavailable, "error");
      return null;
    }

    const sorter = window.jellyCore.createTheListSorter({
      type: config.type,
      list: $list[0],
      handle: config.handle,
      jQuery: $,
    });

    config.stableIds = captureCurrentOrder($list, config.type);

    sorter.onChange((payload, sorterInstance) => {
      saveOrder(payload, sorterInstance, config);
    });

    return sorter;
  }

  /**
   * 初始化产品列表页的文章排序。
   *
   * @return {Object|null} jelly-core 排序控制器实例或空值。
   */
  function initPostOrder() {
    if (!$body.hasClass("edit-php") || !$body.hasClass("post-type-product")) {
      return null;
    }

    return initTheListOrder({
      type: "post",
      action: "jelly_catalog_update_post_order",
      handle: ".jelly-order-handle",
    });
  }

  /**
   * 初始化产品分类列表页的术语排序。
   *
   * @return {Object|null} jelly-core 排序控制器实例或空值。
   */
  function initTermOrder() {
    if (
      !$body.hasClass("edit-tags-php") ||
      !$body.hasClass("taxonomy-product_cat")
    ) {
      return null;
    }

    return initTheListOrder({
      type: "tag",
      action: "jelly_catalog_update_term_order",
      handle: ".jelly-order-handle",
    });
  }

  $(document).ready(() => {
    initPostOrder();
    initTermOrder();
  });
})(jQuery);
