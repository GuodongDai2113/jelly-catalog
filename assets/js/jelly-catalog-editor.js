(function ($) {
  "use strict";

  /**
   * 产品编辑器类
   * 用于重新组织产品编辑页面的 metaboxes 为标签页形式
   */
  class ProductEditor {
    /**
     * 构造函数
     * 初始化产品编辑器功能
     */
    constructor() {
      // 仅在产品编辑页面启用此功能
      if (!$(".post-type-product").length) {
        return;
      }
      console.log("Jelly Catalog Editor Initialized");

      // 初始化所有功能模块
      this.resetProductEditor();

      // 初始化字符计数器
      this.initCharacterCount();

      // 初始化 repeater 功能
      this.initRepeater();

      // 初始化产品画册功能
      this.initProductGallery();
    }

    /**
     * 重置产品编辑器布局
     * 将默认的 metaboxes 布局转换为标签页布局
     */
    resetProductEditor() {
      // 定位标题区域，用于插入标签页容器
      const titlediv = $("#poststuff #titlediv");

      // 定义需要转换为标签页的 metaboxes 列表
      const metaboxs = [
        "postexcerpt", // 产品短描述
        "postdivrich", // 产品详细描述
        "product_catdiv", // 产品分类
        "tagsdiv-product_tag", // 产品标签
        "acf-group_product_field", // ACF 自定义字段组
        "product_faq_metabox", // 产品 FAQ
        "product_attributes_metabox", // 产品属性
        "product_videourl_metabox", // 产品视频链接
        "rank_math_metabox", // Rank Math SEO 插件 metabox
      ];

      // 创建标签页容器结构
      const tabContainer = $(`<div class="jc-tabs">
                                <div class="nav-tab-wrapper"></div>
                                <div class="tab-content"></div>
                              </div>`);

      // 将标签页容器插入到标题下方
      titlediv.after(tabContainer);

      // 生成标签页
      this.generateTabs(metaboxs, tabContainer);

      // 绑定标签页切换事件
      this.bindTabEvents();

      // 触发编辑器工具栏相关事件
      $("#wp-content-editor-tools");
    }

    /**
     * 根据 metabox ID 生成标签页
     * @param {Array} metaboxs - metabox ID 数组
     * @param {jQuery} tabContainer - 标签页容器 jQuery 对象
     */
    generateTabs(metaboxs, tabContainer) {
      // 获取导航和内容容器
      const navWrapper = tabContainer.find(".nav-tab-wrapper");
      const contentWrapper = tabContainer.find(".tab-content");

      // 遍历每个 metabox 并创建对应的标签页
      metaboxs.forEach((metaboxId, index) => {
        const metabox = $("#" + metaboxId);

        // 检查 metabox 是否存在
        if (metabox.length) {
          // 设置标签页激活状态（第一个标签页默认激活）
          const isActive = index === 0 ? "nav-tab-active" : "";

          // 获取标签页标题，特殊处理产品描述
          let tabTitle = metabox.find("h2.hndle").text() || metaboxId;
          if (metaboxId === "postdivrich") {
            tabTitle = "Product Description";
          }

          // 创建标签页导航链接
          const tabLink = $(
            `<a href="#" class="nav-tab ${isActive}" data-tab="${metaboxId}">${tabTitle}</a>`
          );
          navWrapper.append(tabLink);

          // 创建标签页内容面板
          const isActiveClass = index === 0 ? "active" : "";
          const tabPane = $(
            `<div class="tab-pane ${isActiveClass}" id="tab-${metaboxId}"></div>`
          );

          let metaboxContent = null;

          // 根据不同 metabox 类型处理内容
          if (metaboxId !== "postdivrich") {
            // 普通 metabox：提取内部内容
            metaboxContent = metabox.find(".inside").children();
          } else {
            // 产品描述 metabox：直接使用整个 metabox
            metaboxContent = metabox;
          }

          // 从帮助标签中获取描述内容并添加到 metabox 内容开头
          const description = $(
            "#tab-panel-" + metaboxId + "_help .edit-description"
          );
          if (description.length) {
            metaboxContent.first().prepend(description);
          }

          // 将内容添加到标签页面板
          tabPane.append(metaboxContent);

          // 将标签页面板添加到内容容器
          contentWrapper.append(tabPane);

          // 移除原始 metabox（产品描述除外）
          if (metaboxId !== "postdivrich") {
            metabox.remove();
          }
        }
      });
      const elementorEditor = $("#elementor-editor");
      const elementorSwitch = $("#elementor-switch-mode");
      if (elementorEditor.length && elementorSwitch.length) {
        const content = $("#tab-postdivrich");
        content.append(elementorSwitch);
        content.append(elementorEditor);
      }
    }

    /**
     * 绑定标签页切换事件
     * 处理用户点击标签页时的交互逻辑
     */
    bindTabEvents() {
      // 为所有标签页导航链接绑定点击事件
      $(".jc-tabs").on("click", ".nav-tab", function (e) {
        e.preventDefault();

        // 获取被点击标签页的 ID
        const tabId = $(this).data("tab");

        // 更新标签页导航的激活状态
        $(this).siblings(".nav-tab").removeClass("nav-tab-active");
        $(this).addClass("nav-tab-active");

        // 显示对应的内容面板，隐藏其他面板
        $(".tab-pane").removeClass("active");
        $("#tab-" + tabId).addClass("active");

        // 特殊处理产品描述编辑器的工具栏宽度
        if (tabId === "postdivrich") {
          // 延迟执行以确保 DOM 更新完成
          setTimeout(() => {
            const editorContainer = $("#wp-content-editor-container");
            const editorTools = $("#wp-content-editor-tools");
            const mceToolbarGrp = $(".mce-toolbar-grp");

            // 如果编辑器容器和工具栏都存在，则调整工具栏宽度
            if (editorContainer.length && editorTools.length) {
              const containerWidth = editorContainer.width();
              editorTools.css("width", containerWidth);
              mceToolbarGrp.css("width", containerWidth);
            }
          }, 0);
        }
      });
    }

    /**
     * 初始化字符计数器功能
     * 为短描述编辑器添加字符计数功能
     */
    initCharacterCount() {
      const self = this;

      // 监听 WordPress 编辑器初始化完成事件
      $(document).on("tinymce-editor-init", function () {
        if (typeof tinymce !== "undefined") {
          const editor = tinymce.get("excerpt");
          if (editor) {
            // 监听编辑器内容变化事件
            editor.on("keyup paste cut", function () {
              const content = editor.getContent({ format: "text" });
              const count = content.length;
              $("#excerpt-character-count").text(count);

              // 根据字符数改变颜色
              self.updateCharacterCountColor(count);
            });
          }
        }
      });

      // 监听文本域变化事件
      $(document).on("keyup paste cut", "#excerpt", function () {
        const count = $(this).val().length;
        $("#excerpt-character-count").text(count);
        self.updateCharacterCountColor(count);
      });

      setTimeout(() => {
        if (typeof tinymce !== "undefined") {
          const editor = tinymce.get("excerpt");
          if (editor) {
            const content = editor.getContent({ format: "text" });
            const count = content.length;
            $("#excerpt-character-count").text(count);
            self.updateCharacterCountColor(count);
          } else {
            // 如果 TinyMCE 未初始化，尝试从 textarea 获取内容
            const $textarea = $("#excerpt");
            if ($textarea.length) {
              const count = $textarea.val().length;
              $("#excerpt-character-count").text(count);
              self.updateCharacterCountColor(count);
            }
          }
        } else {
          // 如果没有 TinyMCE，直接从 textarea 获取内容
          const $textarea = $("#excerpt");
          if ($textarea.length) {
            const count = $textarea.val().length;
            $("#excerpt-character-count").text(count);
            self.updateCharacterCountColor(count);
          }
        }
      }, 1000);
    }

    /**
     * 更新字符计数颜色
     * 根据字符数量设置不同的颜色提示
     * @param {number} count - 字符数量
     */
    updateCharacterCountColor(count) {
      const $countElement = $("#excerpt-character-count");
      if (count > 160) {
        $countElement.css("color", "#ff4d4f");
      } else if (count > 130) {
        $countElement.css("color", "#faad14");
      } else {
        $countElement.css("color", "#52c41a");
      }
    }

    /**
     * 初始化 repeater 功能
     * 为 FAQ 和属性等 repeater 字段添加新增和删除功能
     */
    initRepeater() {
      const self = this;

      // 为现有的 repeater 项目添加删除按钮
      $(".jc-repeater-wrapper").each(function () {
        self.addDeleteButton($(this));
      });

      // 为 repeater 容器添加新增按钮
      $(".jc-repeater-wrapper").each(function () {
        self.addAddButton($(this));
      });

      // 绑定删除按钮事件
      $(document).on("click", ".repeater-item .repeater-delete", function (e) {
        e.preventDefault();
        const repeaterItem = $(this).closest(".repeater-item");
        repeaterItem.remove();
        // self.updateRepeaterItemNumbers();
      });

      // 绑定新增按钮事件
      $(document).on("click", ".repeater-add-new", function (e) {
        e.preventDefault();
        const repeaterWrapper = $(this).closest(".jc-repeater-wrapper");
        self.addNewItem(repeaterWrapper);
      });
    }

    /**
     * 为 repeater 项目添加删除按钮
     * @param {jQuery} repeaterWrapper - repeater 容器
     */
    addDeleteButton(repeaterWrapper) {
      repeaterWrapper.find(".repeater-item").each(function () {
        // 如果还没有删除按钮，则添加一个
        if ($(this).find(".repeater-delete").length === 0) {
          const deleteButton =
            $(`<button type="button" class="button repeater-delete" title="Delete item">
                <span class="dashicons dashicons-trash"></span>
            </button>`);
          $(this).append(deleteButton);
        }
      });
    }

    /**
     * 为 repeater 容器添加新增按钮
     * @param {jQuery} repeaterWrapper - repeater 容器
     */
    addAddButton(repeaterWrapper) {
      // 如果还没有新增按钮，则添加一个
      if (repeaterWrapper.find(".repeater-add-new").length === 0) {
        const addButton = $(`<div class="repeater-add-wrapper">
            <button type="button" class="button button-primary repeater-add-new">
                <span class="dashicons dashicons-plus"></span>
                Add New Item
            </button>
        </div>`);
        repeaterWrapper.append(addButton);
      }
    }

    /**
     * 添加新的 repeater 项目
     * @param {jQuery} repeaterWrapper - repeater 容器
     */
    addNewItem(repeaterWrapper) {
      const key = repeaterWrapper.data("key");
      const keyName = repeaterWrapper.data("key-name");
      const itemCount = repeaterWrapper.find(".repeater-item").length;

      // 创建新的 repeater 项目
      const newItem = $(`<div class="repeater-item">
        <span class="item-number">${itemCount}</span>
    </div>`);

      // 根据不同的字段类型添加相应的输入字段
      if (key === "product_faqs") {
        // FAQ 字段结构
        const keyField = $(`<div class="repeater-item__key">
            <label for="${key}[${itemCount}][name]">Question:</label>
            <input class="repeater-item__key-input" type="text" id="${key}[${itemCount}][name]" name="${key}[${itemCount}][name]" value="" />
        </div>`);

        const valueField = $(`<div class="repeater-item__value">
            <label for="${key}[${itemCount}][value]">Answer:</label>
            <textarea class="repeater-item__value-input" id="${key}[${itemCount}][value]" name="${key}[${itemCount}][value]"></textarea>
        </div>`);

        newItem.append(keyField);
        newItem.append(valueField);
      } else if (key === "product_attributes") {
        // 属性字段结构
        const keyField = $(`<div class="repeater-item__key">
            <label for="${key}[${itemCount}][name]">Name:</label>
            <input class="repeater-item__key-input" type="text" id="${key}[${itemCount}][name]" name="${key}[${itemCount}][name]" value="" />
        </div>`);

        const valueField = $(`<div class="repeater-item__value">
            <label for="${key}[${itemCount}][value]">Value:</label>
            <input class="repeater-item__value-input" type="text" id="${key}[${itemCount}][value]" name="${key}[${itemCount}][value]" value="" />
        </div>`);

        newItem.append(keyField);
        newItem.append(valueField);
      }

      // 添加删除按钮
      const deleteButton =
        $(`<button type="button" class="button repeater-delete" title="Delete item">
        <span class="dashicons dashicons-trash"></span>
    </button>`);
      newItem.append(deleteButton);

      // 在新增按钮之前插入新项目
      repeaterWrapper.find(".repeater-add-wrapper").before(newItem);

      // 更新项目编号
      // this.updateRepeaterItemNumbers();
    }

    /**
     * 更新 repeater 项目编号
     */
    updateRepeaterItemNumbers() {
      $(".jc-repeater-wrapper").each(function () {
        $(this)
          .find(".repeater-item")
          .each(function (index) {
            $(this).find(".item-number").text(index);

            // 更新输入字段的 name 和 id 属性
            $(this)
              .find("input, textarea")
              .each(function () {
                const name = $(this).attr("name");
                if (name) {
                  const newName = name.replace(/\[\d+\]/g, `[${index}]`);
                  $(this).attr("name", newName);

                  const id = $(this).attr("id");
                  if (id) {
                    const newId = id.replace(/\[\d+\]/g, `[${index}]`);
                    $(this).attr("id", newId);
                  }
                }
              });
          });
      });
    }

    /**
     * 初始化产品图片画廊模块
     */
    initProductGallery() {
      const container = $("#jc-gallery .product-images");
      if (!container.length) {
        return;
      }
      // 图片排序
      container.sortable({
        items: "li.image",
        cursor: "move",
        scrollSensitivity: 40,
        forcePlaceholderSize: true,
        helper: "clone",
        opacity: 0.65,
        placeholder: "ui-sortable-placeholder",
        start: function (event, ui) {
          ui.placeholder.height(ui.item.height());
          ui.placeholder.width(ui.item.width());
        },
        stop: function () {
          container.find("li.image").removeAttr("style");
          this.updateGalleryImages();
        }.bind(this),
      });

      // 删除图片
      $(document).on("click", "#jc-gallery .image a.delete", (e) => {
        e.preventDefault();
        $(e.currentTarget).closest("li.image").remove();
        this.updateGalleryImages();
      });

      // 添加图片
      $(document).on("click", ".jc-add-image a", (e) => {
        e.preventDefault();
        this.openMediaFrame(e.currentTarget);
      });
    }

    /**
     * 打开媒体框架以选择图片
     */
    openMediaFrame(button) {
      let productGalleryFrame;
      const $el = $(button);
      const deleteText = $el.data("delete") || "Delete image";

      if (productGalleryFrame) {
        productGalleryFrame.open();
        return;
      }

      productGalleryFrame = wp.media({
        title: $el.data("choose"),
        button: { text: $el.data("update") },
        library: {
          type: [
            "image/jpeg",
            "image/jpg",
            "image/png",
            "image/gif",
            "image/webp",
          ],
        },
        multiple: true,
      });

      productGalleryFrame.on("select", () => {
        const selection = productGalleryFrame.state().get("selection");

        selection.map((attachment) => {
          attachment = attachment.toJSON();
          if (attachment.id) {
            $("#jelly_product_images_container .product_images").append(`
                  <li class="image" data-attachment_id="${attachment.id}">
                    <img src="${attachment.sizes.thumbnail.url}" alt="" />
                    <ul class="actions">
                      <li><a href="#" class="delete" title="${deleteText}">X</a></li>
                    </ul>
                  </li>
                `);
          }
        });

        this.updateGalleryImages();
      });

      productGalleryFrame.open();
    }
  }

  /**
   * DOM 加载完成后初始化产品编辑器
   */
  $(document).ready(() => {
    new ProductEditor();
  });
})(jQuery);
