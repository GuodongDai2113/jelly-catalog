(function ($) {
  "use strict";

  /**
   * Repeater 功能模块
   * 负责处理 FAQ 和属性等重复字段的增删操作
   */
  class JellyCatalogRepeater {
    constructor() {
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
      this.createModal();
      this.bindBulkCreateEvents();
    }

    /**
     * 显示批量创建模态框
     */
    showBulkCreateModal(button) {
      this.currentWrapper = button.closest('.jc-repeater-wrapper');
      $('#bulk-create-modal').show();
    }

    /**
     * 创建批量创建FAQ模态框
     */
    createModal() {
      if ($('#bulk-create-modal').length > 0) return;

      const modalHtml = `
        <div id="bulk-create-modal" class="bulk-create-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
          <div class="modal-content" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 5px; min-width: 500px; max-width: 80%; max-height: 80%; overflow-y: auto;">
            <h3>Bulk Create Items</h3>
            <p style="margin-bottom: 15px; color: #666;">Paste your content. Each line will be treated as a separate item. For FAQ, each pair of lines will be treated as Question + Answer.</p>
            <textarea id="bulk-item-text" style="width: 100%; height: 200px; margin-bottom: 15px;" placeholder="Enter content here...\nFor FAQ: First line is Question, second line is Answer\nQuestion 1\nAnswer 1\nQuestion 2\nAnswer 2\n...\n\nFor Attributes: First line is Name, second line is Value\nName 1\nValue 1\nName 2\nValue 2\n..."></textarea>
            <div class="modal-buttons" style="text-align: right;">
              <button type="button" class="button button-secondary" id="cancel-bulk-create" style="margin-right: 10px;">Cancel</button>
              <button type="button" class="button button-primary" id="confirm-bulk-create">Create Items</button>
            </div>
          </div>
        </div>
      `;

      $('body').append(modalHtml);
    }

    /**
     * 绑定批量创建事件
     */
    bindBulkCreateEvents() {
      // 关闭模态框
      $(document).on('click', '#cancel-bulk-create, #bulk-create-modal', (e) => {
        if (e.target.id === 'cancel-bulk-create' || e.target.id === 'bulk-create-modal') {
          $('#bulk-create-modal').hide();
          $('#bulk-item-text').val('');
        }
      });

      // 确认创建
      $(document).on('click', '#confirm-bulk-create', (e) => {
        e.preventDefault();
        this.processBulkCreate();
      });

      // 阻止点击模态框内容区域时关闭
      $(document).on('click', '.modal-content', (e) => {
        e.stopPropagation();
      });
    }

    /**
     * 处理批量创建FAQ
     */
    processBulkCreate() {
      const text = $('#bulk-item-text').val().trim();
      if (!text) {
        alert('Please enter content.');
        return;
      }

      const lines = text.split('\n').filter(line => line.trim() !== '');
      const faqItems = [];

      // 每两行为一组（问题+答案）
      for (let i = 0; i < lines.length; i += 2) {
        const question = lines[i]?.trim() || '';
        const answer = lines[i + 1]?.trim() || '';
        
        if (question || answer) {
          faqItems.push({
            name: question,
            value: answer
          });
        }
      }

      if (faqItems.length === 0) {
        alert('No valid items found.');
        return;
      }

      // 添加到repeater
      const $wrapper = this.currentWrapper;
      const key = $wrapper.data('key');

      faqItems.forEach(item => {
        const itemCount = $wrapper.find('.repeater-item').length;
        const $newItem = this.buildRepeaterItem(key, itemCount + 1);
        
        if ($newItem) {
          // 设置值
          $newItem.find('.repeater-item__key-input').val(item.name);
          $newItem.find('.repeater-item__value-input').val(item.value);
          
          $wrapper.find('.repeater-add-wrapper').before($newItem);
        }
      });

      // 关闭模态框
      $('#bulk-create-modal').hide();
      $('#bulk-item-text').val('');
      this.currentWrapper = null;
      
      alert(`Successfully created ${faqItems.length} items.`);
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

      // 批量创建FAQ事件
      $(document).on('click', '.bulk-create', (e) => {
        e.preventDefault();
        this.showBulkCreateModal($(e.currentTarget));
      });
    }

    /**
     * 为旧数据项补齐删除按钮
     */
    addDeleteButtons(repeaterWrapper) {
      repeaterWrapper.find(".repeater-item .repeater-item-header").each((_, item) => {
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
      if (repeaterWrapper.find(".repeater-add-new").length) return;

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
        <div class="repeater-item-header">
          <span class="item-title">${index}. ${key.replace('_', ' ')}</span>
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
