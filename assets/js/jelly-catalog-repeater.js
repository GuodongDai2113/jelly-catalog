(function ($) {
  "use strict";

  const SELECTORS = {
    wrapper: ".jc-repeater-wrapper",
    repeaterItem: ".repeater-item",
    repeaterAddWrapper: ".repeater-add-wrapper",
    repeaterAddNew: ".repeater-add-new",
    repeaterRemove: ".repeater-item .remove-repeater",
    bulkCreate: ".bulk-create",
    bulkCreateText: ".jc-bulk-item-text",
    keyInput: ".repeater-item__key-input",
    valueInput: ".repeater-item__value-input",
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
        title: "Bulk Create Items",
        description:
          "Paste your content. Each line will be treated as a separate item. For FAQ, each pair of lines will be treated as Question + Answer.",
        bodyHtml: `
          <textarea class="jc-bulk-item-text" style="width: 100%; height: 200px; margin-bottom: 15px;" placeholder="Enter content here...\nFor FAQ: First line is Question, second line is Answer\nQuestion 1\nAnswer 1\nQuestion 2\nAnswer 2\n...\n\nFor Attributes: First line is Name, second line is Value\nName 1\nValue 1\nName 2\nValue 2\n..."></textarea>
        `,
        confirmText: "Create Items",
        cancelText: "Cancel",
        onConfirm: () => this.processBulkCreate(),
        onClose: () => this.resetBulkCreateState(),
      });
    }

    /**
     * 显示批量创建模态框
     */
    showBulkCreateModal(button) {
      this.currentWrapper = button.closest(SELECTORS.wrapper);
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
        alert("Please enter content.");
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
        alert("No valid items found.");
        return false;
      }

      // 添加到repeater
      const $wrapper = this.currentWrapper;
      const key = $wrapper.data("key");

      items.forEach((item) => {
        const itemCount = $wrapper.find(SELECTORS.repeaterItem).length;
        const $newItem = this.buildRepeaterItem(key, itemCount + 1);

        if ($newItem) {
          // 设置值
          $newItem.find(SELECTORS.keyInput).val(item.name);
          $newItem.find(SELECTORS.valueInput).val(item.value);

          $wrapper.find(SELECTORS.repeaterAddWrapper).before($newItem);
        }
      });

      $textArea.val("");
      this.currentWrapper = null;

      alert(`Successfully created ${items.length} items.`);
      return true;
    }

    resetBulkCreateState() {
      const $textArea = this.getBulkCreateTextArea();
      if ($textArea.length) {
        $textArea.val("");
      }
      this.currentWrapper = null;
    }

    getBulkCreateTextArea() {
      if (!this.bulkCreateModal) {
        return $(SELECTORS.bulkCreateText);
      }
      return this.bulkCreateModal.find(SELECTORS.bulkCreateText);
    }

    /**
     * 为已有 repeater 添加操作按钮
     */
    prepareExistingRepeaters() {
      $(SELECTORS.wrapper).each((_, wrapper) => {
        const $wrapper = $(wrapper);
        this.addDeleteButtons($wrapper);
        this.ensureAddButton($wrapper);
      });
    }

    /**
     * 绑定 repeater 的增删事件
     */
    bindRepeaterEvents() {
      $(document).on("click", SELECTORS.repeaterRemove, (e) => {
        e.preventDefault();
        $(e.currentTarget).closest(SELECTORS.repeaterItem).remove();
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
          <div type="button" class="remove-repeater" title="Delete item">
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

      repeaterWrapper.append(`
        <div class="repeater-add-wrapper">
          <button type="button" class="button button-secondary repeater-add-new">
            <span class="dashicons dashicons-plus"></span>
            Add New Item
          </button>
          <button type="button" class="button button-secondary bulk-create" style="margin-left: 10px;" title="Bulk Create Items from Text">
            <span class="dashicons dashicons-editor-ul"></span>
            Bulk Create
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
      const $newItem = this.buildRepeaterItem(key, itemCount + 1);

      if (!$newItem) return;

      repeaterWrapper.find(SELECTORS.repeaterAddWrapper).before($newItem);
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
        <div class="repeater-item-header">
          <span class="item-title">${index}. ${key.replace(/_/g, " ")}</span>
          <div type="button" class="remove-repeater" title="Delete item">
            <span class="dashicons dashicons-no-alt"></span>
          </div>
        </div>
      `);

      builder().appendTo($item);
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
