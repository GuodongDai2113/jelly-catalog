/**
 * Jelly Catalog 产品表格编辑器 (Tabulator版本)
 *
 * 使用 Tabulator 作为表格显示和编辑组件
 */
class JellyCatalogSheetTabulator {
    /**
     * 构造函数 - 初始化类属性
     */
    constructor() {
        /** @type {null|Tabulator} 表格实例 */
        this.table = null;
        /** @type {number} 当前页码 */
        this.currentPage = 1;
        /** @type {number} 总页数 */
        this.totalPages = 1;
        /** @type {Array} 分类数据 */
        this.categories = [];
        /** @type {Object} 分类映射表 */
        this.categoriesMap = {};
        /** @type {Array} 标签数据 */
        this.tags = [];
        /** @type {Object} 标签映射表 */
        this.tagsMap = {};
        /** @type {Array} 元数据字段 */
        this.metaFields = [];
        /** @type {number} 每页显示的产品数量 */
        this.perPage = 20;
        /** @type {Array} 原始数据备份 */
        this.originalData = [];
    }

    /**
     * 初始化表格编辑器
     * 加载分类和标签数据，然后加载产品数据并绑定事件
     */
    init() {
        this.loadTaxonomyTerms().then(() => {
            this.loadProducts(1);
            this.bindEvents();
            console.log("Jelly Catalog Sheet Editor Tabulator Initialized");
        });
    }

    /**
     * 加载分类和标签数据
     * @returns {Promise} 返回一个 Promise，确保调用者可以等待数据加载完成
     */
    loadTaxonomyTerms() {
        let categoriesLoaded = false;
        let tagsLoaded = false;
        // 返回一个 Promise，确保调用者可以等待数据加载完成
        return new Promise((resolve, reject) => {
            // 获取产品分类（树形结构）
            this.fetchTaxonomyTerms("product_cat")
                .then((categories) => {
                    this.categories = categories; // 保存树形结构
                    this.categoriesMap = Object.fromEntries(
                        categories.map((item) => [item.id, item])
                    );
                    categoriesLoaded = true;
                    if (categoriesLoaded && tagsLoaded) {
                        resolve();
                    }
                })
                .catch((error) => {
                    console.warn("Failed to load product categories:", error);
                    categoriesLoaded = true;
                    if (categoriesLoaded && tagsLoaded) {
                        resolve();
                    }
                });

            // 获取产品标签
            this.fetchTaxonomyTerms("product_tag")
                .then((tags) => {
                    this.tags = tags;
                    this.tagsMap = Object.fromEntries(
                        tags.map((item) => [item.id, item])
                    );
                    tagsLoaded = true;
                    if (categoriesLoaded && tagsLoaded) {
                        resolve();
                    }
                })
                .catch((error) => {
                    console.warn("Failed to load product tags:", error);
                    tagsLoaded = true;
                    if (categoriesLoaded && tagsLoaded) {
                        resolve();
                    }
                });
        });
    }

    /**
     * 从服务器获取分类法术语
     * @param {string} taxonomy - 分类法名称
     * @returns {Promise} 包含分类法术语数据的 Promise
     */
    fetchTaxonomyTerms(taxonomy) {
        return new Promise((resolve, reject) => {
            jQuery
                .post(jc_ajax.ajax_url, {
                    action: "get_taxonomy_terms",
                    nonce: jc_ajax.nonce,
                    taxonomy: taxonomy,
                })
                .done((response) => {
                    if (response.success && response.data) {
                        resolve(response.data);
                    } else {
                        reject(
                            new Error(response.data || "Failed to fetch terms")
                        );
                    }
                })
                .fail((xhr) => {
                    reject(new Error("Network error: " + xhr.statusText));
                });
        });
    }

    /**
     * 封装 AJAX 请求，返回 jQuery Deferred
     * @param {number} page - 页码
     * @returns {jQuery.Deferred} jQuery Deferred 对象
     */
    requestProducts(page) {
        return jQuery.post(jc_ajax.ajax_url, {
            action: "get_products_sheet",
            nonce: jc_ajax.nonce,
            page,
            per_page: this.perPage,
        });
    }

    /**
     * 加载指定页商品数据
     * @param {number} page - 要加载的页码，默认为1
     */
    loadProducts(page = 1) {
        this.requestProducts(page)
            .done((res) => {
                this.handleProductResponse(res);
            })
            .fail(() => {
                alert("加载商品数据失败，请稍后重试");
            });
    }

    /**
     * 处理产品响应数据
     * @param {Object} res - 服务器响应数据
     */
    handleProductResponse(res) {
        if (!res || !Array.isArray(res.products)) {
            alert("返回数据格式不正确");
            return;
        }
        res.products.forEach((product) => {
            if (
                product.categories &&
                Array.isArray(product.categories) &&
                product.categories.length > 0
            ) {
                const categories = product.categories[0];
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

    /**
     * 绑定所有事件监听器
     */
    bindEvents() {
        this.bindPaginationEvents();
        this.bindSaveEvent();
    }

    /**
     * 同步分页元数据并刷新按钮状态
     * @param {Object} paginationData - 包含分页信息的对象
     * @param {number} paginationData.page - 当前页码
     * @param {number} paginationData.total_pages - 总页数
     */
    updatePaginationMeta({ page, total_pages }) {
        this.currentPage = page;
        this.totalPages = total_pages;

        jQuery("#current-page").text(this.currentPage);
        jQuery("#total-pages").text(this.totalPages);

        this.updatePaginationButtons();
    }

    /**
     * 更新分页按钮状态
     */
    updatePaginationButtons() {
        jQuery("#first-page, #prev-page").prop(
            "disabled",
            this.currentPage <= 1
        );
        jQuery("#next-page, #last-page").prop(
            "disabled",
            this.currentPage >= this.totalPages
        );
        jQuery("#goto-page, #goto-page-btn").prop(
            "disabled",
            this.totalPages == 1
        );
    }

    /**
     * 绑定分页相关事件
     */
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
            if (
                page >= 1 &&
                page <= this.totalPages &&
                page !== this.currentPage
            ) {
                this.loadProducts(page);
            } else {
                alert("页码错误！");
            }
        });

        jQuery("#goto-page").on("keyup", (e) => {
            if (e.key === "Enter") {
                jQuery("#goto-page-btn").click();
            }
        });

        // 每页显示数量改变事件
        jQuery("#per-page-select").on("change", (e) => {
            const perPage = parseInt(e.target.value, 10);
            this.perPage = perPage;
            this.loadProducts(1); // 重新加载第一页
        });
    }

    /**
     * 确保表格实例存在并初始化
     * @param {Array} products - 产品数据数组
     */
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
                row.getElement().setAttribute("data-id", rowData.ID);
            },
        });
    }

    /**
     * 构建表格列配置
     * @returns {Array} 列配置数组
     */
    buildTableColumns() {
        const columns = [
            {
                title: "ID",
                field: "ID",
                width: 80,
                hozAlign: "center",
                frozen: true,
            },
            {
                title: "主图",
                field: "meta_data._thumbnail_id",
                width: 70,
                hozAlign: "center",
                formatter: (cell) => {
                    const data = cell.getValue();
                    if (
                        data &&
                        Array.isArray(data) &&
                        data.length > 0 &&
                        data[0].url
                    ) {
                        return `<img src="${data[0].url}" style="max-width: 80px; max-height: 80px;" />`;
                    }
                    return "";
                },
            },
            {
                title: "相册",
                field: "meta_data._product_image_gallery",
                width: 200,
                hozAlign: "center",
                formatter: (cell) => {
                    const data = cell.getValue();
                    if (data && Array.isArray(data) && data.length > 0) {
                        let html = "";
                        data.forEach((img) => {
                            if (img.url) {
                                html += `<img src="${img.url}" style="max-width: 50px; max-height: 50px; margin: 2px;" />`;
                            }
                        });
                        return html;
                    }
                    return "";
                },
            },
            {
                title: "名称",
                field: "post_title",
                width: 200,
                editor: "input",
            },
            {
                title: "摘要",
                field: "post_excerpt",
                width: 300,
                editor: "textarea",
            },
            {
                title: "内容",
                field: "post_content",
                width: 300,
                formatter: (cell, formatterParams, onRendered) => {
                    const data = cell.getValue();
                    if (data) {
                        let displayValue = data.replace(/<[^>]*>/g, "").trim();
                        if (displayValue.length > 150) {
                            displayValue =
                                displayValue.substring(0, 150) + "...";
                        }

                        // 处理换行符，最多保留3行
                        const lines = displayValue.split("\n");
                        if (lines.length > 3) {
                            displayValue = lines.slice(0, 3).join("\n") + "...";
                        }

                        const span = document.createElement("span");
                        span.textContent = displayValue;
                        span.setAttribute(
                            "title",
                            data.replace(/<[^>]*>/g, "").trim()
                        );
                        return span;
                    }
                    return "";
                },
                editor: "textarea",
                cellEdited: (cell) => {
                    // 内容编辑后更新tooltip
                    const element = cell.getElement();
                    const value = cell.getValue();
                    if (value) {
                        element.setAttribute(
                            "title",
                            value.replace(/<[^>]*>/g, "").trim()
                        );
                    }
                },
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
                        { value: "draft", label: "草稿" },
                    ],
                },
                formatter: (cell) => {
                    const value = cell.getValue();
                    if (!value) return "";
                    return value === "publish" ? "发布" : "草稿";
                },
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
                        placeholder: "选择一级分类",
                    };
                },
                formatter: (cell) => {
                    const categoryId = cell.getValue();
                    if (
                        !categoryId ||
                        !this.categoriesMap ||
                        !this.categoriesMap[categoryId]
                    ) {
                        return "";
                    }
                    return this.categoriesMap[categoryId].name;
                },
                cellEdited: (cell) => {
                    // 当一级分类更改时，清除二级和三级分类的值
                    const row = cell.getRow();
                    row.update({
                        secondary_category: "",
                        tertiary_category: "",
                    });
                },
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
                            disabled: true,
                        };
                    }

                    // 根据一级分类获取对应的二级分类选项
                    return {
                        values: this.getParentCategories(primaryCategory),
                        autocomplete: false,
                        placeholder: "选择二级分类",
                        disabled: false,
                    };
                },
                formatter: (cell) => {
                    const categoryId = cell.getValue();
                    if (
                        !categoryId ||
                        !this.categoriesMap ||
                        !this.categoriesMap[categoryId]
                    ) {
                        return "";
                    }
                    return this.categoriesMap[categoryId].name;
                },
                cellEdited: (cell) => {
                    // 当二级分类更改时，清除三级分类的值
                    const row = cell.getRow();
                    row.update({ tertiary_category: "" });
                },
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
                            disabled: true,
                        };
                    }

                    // 根据二级分类获取对应的三级分类选项
                    return {
                        values: this.getParentCategories(secondaryCategory),
                        autocomplete: false,
                        placeholder: "选择三级分类",
                        disabled: false,
                    };
                },
                formatter: (cell) => {
                    const categoryId = cell.getValue();
                    if (
                        !categoryId ||
                        !this.categoriesMap ||
                        !this.categoriesMap[categoryId]
                    ) {
                        return "";
                    }
                    return this.categoriesMap[categoryId].name;
                },
            },
        ];

        // 添加元数据字段
        this.metaFields.forEach((field) => {
            columns.push({
                title: field.label,
                field: `meta_data.${field.key}`,
                width: 150,
                editor: "input",
                formatter: (cell) => {
                    const value = cell.getValue();
                    return value || "";
                },
            });
        });

        return columns;
    }

    /**
     * 获取指定父级ID的子分类
     * @param {number} parent_id - 父级分类ID，默认为0（顶级分类）
     * @returns {Array} 子分类数组
     */
    getParentCategories(parent_id = 0) {
        const result = [
            { value: "", label: "空" }, // 空选项
        ];

        Object.values(this.categoriesMap).forEach((item) => {
            if (item.parent_id === parent_id) {
                result.push({
                    value: item.id,
                    label: item.name,
                });
            }
        });

        return result;
    }

    /**
     * 绑定保存按钮事件
     */
    bindSaveEvent() {
        jQuery("#save-sheet").on("click", () => {
            this.saveChanges();
        });
    }

    /**
     * 保存更改的统一入口
     */
    saveChanges() {
        const changedData = this.collectChanges();
        if (changedData.length === 0) {
            alert("没有更改需要保存");
            return;
        }

        this.submitChanges(changedData);
    }

    /**
     * 汇总所有被修改的行数据
     * @returns {Array} 包含更改数据的数组
     */
    collectChanges() {
        if (!this.table) return [];

        // 获取所有数据
        const tableData = this.table.getData();
        const changes = [];

        tableData.forEach((rowData) => {
            const originalRow = this.originalData.find(
                (item) => item.ID == rowData.ID
            );

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

                if (originalRow.categories.length <= 0) {
                    // 只有当至少有一个分类级别非空时才标记为变更
                    if (
                        rowData.primary_category ||
                        rowData.secondary_category ||
                        rowData.tertiary_category
                    ) {
                        categories.level_1 = rowData.primary_category;
                        categories.level_2 = rowData.secondary_category;
                        categories.level_3 = rowData.tertiary_category;
                        isCategoriesChanges = true;
                    }
                } else {
                    const originalCategories = originalRow.categories[0];

                    if (
                        originalCategories.level_1 !== rowData.primary_category
                    ) {
                        categories.level_1 = rowData.primary_category;
                        isCategoriesChanges = true;
                    }
                    if (
                        originalCategories.level_2 !==
                        rowData.secondary_category
                    ) {
                        categories.level_2 = rowData.secondary_category;
                        isCategoriesChanges = true;
                    }
                    if (
                        originalCategories.level_3 !== rowData.tertiary_category
                    ) {
                        categories.level_3 = rowData.tertiary_category;
                        isCategoriesChanges = true;
                    }
                }

                if (isCategoriesChanges) {
                    rowChanges.categories = categories;
                }
            }

            // 处理标签变更
            const currentTags = Array.isArray(rowData.tags)
                ? rowData.tags.join(", ")
                : rowData.tags || "";
            const originalTags = Array.isArray(originalRow.tags)
                ? originalRow.tags.join(", ")
                : originalRow.tags || "";

            if (currentTags !== originalTags) {
                rowChanges.tags = currentTags;
            }

            // 检查元数据字段变更
            // if (rowData.meta_data) {
            //     Object.keys(rowData.meta_data).forEach((key) => {
            //         const currentValue = rowData.meta_data[key];
            //         const originalValue = originalRow.meta_data[key];
            //         console.log(currentValue);
            //         console.log(originalValue);

            //         if (currentValue !== originalValue) {
            //             if (!rowChanges.meta_data) rowChanges.meta_data = {};
            //             rowChanges.meta_data[key] = currentValue;
            //         }
            //     });
            // }

            // 如果有变更则添加到changes数组
            if (Object.keys(rowChanges).length > 1) {
                changes.push(rowChanges);
            }
        });
        return changes;
    }

    /**
     * 提交变更到服务器
     * @param {Array} changedData - 更改的数据数组
     */
    submitChanges(changedData) {
        jQuery
            .post(jc_ajax.ajax_url, {
                action: "save_products_sheet",
                nonce: jc_ajax.nonce,
                data: changedData,
            })
            .done((res) => {
                if (res.success) {
                    alert(res.data || "保存成功");
                    // 重新加载数据以反映更改
                    this.loadProducts(this.currentPage);
                    // 刷新备份数据，避免重复提交
                    this.originalData = JSON.parse(
                        JSON.stringify(this.table.getData())
                    );
                } else {
                    alert("保存失败：" + (res.data || "未知错误"));
                }
            })
            .fail(() => {
                alert("网络错误，保存失败");
            });
    }

    /**
     * 创建新产品
     * @param {Object} productData - 产品数据
     * @returns {Promise} 创建操作的 Promise
     */
    createProduct(productData) {
        return new Promise((resolve, reject) => {
            jQuery
                .post(jc_ajax.ajax_url, {
                    action: "create_product",
                    nonce: jc_ajax.nonce,
                    data: productData,
                })
                .done((res) => {
                    if (res.success) {
                        resolve(res.data);
                    } else {
                        reject(new Error(res.data || "创建产品失败"));
                    }
                })
                .fail((xhr) => {
                    reject(new Error("网络错误: " + xhr.statusText));
                });
        });
    }

    /**
     * 删除产品
     * @param {number} productId - 产品ID
     * @returns {Promise} 删除操作的 Promise
     */
    deleteProduct(productId) {
        return new Promise((resolve, reject) => {
            jQuery
                .post(jc_ajax.ajax_url, {
                    action: "delete_product",
                    nonce: jc_ajax.nonce,
                    id: productId,
                })
                .done((res) => {
                    if (res.success) {
                        resolve(res.data);
                    } else {
                        reject(new Error(res.data || "删除产品失败"));
                    }
                })
                .fail((xhr) => {
                    reject(new Error("网络错误: " + xhr.statusText));
                });
        });
    }

    /**
     * 更新单个产品
     * @param {number} productId - 产品ID
     * @param {Object} productData - 更新的产品数据
     * @returns {Promise} 更新操作的 Promise
     */
    updateProduct(productId, productData) {
        return new Promise((resolve, reject) => {
            jQuery
                .post(jc_ajax.ajax_url, {
                    action: "update_product",
                    nonce: jc_ajax.nonce,
                    id: productId,
                    data: productData,
                })
                .done((res) => {
                    if (res.success) {
                        resolve(res.data);
                    } else {
                        reject(new Error(res.data || "更新产品失败"));
                    }
                })
                .fail((xhr) => {
                    reject(new Error("网络错误: " + xhr.statusText));
                });
        });
    }

    /**
     * 获取单个产品详情
     * @param {number} productId - 产品ID
     * @returns {Promise} 获取产品详情的 Promise
     */
    getProduct(productId) {
        return new Promise((resolve, reject) => {
            jQuery
                .post(jc_ajax.ajax_url, {
                    action: "get_product",
                    nonce: jc_ajax.nonce,
                    id: productId,
                })
                .done((res) => {
                    if (res.success && res.data) {
                        resolve(res.data);
                    } else {
                        reject(new Error(res.data || "获取产品详情失败"));
                    }
                })
                .fail((xhr) => {
                    reject(new Error("网络错误: " + xhr.statusText));
                });
        });
    }
}

// 页面加载完成后初始化
jQuery(document).ready(function ($) {
    window.jellyCatalogSheet = new JellyCatalogSheetTabulator();
    window.jellyCatalogSheet.init();
});
