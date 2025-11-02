(function ($) {
  "use strict";

  /**
   * Repeater 功能模块
   * 负责处理 FAQ 和属性等重复字段的增删操作
   */
  class JellyCatalogRepeater {
    constructor() {
      this.init();
    }

    /**
     * 初始化 Repeater 功能
     */
    init() {
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

    /**
     * 为旧数据项补齐删除按钮
     */
    addDeleteButtons(repeaterWrapper) {
      repeaterWrapper.find(".repeater-item").each((_, item) => {
        const $item = $(item);
        if ($item.find(".remove-repeater").length) return;

        $item.append(`
          <button type="button" class="button remove-repeater" title="Delete item">
            <span class="dashicons dashicons-trash"></span>
          </button>
        `);
      });
    }

    /**
     * 确保容器内存在"新增"按钮
     */
    ensureAddButton(repeaterWrapper) {
      if (repeaterWrapper.find(".repeater-add-new").length) return;

      repeaterWrapper.append(`
        <div class="repeater-add-wrapper">
          <button type="button" class="button button-secondary repeater-add-new">
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
      const $newItem = this.buildRepeaterItem(key, itemCount + 1);

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
        product_cat_faqs: () => this.buildCategoryFaqItem(index),
      };

      const builder = builders[key];
      if (!builder) return null;

      const $item = $(`
        <div class="repeater-item">
          <span class="item-number">No.${index}</span>
        </div>
      `);

      builder().appendTo($item);

      $item.append(`
        <button type="button" class="button remove-repeater" title="Delete item">
          <span class="dashicons dashicons-trash"></span>
        </button>
      `);

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
     * 构建分类 FAQ 字段结构
     */
    buildCategoryFaqItem(index) {
      return $(`
        <div>
          <div class="repeater-item__key">
            <label for="product_cat_faqs[${index}][name]">Question:</label>
            <input class="repeater-item__key-input" type="text" id="product_cat_faqs[${index}][name]" name="product_cat_faqs[${index}][name]" value="" />
          </div>
          <div class="repeater-item__value">
            <label for="product_cat_faqs[${index}][value]">Answer:</label>
            <textarea class="repeater-item__value-input" id="product_cat_faqs[${index}][value]" name="product_cat_faqs[${index}][value]"></textarea>
          </div>
        </div>
      `);
    }
  }

  $(document).ready(() => {
    window.JellyCatalogRepeater = new JellyCatalogRepeater();
  });
})(jQuery);
