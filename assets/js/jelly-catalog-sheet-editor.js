(function ($) {
  "use strict";

  class JellyCatalogSheetEditor {
    constructor() {
      this.hotInstance = null;
      this.originalData = [];
      this.currentPage = 1;
      this.totalPages = 1;
      this.perPage = 20;
      this.metaFields = [
        { key: "rank_math_focus_keyword", label: "焦点关键词" },
        { key: "rank_math_title", label: "SEO 标题" },
        { key: "rank_math_description", label: "SEO 描述" },
      ];

      this.init();
    }

    // 初始化入口
    init() {
      this.loadProducts(1);
      this.bindEvents();
      console.log("Jelly Catalog Sheet Editor Initialized");
    }

    // 加载指定页商品数据
    loadProducts(page = 1) {
      this.requestProducts(page)
        .done((res) => {
          this.handleProductResponse(res);
        })
        .fail(() => {
          alert("加载商品数据失败，请稍后重试");
        });
    }

    // 封装 AJAX 请求，返回 jQuery Deferred
    requestProducts(page) {
      return jQuery.post(jc_ajax.ajax_url, {
        action: "get_products_sheet",
        nonce: jc_ajax.nonce,
        page,
        per_page: this.perPage,
      });
    }

    // 处理服务器返回的数据
    handleProductResponse(res) {
      if (!res || !Array.isArray(res.products)) {
        alert("返回数据格式不正确");
        return;
      }

      this.originalData = JSON.parse(JSON.stringify(res.products));
      this.updatePaginationMeta(res);
      this.ensureHotInstance(res.products);
    }

    // 同步分页元数据并刷新按钮状态
    updatePaginationMeta({ page, total_pages }) {
      this.currentPage = page;
      this.totalPages = total_pages;

      $("#current-page").text(this.currentPage);
      $("#total-pages").text(this.totalPages);

      this.updatePaginationButtons();
    }

    // 创建或更新 Handsontable 实例
    ensureHotInstance(products) {
      const container = document.getElementById("hot");
      if (!container) {
        alert("找不到表格容器");
        return;
      }

      if (this.hotInstance) {
        this.hotInstance.loadData(products);
        return;
      }

      const { columns, colHeaders } = this.buildTableStructure();

      this.hotInstance = new Handsontable(container, {
        themeName: "ht-theme-main",
        data: products,
        rowHeaders: true,
        colHeaders,
        columns,
        filters: true,
        dropdownMenu: true,
        height: "auto",
        autoWrapRow: true,
        autoWrapCol: true,
        licenseKey: "non-commercial-and-evaluation",
        contextMenu: true,
      });
    }

    // 构建表格列配置与表头
    buildTableStructure() {
      const columns = [
        { data: "ID", readOnly: true, width: 80 },
        { data: "post_title", width: 200 },
        { data: "post_excerpt", width: 300 },
      ];
      const colHeaders = ["ID", "名称", "摘要"];

      this.metaFields.forEach((field) => {
        columns.push({
          data: `meta_data.${field.key}`,
          width: 150,
        });
        colHeaders.push(field.label);
      });

      return { columns, colHeaders };
    }

    // 更新分页按钮状态
    updatePaginationButtons() {
      $("#first-page, #prev-page").prop("disabled", this.currentPage <= 1);
      $("#next-page, #last-page").prop(
        "disabled",
        this.currentPage >= this.totalPages
      );
    }

    // 绑定页面事件
    bindEvents() {
      this.bindPaginationEvents();
      this.bindSaveEvent();
    }

    // 绑定分页相关事件
    bindPaginationEvents() {
      $("#first-page").on("click", () => {
        if (this.currentPage > 1) {
          this.loadProducts(1);
        }
      });

      $("#prev-page").on("click", () => {
        if (this.currentPage > 1) {
          this.loadProducts(this.currentPage - 1);
        }
      });

      $("#next-page").on("click", () => {
        if (this.currentPage < this.totalPages) {
          this.loadProducts(this.currentPage + 1);
        }
      });

      $("#last-page").on("click", () => {
        if (this.currentPage < this.totalPages) {
          this.loadProducts(this.totalPages);
        }
      });

      $("#goto-page-btn").on("click", () => {
        const page = parseInt($("#goto-page").val(), 10);
        if (page >= 1 && page <= this.totalPages && page !== this.currentPage) {
          this.loadProducts(page);
        }
      });

      $("#goto-page").on("keyup", (e) => {
        if (e.key === "Enter") {
          $("#goto-page-btn").click();
        }
      });
    }

    // 绑定保存按钮事件
    bindSaveEvent() {
      $("#save-sheet").on("click", () => {
        this.saveChanges();
      });
    }

    // 保存更改的统一入口
    saveChanges() {
      if (!this.hotInstance) return;

      const changedData = this.collectChanges();
      if (changedData.length === 0) {
        alert("没有更改需要保存");
        return;
      }

      this.submitChanges(changedData);
    }

    // 汇总所有被修改的行数据
    collectChanges() {
      const changes = [];
      const productsData = this.hotInstance.getSourceData();

      productsData.forEach((row, rowIndex) => {
        const originalRow = this.originalData.find((item) => item.ID == row.ID);
        if (!originalRow) {
          return;
        }

        const rowChanges = this.extractRowChanges(rowIndex, originalRow);
        if (Object.keys(rowChanges).length > 1) {
          // rowChanges 至少包含 ID + 其它字段
          changes.push(rowChanges);
        }
      });

      return changes;
    }

    // 计算单行的差异字段
    extractRowChanges(rowIndex, originalRow) {
      const rowChanges = { ID: originalRow.ID };

      const newName = this.hotInstance.getDataAtCell(rowIndex, 1);
      if (newName !== originalRow.post_title) {
        rowChanges.post_title = newName;
      }

      const newExcerpt = this.hotInstance.getDataAtCell(rowIndex, 2);
      if (newExcerpt !== originalRow.post_excerpt) {
        rowChanges.post_excerpt = newExcerpt;
      }

      const metaChanges = this.extractMetaChanges(rowIndex, originalRow);
      if (Object.keys(metaChanges).length > 0) {
        rowChanges.meta_data = metaChanges;
      }

      return rowChanges;
    }

    // 比较元数据的差异
    extractMetaChanges(rowIndex, originalRow) {
      const metaChanges = {};
      const originalMeta = originalRow.meta_data || {};

      this.metaFields.forEach((field, fieldIndex) => {
        const columnIndex = 3 + fieldIndex;
        const newValue = this.hotInstance.getDataAtCell(rowIndex, columnIndex);
        const originalValue = originalMeta[field.key] || "";

        if (newValue !== originalValue) {
          metaChanges[field.key] = newValue;
        }
      });

      return metaChanges;
    }

    // 提交修改到服务器
    submitChanges(changedData) {
      jQuery
        .post(jc_ajax.ajax_url, {
          action: "save_products_sheet",
          nonce: jc_ajax.nonce,
          data: changedData,
        })
        .done((res) => {
          if (res.success) {
            alert(res.data);
            this.syncOriginalData();
          } else {
            alert(res.data || "保存失败");
          }
        })
        .fail(() => {
          alert("保存失败，请稍后重试");
        });
    }

    // 同步最新数据到 originalData，保持后续比对准确
    syncOriginalData() {
      const productsData = this.hotInstance.getSourceData();

      productsData.forEach((row, rowIndex) => {
        const originalRow = this.originalData.find((item) => item.ID == row.ID);
        if (!originalRow) return;

        originalRow.post_title = this.hotInstance.getDataAtCell(rowIndex, 1);
        originalRow.post_excerpt = this.hotInstance.getDataAtCell(rowIndex, 2);

        if (!originalRow.meta_data) {
          originalRow.meta_data = {};
        }

        this.metaFields.forEach((field, fieldIndex) => {
          const columnIndex = 3 + fieldIndex;
          const newValue = this.hotInstance.getDataAtCell(
            rowIndex,
            columnIndex
          );
          originalRow.meta_data[field.key] = newValue;
        });
      });
    }
  }

  // 初始化编辑器
  $(document).ready(() => {
    window.jellyCatalogSheetEditor = new JellyCatalogSheetEditor();
  });
})(jQuery);
