(function ($) {
  "use strict";

  const SELECTORS = {
    wrapper: ".jc-repeater-wrapper",
    repeaterItem: ".repeater-item",
    repeaterItemHeader: ".repeater-item-header",
    repeaterAddWrapper: ".repeater-add-wrapper",
    repeaterAddNew: ".repeater-add-new",
    repeaterRemove: ".repeater-item .remove-repeater",
    bulkCreate: ".bulk-create",
    bulkCreateText: ".jc-bulk-item-text",
    keyInput: ".repeater-item__key-input",
    valueInput: ".repeater-item__value-input",
  };

  /**
   * Repeater 类型配置
   * 负责为 FAQ、属性等不同场景提供独立的布局与文案
   */
  const REPEATER_TYPES = {
    product_faqs: {
      title: () => jc_product_i18n.product_faqs || "Product FAQ",
      itemLabel: () => jc_product_i18n.product_faq_singular || "FAQ",
      wrapperClass: "jc-repeater-wrapper--faq",
      itemClass: "repeater-item--faq",
      bodyClass: "repeater-item__body repeater-item__body--faq",
      keyFieldClass: "repeater-item__field repeater-item__field--question",
      valueFieldClass: "repeater-item__field repeater-item__field--answer",
      addButtonText: () => jc_product_i18n.add_new_faq_btn || "Add FAQ",
      bulkPlaceholder: () =>
        jc_product_i18n.bulk_create_faq_placeholder ||
        "Question 1\nAnswer 1\nQuestion 2\nAnswer 2",
    },
    product_cat_faqs: {
      title: () => jc_product_i18n.product_cat_faqs || "Category FAQ",
      itemLabel: () => jc_product_i18n.category_faq_singular || "FAQ",
      wrapperClass: "jc-repeater-wrapper--faq",
      itemClass: "repeater-item--faq",
      bodyClass: "repeater-item__body repeater-item__body--faq",
      keyFieldClass: "repeater-item__field repeater-item__field--question",
      valueFieldClass: "repeater-item__field repeater-item__field--answer",
      addButtonText: () => jc_product_i18n.add_new_faq_btn || "Add FAQ",
      bulkPlaceholder: () =>
        jc_product_i18n.bulk_create_faq_placeholder ||
        "Question 1\nAnswer 1\nQuestion 2\nAnswer 2",
    },
    product_attributes: {
      title: () => jc_product_i18n.product_attributes || "Product Attributes",
      itemLabel: () =>
        jc_product_i18n.product_attribute_singular || "Attribute",
      wrapperClass: "jc-repeater-wrapper--attributes",
      itemClass: "repeater-item--attributes",
      bodyClass: "repeater-item__body repeater-item__body--attributes",
      keyFieldClass: "repeater-item__field repeater-item__field--name",
      valueFieldClass: "repeater-item__field repeater-item__field--value",
      addButtonText: () =>
        jc_product_i18n.add_new_attribute_btn || "Add Attribute",
      bulkPlaceholder: () =>
        jc_product_i18n.bulk_create_attribute_placeholder ||
        "Name 1\nValue 1\nName 2\nValue 2",
    },
  };

  /**
   * Repeater 功能模块
   * 负责处理 FAQ 和属性等重复字段的增删操作
   */
  class JellyCatalogRepeater {
    constructor() {
      this.currentWrapper = null;
      this.bulkCreateModal = null;
      this.init();
      this.initBulkCreateModal();
    }

    /**
     * 调用新版 jelly-core 通知接口，并在缺失时降级为浏览器提示。
     *
     * @param {string} message 通知文案。
     * @param {string} type 通知类型。
     * @returns {void}
     */
    showNotice(message, type = "info") {
      if (typeof window.jellyShowNotice === "function") {
        window.jellyShowNotice(message, type);
        return;
      }

      window.alert(message);
    }

    /**
     * 初始化 Repeater 功能
     */
    init() {
      this.prepareExistingRepeaters();
      this.bindRepeaterEvents();
    }

    /**
     * 初始化批量创建FAQ模态框
     */
    initBulkCreateModal() {
      if (typeof window.JellyModal !== "function") {
        return;
      }

      this.bulkCreateModal = new window.JellyModal({
        id: "bulk-create-modal",
        title: jc_product_i18n.bulk_create_title || "Bulk Create Items",
        bodyHtml: `
          <textarea class="jc-bulk-item-text" style="width: 100%; height: 200px; margin-bottom: 15px;" placeholder="${
            jc_product_i18n.bulk_create_placeholder ||
            "Enter content here...\nFor FAQ: First line is Question, second line is Answer\nQuestion 1\nAnswer 1\nQuestion 2\nAnswer 2...\n\nFor Attributes: First line is Name, second line is Value\nName 1\nValue 1\nName 2\nValue 2..."
          }"></textarea>
        `,
        confirmText: jc_product_i18n.create_items_btn || "Create Items",
        cancelText: jc_product_i18n.cancel_btn || "Cancel",
        onOpen: () => this.focusBulkCreateTextArea(),
        onConfirm: () => this.processBulkCreate(),
        onClose: () => this.resetBulkCreateState(),
      });
    }

    /**
     * 显示批量创建模态框
     */
    showBulkCreateModal(button) {
      this.currentWrapper = button.closest(SELECTORS.wrapper);
      this.syncBulkCreateModal();

      if (this.bulkCreateModal) {
        this.bulkCreateModal.open();
      }
    }

    /**
     * 处理批量创建FAQ
     */
    processBulkCreate() {
      const $textArea = this.getBulkCreateTextArea();
      const text = $textArea.val().trim();
      if (!text) {
        this.showNotice(
          jc_product_i18n.no_content_error || "Please enter content.",
          "error"
        );
        return false;
      }

      const lines = text.split("\n").filter((line) => line.trim() !== "");
      const items = [];

      // 每两行为一组（问题+答案）
      for (let i = 0; i < lines.length; i += 2) {
        const question = lines[i]?.trim() || "";
        const answer = lines[i + 1]?.trim() || "";

        if (question || answer) {
          items.push({
            name: question,
            value: answer,
          });
        }
      }

      if (items.length === 0) {
        this.showNotice(
          jc_product_i18n.no_valid_items_error || "No valid items found.",
          "error"
        );
        return false;
      }

      // 添加到repeater
      const $wrapper = this.currentWrapper;
      const key = $wrapper.data("key");

      items.forEach((item) => {
        const itemCount = $wrapper.find(SELECTORS.repeaterItem).length;
        const $newItem = this.buildRepeaterItem($wrapper, key, itemCount + 1);

        if ($newItem) {
          // 设置值
          $newItem.find(SELECTORS.keyInput).val(item.name);
          $newItem.find(SELECTORS.valueInput).val(item.value);

          $wrapper.find(SELECTORS.repeaterAddWrapper).before($newItem);
        }
      });

      this.refreshRepeaterItems($wrapper);

      $textArea.val("");
      this.currentWrapper = null;

      this.showNotice(
        (
          jc_product_i18n.success_created_items ||
          "Successfully created {count} items."
        ).replace("{count}", items.length),
        "success"
      );
      return true;
    }

    resetBulkCreateState() {
      const $textArea = this.getBulkCreateTextArea();
      if ($textArea.length) {
        $textArea.val("");
      }
      this.currentWrapper = null;
    }

    /**
     * 聚焦当前模态框中的批量输入框。
     *
     * @returns {void}
     */
    focusBulkCreateTextArea() {
      const $textArea = this.getBulkCreateTextArea();
      if ($textArea.length) {
        $textArea.trigger("focus");
      }
    }

    /**
     * 获取批量创建输入框的 jQuery 包装对象。
     *
     * 新版 jelly-core 的 `find()` 返回原生 DOM 节点，这里统一包装成 jQuery，
     * 避免后续 `.val()`、`.trigger()` 等调用失效。
     *
     * @returns {jQuery}
     */
    getBulkCreateTextArea() {
      if (!this.bulkCreateModal) {
        return $(SELECTORS.bulkCreateText);
      }

      const textAreaElement = this.bulkCreateModal.find(
        SELECTORS.bulkCreateText
      );

      return $(textAreaElement || []);
    }

    /**
     * 获取指定 repeater 的类型配置
     *
     * @param {jQuery} repeaterWrapper 当前容器。
     * @returns {object}
     */
    getRepeaterConfig(repeaterWrapper) {
      const key = repeaterWrapper.data("key");

      return REPEATER_TYPES[key] || {};
    }

    /**
     * 返回当前类型的批量导入占位说明
     *
     * @param {jQuery} repeaterWrapper 当前容器。
     * @returns {string}
     */
    getBulkPlaceholder(repeaterWrapper) {
      const config = this.getRepeaterConfig(repeaterWrapper);

      if (typeof config.bulkPlaceholder === "function") {
        return config.bulkPlaceholder();
      }

      return jc_product_i18n.bulk_create_placeholder || "Enter content here...";
    }

    /**
     * 同步批量导入弹窗文案，使 FAQ 与属性各自匹配
     */
    syncBulkCreateModal() {
      const $textArea = this.getBulkCreateTextArea();
      if (!$textArea.length || !this.currentWrapper) {
        return;
      }

      $textArea.attr(
        "placeholder",
        this.getBulkPlaceholder(this.currentWrapper)
      );
    }

    /**
     * 为已有 repeater 添加操作按钮
     */
    prepareExistingRepeaters() {
      $(SELECTORS.wrapper).each((_, wrapper) => {
        const $wrapper = $(wrapper);
        this.decorateRepeaterWrapper($wrapper);
        this.addDeleteButtons($wrapper);
        this.ensureAddButton($wrapper);
      });
    }

    /**
     * 为现有 repeater 容器和条目补齐类型类名
     *
     * @param {jQuery} repeaterWrapper 当前容器。
     * @returns {void}
     */
    decorateRepeaterWrapper(repeaterWrapper) {
      const config = this.getRepeaterConfig(repeaterWrapper);

      if (config.wrapperClass) {
        repeaterWrapper.addClass(config.wrapperClass);
      }

      repeaterWrapper.find(SELECTORS.repeaterItem).each((index, item) => {
        this.decorateRepeaterItem(repeaterWrapper, $(item), index + 1);
      });
    }

    /**
     * 为单个条目补齐类型类名和标题
     *
     * @param {jQuery} repeaterWrapper 当前容器。
     * @param {jQuery} repeaterItem 当前条目。
     * @param {number} index 当前序号。
     * @returns {void}
     */
    decorateRepeaterItem(repeaterWrapper, repeaterItem, index) {
      const config = this.getRepeaterConfig(repeaterWrapper);
      const itemLabel =
        typeof config.itemLabel === "function"
          ? config.itemLabel()
          : repeaterWrapper.data("key");
      const $header = repeaterItem.find(SELECTORS.repeaterItemHeader).first();

      if (config.itemClass) {
        repeaterItem.addClass(config.itemClass);
      }

      if ($header.length) {
        $header.find(".item-title").text(`${index}. ${itemLabel}`);
      }

      if (!repeaterItem.find(".repeater-item__body").length) {
        const bodyClass =
          typeof config.bodyClass === "string" ? config.bodyClass : "";
        const $body = $(`<div class="${bodyClass}"></div>`);

        repeaterItem
          .children()
          .not(SELECTORS.repeaterItemHeader)
          .appendTo($body);

        repeaterItem.append($body);
      }

      const $keyField = repeaterItem.find(".repeater-item__key").first();
      const $valueField = repeaterItem.find(".repeater-item__value").first();

      if ($keyField.length && config.keyFieldClass) {
        $keyField.addClass(config.keyFieldClass);
      }

      if ($valueField.length && config.valueFieldClass) {
        $valueField.addClass(config.valueFieldClass);
      }
    }

    /**
     * 重新整理容器内的条目标题顺序
     *
     * @param {jQuery} repeaterWrapper 当前容器。
     * @returns {void}
     */
    refreshRepeaterItems(repeaterWrapper) {
      repeaterWrapper.find(SELECTORS.repeaterItem).each((index, item) => {
        this.decorateRepeaterItem(repeaterWrapper, $(item), index + 1);
      });
    }

    /**
     * 绑定 repeater 的增删事件
     */
    bindRepeaterEvents() {
      $(document).on("click", SELECTORS.repeaterRemove, (e) => {
        e.preventDefault();
        const $wrapper = $(e.currentTarget).closest(SELECTORS.wrapper);
        $(e.currentTarget).closest(SELECTORS.repeaterItem).remove();
        this.refreshRepeaterItems($wrapper);
      });

      $(document).on("click", SELECTORS.repeaterAddNew, (e) => {
        e.preventDefault();
        const $wrapper = $(e.currentTarget).closest(SELECTORS.wrapper);
        this.addNewRepeaterItem($wrapper);
      });

      // 批量创建FAQ事件
      $(document).on("click", SELECTORS.bulkCreate, (e) => {
        e.preventDefault();
        this.showBulkCreateModal($(e.currentTarget));
      });
    }

    /**
     * 为旧数据项补齐删除按钮
     */
    addDeleteButtons(repeaterWrapper) {
      repeaterWrapper
        .find(".repeater-item .repeater-item-header")
        .each((_, item) => {
          const $item = $(item);
          if ($item.find(".remove-repeater").length) return;

          $item.append(`
          <div type="button" class="remove-repeater" title="${
            jc_product_i18n.delete_item_tooltip || "Delete item"
          }">
            <span class="dashicons dashicons-no-alt"></span>
          </div>
        `);
        });
    }

    /**
     * 确保容器内存在"新增"按钮
     */
    ensureAddButton(repeaterWrapper) {
      if (repeaterWrapper.find(SELECTORS.repeaterAddNew).length) return;

      const config = this.getRepeaterConfig(repeaterWrapper);
      const addButtonText =
        typeof config.addButtonText === "function"
          ? config.addButtonText()
          : jc_product_i18n.add_new_item_btn || "Add New Item";

      repeaterWrapper.append(`
        <div class="repeater-add-wrapper">
          <button type="button" class="button button-secondary repeater-add-new">
            <span class="dashicons dashicons-plus"></span>
            ${addButtonText}
          </button>
          <button type="button" class="button button-secondary bulk-create" style="margin-left: 10px;" title="${
            jc_product_i18n.bulk_create_tooltip || "Bulk Create Items from Text"
          }">
            <span class="dashicons dashicons-editor-ul"></span>
            ${jc_product_i18n.bulk_create_btn || "Bulk Create"}
          </button>
        </div>
      `);
    }

    /**
     * 新增一个 repeater 条目
     */
    addNewRepeaterItem(repeaterWrapper) {
      const key = repeaterWrapper.data("key");
      const itemCount = repeaterWrapper.find(SELECTORS.repeaterItem).length;
      const $newItem = this.buildRepeaterItem(
        repeaterWrapper,
        key,
        itemCount + 1
      );

      if (!$newItem) return;

      repeaterWrapper.find(SELECTORS.repeaterAddWrapper).before($newItem);
      this.refreshRepeaterItems(repeaterWrapper);
    }

    /**
     * 构建指定类型的 repeater DOM
     */
    buildRepeaterItem(repeaterWrapper, key, index) {
      const builders = {
        product_faqs: () => this.buildFaqItem(index),
        product_attributes: () => this.buildAttributeItem(index),
        product_cat_faqs: () => this.buildCategoryFaqItem(index),
      };
      const config = this.getRepeaterConfig(repeaterWrapper);
      const itemLabel =
        typeof config.itemLabel === "function"
          ? config.itemLabel()
          : key.replace(/_/g, " ");
      const bodyClass =
        typeof config.bodyClass === "string" ? config.bodyClass : "";

      const builder = builders[key];
      if (!builder) return null;
      const $item = $(`
        <div class="repeater-item ${config.itemClass || ""}">
        <div class="repeater-item-header">
          <span class="item-title">${index}. ${itemLabel}</span>
          <div type="button" class="remove-repeater" title="${
            jc_product_i18n.delete_item_tooltip || "Delete item"
          }">
            <span class="dashicons dashicons-no-alt"></span>
          </div>
        </div>
        <div class="${bodyClass}"></div>
      `);

      builder().appendTo($item.find(".repeater-item__body"));
      return $item;
    }

    /**
     * 构建 FAQ 字段结构
     */
    buildFaqItem(index) {
      return $(`
        <div class="repeater-item__field-group">
          <div class="repeater-item__key repeater-item__field repeater-item__field--question">
            <label for="product_faqs[${index}][name]">${
        jc_product_i18n.faq_question_label || "Question"
      }:</label>
            <input class="repeater-item__key-input" type="text" id="product_faqs[${index}][name]" name="product_faqs[${index}][name]" value="" />
          </div>
          <div class="repeater-item__value repeater-item__field repeater-item__field--answer">
            <label for="product_faqs[${index}][value]">${
        jc_product_i18n.faq_answer_label || "Answer"
      }:</label>
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
        <div class="repeater-item__field-group">
          <div class="repeater-item__key repeater-item__field repeater-item__field--name">
            <label for="product_attributes[${index}][name]">${
        jc_product_i18n.attribute_name_label || "Name"
      }:</label>
            <input class="repeater-item__key-input" type="text" id="product_attributes[${index}][name]" name="product_attributes[${index}][name]" value="" />
          </div>
          <div class="repeater-item__value repeater-item__field repeater-item__field--value">
            <label for="product_attributes[${index}][value]">${
        jc_product_i18n.attribute_value_label || "Value"
      }:</label>
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
        <div class="repeater-item__field-group">
          <div class="repeater-item__key repeater-item__field repeater-item__field--question">
            <label for="product_cat_faqs[${index}][name]">${
        jc_product_i18n.faq_question_label || "Question"
      }:</label>
            <input class="repeater-item__key-input" type="text" id="product_cat_faqs[${index}][name]" name="product_cat_faqs[${index}][name]" value="" />
          </div>
          <div class="repeater-item__value repeater-item__field repeater-item__field--answer">
            <label for="product_cat_faqs[${index}][value]">${
        jc_product_i18n.faq_answer_label || "Answer"
      }:</label>
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
