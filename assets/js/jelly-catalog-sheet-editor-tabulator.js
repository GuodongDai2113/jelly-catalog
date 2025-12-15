/**
 * Jelly Catalog 产品表格编辑器 (Tabulator版本)
 * 
 * 使用 Tabulator 作为表格显示和编辑组件
 */

class JellyCatalogSheetTabulator {
    constructor() {
        this.table = null;
        this.currentPage = 1;
        this.totalPages = 1;
        this.categories = [];
        this.categoriesMap = {};
        this.tags = [];
        this.tagsMap = {};
        this.metaFields = [];
    }

    init() {
        this.loadTaxonomyTerms().then(() => {
            this.loadProducts(1);
            this.bindEvents();
            console.log("Jelly Catalog Sheet Editor Tabulator Initialized");
        });
    }

    // 加载分类和标签数据
    loadTaxonomyTerms() {
        let categoriesLoaded = false;
        let tagsLoaded = false;
        // 返回一个 Promise，确保调用者可以等待数据加载完成
        return new Promise((resolve, reject) => {
            // 获取产品分类（树形结构）
            this.fetchTaxonomyTerms('product_cat')
                .then(categories => {
                    this.categories = categories; // 保存树形结构
                    this.categoriesMap = Object.fromEntries(
                        categories.map(item => [item.id, item])
                    );
                    categoriesLoaded = true;
                    if (categoriesLoaded && tagsLoaded) {
                        resolve();
                    }
                })
                .catch(error => {
                    console.warn('Failed to load product categories:', error);
                    categoriesLoaded = true;
                    if (categoriesLoaded && tagsLoaded) {
                        resolve();
                    }
                });

            // 获取产品标签
            this.fetchTaxonomyTerms('product_tag')
                .then(tags => {
                    this.tags = tags;
                    this.tagsMap = Object.fromEntries(
                        tags.map(item => [item.id, item])
                    );
                    tagsLoaded = true;
                    if (categoriesLoaded && tagsLoaded) {
                        resolve();
                    }
                })
                .catch(error => {
                    console.warn('Failed to load product tags:', error);
                    tagsLoaded = true;
                    if (categoriesLoaded && tagsLoaded) {
                        resolve();
                    }
                });
        });
    }

    // 从服务器获取分类法术语
    fetchTaxonomyTerms(taxonomy) {
        return new Promise((resolve, reject) => {
            jQuery.post(jc_ajax.ajax_url, {
                action: 'get_taxonomy_terms',
                nonce: jc_ajax.nonce,
                taxonomy: taxonomy
            })
                .done(response => {
                    if (response.success && response.data) {
                        resolve(response.data);
                    } else {
                        reject(new Error(response.data || 'Failed to fetch terms'));
                    }
                })
                .fail(xhr => {
                    reject(new Error('Network error: ' + xhr.statusText));
                });
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

    handleProductResponse(res) {
        if (!res || !Array.isArray(res.products)) {
            alert("返回数据格式不正确");
            return;
        }
        res.products.forEach(product => {
            if (product.categories && Array.isArray(product.categories) && product.categories.length > 0) {
                const categories = product.categories[0]
                product.primary_category = categories.level_1;
                product.secondary_category = categories.level_2;
                product.tertiary_category = categories.level_3;
            } else {
                product.primary_category = "";
                product.secondary_category = "";
                product.tertiary_category = "";
            }
        });

        this.originalData = JSON.parse(JSON.stringify(res.products));
        this.updatePaginationMeta(res);
        this.ensureTableInstance(res.products);

    }

    bindEvents() {
        this.bindPaginationEvents();
        this.bindSaveEvent();
    }

    // 同步分页元数据并刷新按钮状态
    updatePaginationMeta({ page, total_pages }) {
        this.currentPage = page;
        this.totalPages = total_pages;

        jQuery("#current-page").text(this.currentPage);
        jQuery("#total-pages").text(this.totalPages);

        this.updatePaginationButtons();
    }

    // 更新分页按钮状态
    updatePaginationButtons() {
        jQuery("#first-page, #prev-page").prop("disabled", this.currentPage <= 1);
        jQuery("#next-page, #last-page").prop(
            "disabled",
            this.currentPage >= this.totalPages
        );
    }

    // 绑定分页相关事件
    bindPaginationEvents() {
        jQuery("#first-page").on("click", () => {
            if (this.currentPage > 1) {
                this.loadProducts(1);
            }
        });

        jQuery("#prev-page").on("click", () => {
            if (this.currentPage > 1) {
                this.loadProducts(this.currentPage - 1);
            }
        });

        jQuery("#next-page").on("click", () => {
            if (this.currentPage < this.totalPages) {
                this.loadProducts(this.currentPage + 1);
            }
        });

        jQuery("#last-page").on("click", () => {
            if (this.currentPage < this.totalPages) {
                this.loadProducts(this.totalPages);
            }
        });

        jQuery("#goto-page-btn").on("click", () => {
            const page = parseInt(jQuery("#goto-page").val(), 10);
            if (page >= 1 && page <= this.totalPages && page !== this.currentPage) {
                this.loadProducts(page);
            }
        });

        jQuery("#goto-page").on("keyup", (e) => {
            if (e.key === "Enter") {
                jQuery("#goto-page-btn").click();
            }
        });
    }

    ensureTableInstance(products) {
        const container = document.getElementById("hot");
        if (!container) {
            alert("找不到表格容器");
            return;
        }

        // 如果已存在Table实例，先销毁
        if (this.table) {
            this.table.destroy();
        }

        // 初始化Tabulator
        this.table = new Tabulator("#hot", {
            data: products,
            layout: "fitColumns",
            pagination: false,
            movableColumns: true,
            resizableRows: true,
            columns: this.buildTableColumns(),
            rowFormatter: (row) => {
                const rowData = row.getData();
                row.getElement().setAttribute('data-id', rowData.ID);
            }
        });
    }

    // 构建表格列配置
    buildTableColumns() {
        const columns = [
            {
                title: "ID",
                field: "ID",
                width: 80,
                hozAlign: "center",
                frozen: true
            },
            {
                title: "名称",
                field: "post_title",
                width: 200,
                editor: "input"
            },
            {
                title: "摘要",
                field: "post_excerpt",
                width: 300,
                editor: "textarea"
            },
            {
                title: "内容",
                field: "post_content",
                width: 300,
                formatter: (cell, formatterParams, onRendered) => {
                    const data = cell.getValue();
                    if (data) {
                        let displayValue = data.replace(/<[^>]*>/g, '').trim();
                        if (displayValue.length > 150) {
                            displayValue = displayValue.substring(0, 150) + '...';
                        }

                        // 处理换行符，最多保留3行
                        const lines = displayValue.split('\n');
                        if (lines.length > 3) {
                            displayValue = lines.slice(0, 3).join('\n') + '...';
                        }

                        const span = document.createElement("span");
                        span.textContent = displayValue;
                        span.setAttribute("title", data.replace(/<[^>]*>/g, '').trim());
                        return span;
                    }
                    return '';
                },
                editor: "textarea",
                cellEdited: (cell) => {
                    // 内容编辑后更新tooltip
                    const element = cell.getElement();
                    const value = cell.getValue();
                    if (value) {
                        element.setAttribute('title', value.replace(/<[^>]*>/g, '').trim());
                    }
                }
            },
            {
                title: "状态",
                field: "post_status",
                width: 100,
                hozAlign: "center",
                editor: "list",
                editorParams: {
                    values: [
                        { value: "publish", label: "发布" },
                        { value: "draft", label: "草稿" }
                    ]
                },
                formatter: (cell) => {
                    const value = cell.getValue();
                    if (!value) return '';
                    return value === 'publish' ? '发布' : '草稿';
                }
            },
            {
                title: "一级分类",
                field: "primary_category",
                width: 150,
                editor: "list",
                editorParams: () => {
                    return {
                        values: this.getParentCategories(0),
                        autocomplete: true,
                        placeholder: "选择一级分类"
                    };
                },
                formatter: (cell) => {
                    const categoryId = cell.getValue();
                    if (!categoryId || !this.categoriesMap || !this.categoriesMap[categoryId]) {
                        return '';
                    }
                    return this.categoriesMap[categoryId].name
                },
                cellEdited: (cell) => {
                    // 当一级分类更改时，清除二级和三级分类的值
                    const row = cell.getRow();
                    row.update({ secondary_category: "", tertiary_category: "" });
                }
            },
            {
                title: "二级分类",
                field: "secondary_category",
                width: 150,
                editor: "list",
                editorParams: (cell) => {
                    const row = cell.getRow();
                    const rowData = row.getData();
                    const primaryCategory = rowData.primary_category;

                    // 如果一级分类未选择，则禁用二级分类
                    if (!primaryCategory) {
                        return {
                            values: [{ value: "", label: "请选择" }],
                            autocomplete: false,
                            placeholder: "先选择一级分类",
                            disabled: true
                        };
                    }

                    // 根据一级分类获取对应的二级分类选项
                    return {
                        values: this.getParentCategories(primaryCategory),
                        autocomplete: false,
                        placeholder: "选择二级分类",
                        disabled: false
                    };
                },
                formatter: (cell) => {
                    const categoryId = cell.getValue();
                    if (!categoryId || !this.categoriesMap || !this.categoriesMap[categoryId]) {
                        return '';
                    }
                    return this.categoriesMap[categoryId].name
                },
                cellEdited: (cell) => {
                    // 当二级分类更改时，清除三级分类的值
                    const row = cell.getRow();
                    row.update({ tertiary_category: "" });
                }
            },
            {
                title: "三级分类",
                field: "tertiary_category",
                width: 150,
                editor: "list",
                editorParams: (cell) => {
                    const row = cell.getRow();
                    const rowData = row.getData();
                    const secondaryCategory = rowData.secondary_category;

                    // 如果二级分类未选择，则禁用三级分类
                    if (!secondaryCategory) {
                        return {
                            values: [{ value: "", label: "请选择" }],
                            autocomplete: false,
                            placeholder: "先选择二级分类",
                            disabled: true
                        };
                    }

                    // 根据二级分类获取对应的三级分类选项
                    return {
                        values: this.getParentCategories(secondaryCategory),
                        autocomplete: false,
                        placeholder: "选择三级分类",
                        disabled: false
                    };
                },
                formatter: (cell) => {
                    const categoryId = cell.getValue();
                    if (!categoryId || !this.categoriesMap || !this.categoriesMap[categoryId]) {
                        return '';
                    }
                    return this.categoriesMap[categoryId].name
                }
            },
        ];

        // 添加元数据字段
        this.metaFields.forEach(field => {
            columns.push({
                title: field.label,
                field: `meta_data.${field.key}`,
                width: 150,
                editor: "input",
                formatter: (cell) => {
                    const value = cell.getValue();
                    return value || '';
                }
            });
        });

        return columns;
    }

    getParentCategories(parent_id = 0) {
        const result = [
            { value: '', label: '空' } // 空选项
        ];

        Object.values(this.categoriesMap).forEach(item => {
            if (item.parent_id === parent_id) {
                result.push({
                    value: item.id,
                    label: item.name
                });
            }
        });

        return result;
    }
    // 绑定保存按钮事件
    bindSaveEvent() {
        jQuery("#save-sheet").on("click", () => {
            this.saveChanges();
        });
    }

    // 保存更改的统一入口
    saveChanges() {
        const changedData = this.collectChanges();
        if (changedData.length === 0) {
            alert("没有更改需要保存");
            return;
        }

        this.submitChanges(changedData);
    }

    // 汇总所有被修改的行数据
    collectChanges() {
        if (!this.table) return [];

        // 获取所有数据
        const tableData = this.table.getData();
        const changes = [];

        tableData.forEach(rowData => {
            const originalRow = this.originalData.find(item => item.ID == rowData.ID);

            if (!originalRow) return;

            const rowChanges = { ID: rowData.ID };

            // 检查基本字段变更
            if (rowData.post_title !== originalRow.post_title) {
                rowChanges.post_title = rowData.post_title;
            }

            if (rowData.post_excerpt !== originalRow.post_excerpt) {
                rowChanges.post_excerpt = rowData.post_excerpt;
            }

            if (rowData.post_content !== originalRow.post_content) {
                rowChanges.post_content = rowData.post_content;
            }

            if (rowData.post_status !== originalRow.post_status) {
                rowChanges.post_status = rowData.post_status;
            }

            // 只使用通过分级选择器选择的分类
            if (rowData.primary_category) {
                // 使用分类名称而不是ID

                let categories = {};
                let isCategoriesChanges = false;
                const originalCategories = originalRow.categories[0];
                if (originalCategories.level_1 !== rowData.primary_category) {
                    categories.level_1 = rowData.primary_category;
                    isCategoriesChanges = true;
                }
                if (originalCategories.level_2 !== rowData.secondary_category) {
                    categories.level_2 = rowData.secondary_category;
                    isCategoriesChanges = true;
                }
                if (originalCategories.level_3 !== rowData.tertiary_category) {
                    categories.level_3 = rowData.tertiary_category;
                    isCategoriesChanges = true;
                }

                if (isCategoriesChanges) {
                    rowChanges.categories = categories;
                }
            }

            // 处理标签变更
            const currentTags = Array.isArray(rowData.tags) ? rowData.tags.join(', ') : rowData.tags || '';
            const originalTags = Array.isArray(originalRow.tags) ? originalRow.tags.join(', ') : originalRow.tags || '';

            if (currentTags !== originalTags) {
                rowChanges.tags = currentTags;
            }

            // 检查元数据字段变更
            if (rowData.meta_data) {
                Object.keys(rowData.meta_data).forEach(key => {
                    const currentValue = rowData.meta_data[key];
                    const originalValue = originalRow.meta_data[key];

                    if (currentValue !== originalValue) {
                        if (!rowChanges.meta_data) rowChanges.meta_data = {};
                        rowChanges.meta_data[key] = currentValue;
                    }
                });
            }

            // 如果有变更则添加到changes数组
            if (Object.keys(rowChanges).length > 1) {
                changes.push(rowChanges);
            }
        });

        return changes;
    }

    // 提交变更到服务器
    submitChanges(changedData) {
        jQuery.post(jc_ajax.ajax_url, {
            action: "save_products_sheet",
            nonce: jc_ajax.nonce,
            data: changedData
        })
            .done((res) => {
                if (res.success) {
                    alert(res.data || "保存成功");
                    // 重新加载数据以反映更改
                    this.loadProducts(this.currentPage);
                } else {
                    alert("保存失败：" + (res.data || "未知错误"));
                }
            })
            .fail(() => {
                alert("网络错误，保存失败");
            });
    }
}

// 页面加载完成后初始化
jQuery(document).ready(function ($) {
    window.jellyCatalogSheet = new JellyCatalogSheetTabulator();
    window.jellyCatalogSheet.init();
});