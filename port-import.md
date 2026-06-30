# port-import.php 说明文档

## 文件概述

`modules/port-import.php` 是一个 PHP Trait，定义了 `Port_Import`，为 Jelly Catalog 插件提供完整的**产品批量导入**能力。核心功能包括：

- CSV 文件解析与产品数据导入
- 分类 CSV 独立导入
- ZIP 图片文件解压与媒体库导入
- 断点续传（通过 transient 持久化 job 状态）
- 日志记录
- 错误处理与重试机制
- AJAX 分批接口（前端可逐批拉取进度）

文件路径：`modules/port-import.php`
行数：2517 行
命名空间：`Jelly_Catalog\Modules`

---

## 架构设计

### 依赖

| 依赖 | 用途 |
|------|------|
| `WP_Error` | 统一错误传递 |
| `Throwable` | 异常捕获 |
| `ZipArchive` | ZIP 解压 |

### 对外接口（public methods）

该 Trait 通过 **WordPress action hooks** 被调用，不直接对外暴露 API。外部调用方式：

```php
add_action( 'wp_ajax_jc_start_import_products',  array( $this, 'ajax_start_import_products' ) );
add_action( 'wp_ajax_jc_process_import_products', array( $this, 'ajax_process_import_products' ) );
add_action( 'admin_post_jc_import_products',       array( $this, 'import_products' ) );
add_action( 'admin_notices',                        array( $this, 'import_notices' ) );
```

---

## 方法清单

### 公开方法（public）

| 方法 | 行号 | 说明 |
|------|------|------|
| `import_products()` | 83 | 入口方法（表单提交方式） |
| `ajax_start_import_products()` | 132 | AJAX 初始化导入任务 |
| `ajax_process_import_products()` | 172 | AJAX 分批处理导入任务 |
| `import_notices()` | 2447 | 显示导入结果通知 |

### 私有方法（private）

#### 日志系统

| 方法 | 行号 | 说明 |
|------|------|------|
| `log($message, $level)` | 55 | 写入日志（按级别过滤） |

#### 导入任务管理

| 方法 | 行号 | 说明 |
|------|------|------|
| `create_import_job()` | 223 | 创建导入任务，保存上传文件与状态 |
| `save_import_job($job)` | 2073 | 保存任务到 transient |
| `get_import_job($job_id)` | 2080 | 从 transient 读取任务 |
| `get_import_job_transient_key($job_id)` | 2100 | 生成 transient key (`jc_import_job_{id}`) |
| `format_import_job_response($job, $message)` | 2107 | 格式化 AJAX 响应 |

#### CSV 处理

| 方法 | 行号 | 说明 |
|------|------|------|
| `has_import_upload($field)` | 368 | 判断上传文件是否存在 |
| `get_import_upload($field)` | 378 | 获取上传文件信息 |
| `save_uploaded_import_csv($upload, $target, $label)` | 390 | 保存上传的 CSV 到临时目录 |
| `inspect_import_csv($csv_file)` | 1856 | 读取表头并统计总行数 |
| `skip_csv_bom($handle)` | 1911 | 跳过 UTF-8 BOM |
| `get_import_dynamic_column_counts($headers)` | 1921 | 统计动态 FAQ 和属性列数量 |

#### 分批处理与断点续传

| 方法 | 行号 | 说明 |
|------|------|------|
| `process_import_batch($job)` | 418 | 核心分批处理逻辑 |
| `handle_expired_import_lock($job)` | 616 | 处理过期锁，跳过问题行 |
| `prepare_import_current_row($job, $row, ...)` | 693 | 保存当前处理的 CSV 行信息 |
| `get_import_row_summary($row, $headers)` | 717 | 获取行摘要（source_id, title） |
| `log_import_current_row_failure($row, $reason)` | 733 | 记录失败产品详情 |

#### 单行处理与重试

| 方法 | 行号 | 说明 |
|------|------|------|
| `process_import_row_with_retries($row, ...)` | 763 | 带重试处理单行产品数据 |
| `process_import_row($row, ...)` | 817 | 处理单行产品数据（核心编排） |
| `get_import_row_key($job, $row, $num)` | 810 | 生成幂等标记（MD5） |

#### 产品 CRUD

| 方法 | 行号 | 说明 |
|------|------|------|
| `save_import_post($data, $row_key)` | 853 | 创建或更新产品基础字段 |
| `get_import_post_status($data, $default)` | 920 | 获取导入产品状态 |
| `find_existing_import_post_id($data, $row_key)` | 936 | 查找现有产品（ID / row_key / Slug） |
| `find_existing_import_post_id_by_slug($data)` | 971 | 根据 Slug 查找产品 |
| `sanitize_import_post_slug($slug)` | 995 | 规范化产品 Slug |
| `find_imported_post_by_row_key($row_key)` | 1769 | 根据 row_key 查找已导入产品 |

#### 图片导入

| 方法 | 行号 | 说明 |
|------|------|------|
| `prepare_import_images($temp_dir)` | 1807 | 解压 ZIP 并定位图片目录 |
| `build_image_index($images_path)` | 1948 | 创建文件名索引 |
| `load_image_index($image_index_file)` | 2001 | 读取图片索引 |
| `maybe_import_featured_image($post_id, ...)` | 1002 | 导入特色图像 |
| `maybe_import_gallery_images($post_id, ...)` | 1026 | 导入画廊图像 |
| `maybe_process_description_images($post_id, ...)` | 1071 | 处理 Description 中的 `[file.ext]` 占位符 |
| `import_image_reference($ref, $post_id, ...)` | 1120 | 导入单张图片引用 |
| `sanitize_import_image_reference($ref)` | 2052 | 规范化图片引用路径 |
| `resolve_import_image_path($path, $ref, $idx)` | 2015 | 根据文件名查找图片路径 |
| `find_file_in_subdirectories($root, $fn)` | 2157 | 在子目录中递归查找文件 |
| `find_images_directory($path)` | 2208 | 查找包含图片的目录 |
| `import_image_as_attachment($path, $post_id, ...)` | 2302 | 将图片导入为附件 |
| `find_imported_attachment_by_hash($hash)` | 2393 | 根据文件 hash 查找已导入附件（去重） |

#### 分类与标签

| 方法 | 行号 | 说明 |
|------|------|------|
| `maybe_update_import_product_categories($post_id, $data)` | 1190 | 更新产品分类（支持名称/Slug 路径） |
| `maybe_update_import_terms($post_id, $data, $col, $tax)` | 1141 | 更新分类或标签 |
| `parse_import_multi_value_list($value)` | 1235 | 解析 `|` 分隔的多值列表 |
| `parse_import_category_name_path($path)` | 1245 | 解析 `>` 分隔的分类名称路径 |
| `parse_import_category_slug_path($path)` | 1255 | 解析 `/` 分隔的分类 Slug 路径 |
| `resolve_import_product_category_term($slug_path, $name_path)` | 1266 | 解析或创建层级分类 |
| `format_import_category_name_from_slug($slug)` | 1328 | 根据 slug 生成兜底分类名称 |

#### 分类 CSV 导入

| 方法 | 行号 | 说明 |
|------|------|------|
| `import_product_categories_from_csv($csv_file)` | 1343 | 从分类 CSV 批量导入分类 |
| `import_product_category_row($data)` | 1440 | 导入单个分类 CSV 行 |
| `maybe_update_import_product_category_meta($term_id, $data)` | 1519 | 更新分类 Rank Math 元数据 |

#### FAQ 与属性

| 方法 | 行号 | 说明 |
|------|------|------|
| `parse_import_faqs($data, $faq_count)` | 1563 | 解析 FAQ 数据 |
| `parse_import_attributes($data, $attr_count)` | 1597 | 解析属性数据 |
| `decode_import_collection($value)` | 1631 | 解析 base64 JSON 集合字段 |
| `maybe_update_import_collection_meta($post_id, $key, $items, $label)` | 1646 | 更新集合型元数据 |

#### 产品元数据

| 方法 | 行号 | 说明 |
|------|------|------|
| `maybe_update_import_product_meta($post_id, $data)` | 1670 | 更新产品基础元数据（SKU 等） |
| `maybe_update_import_rank_math_meta($post_id, $data)` | 1714 | 更新 Rank Math SEO 元数据 |
| `get_import_rank_math_meta_map()` | 1749 | 获取 SEO 字段映射表 |

#### 工具方法

| 方法 | 行号 | 说明 |
|------|------|------|
| `move_uploaded_file_to_path($src, $dst)` | 1792 | 移动上传文件到指定路径 |
| `copy_file_with_retries($src, $dst, $ctx)` | 2247 | 带重试的文件复制 |
| `zip_has_safe_paths($zip)` | 2187 | 校验 ZIP 内路径安全性（防目录穿越） |
| `cleanup_stale_import_temp_dirs()` | 2271 | 清理过期临时目录 |
| `rrmdir($dir)` | 2428 | 递归删除目录 |
| `get_import_error_code($error)` | 2128 | 获取前端可识别的错误代码 |
| `is_import_error_retryable($error)` | 2144 | 判断错误是否可重试 |

---

## 完整导入流程

### 概览

```
上传 CSV + 可选分类 CSV + 可选 ZIP 图片
         │
         ▼
   create_import_job()          ← 初始化：解析 CSV、解压图片、构建索引
         │
         ▼
   ajax_process_import_products()  ← AJAX 循环调用
         │
         ▼
   process_import_batch()       ← 每批处理 N 行（默认 5）
         │
         ├── process_import_row_with_retries()  ← 带重试
         │       └── process_import_row()
         │             ├── save_import_post()            创建/更新产品
         │             ├── maybe_import_featured_image()
         │             ├── maybe_import_gallery_images()
         │             ├── maybe_process_description_images()
         │             ├── maybe_update_import_product_categories()
         │             ├── maybe_update_import_terms()        (Tags)
         │             ├── maybe_update_import_collection_meta()  (FAQs)
         │             ├── maybe_update_import_collection_meta()  (Attributes)
         │             ├── maybe_update_import_product_meta()
         │             └── maybe_update_import_rank_math_meta()
         │
         ▼
   全部处理完成 → 清理临时目录 → 设置完成状态
```

### 1. 任务创建阶段 (`create_import_job`)

1. 设置时间限制 120 秒
2. 清理过期临时目录
3. 清空旧日志文件
4. 验证上传：至少需要一个 CSV 文件（产品 CSV 或分类 CSV）
5. 生成 UUID 任务 ID，创建临时目录 `wp-content/uploads/jc_import_{uuid}/`
6. 处理分类 CSV（如果存在）：
   - 保存到 `product-categories.csv`
   - 调用 `import_product_categories_from_csv()` 导入
7. 处理产品 CSV（如果存在）：
   - 保存到 `products.csv`
   - 解析图片 ZIP → 创建图片索引 JSON
   - 调用 `inspect_import_csv()` 获取表头和行数
8. 构建 job 数组，包含：
   - 任务元数据（ID、用户、状态、时间戳）
   - CSV 文件路径、表头、FAQ/属性列数
   - 图片路径和索引文件路径
   - 计数器（total、processed、imported、errors）
   - 重试配置（max_retries）
9. 保存到 transient，有效期 `DAY_IN_SECONDS`

**特殊情况：** 如果只上传了分类 CSV 而没有产品 CSV，任务直接标记为 `complete`，结果写入 `jc_import_result` transient。

### 2. 分批处理阶段 (`process_import_batch`)

#### 锁机制

- 任务有 `locked_at` 字段，表示上次请求时间
- 锁 TTL 默认 300 秒（可过滤 `jc_import_batch_lock_ttl`）
- 如果锁未过期 → 返回 `waiting` 状态，前端延迟重试（默认 2000ms）
- 如果锁已过期 → 调用 `handle_expired_import_lock()` 处理

#### 分批读取 CSV

- 使用文件指针 `fseek()` 跳转到 `job['offset']` 位置（断点续传）
- 每批处理 `jc_import_batch_size` 行（默认 5）
- 时间限制 `jc_import_batch_time_limit`（默认 20 秒）
- 取两者先到者

#### 每行处理

1. 记录当前行信息到 `job['current_row']`（用于崩溃恢复的容错处理）
2. 调用 `process_import_row_with_retries()` 处理
3. 更新 `offset` 为文件指针当前位置
4. 更新 `processed`、`imported`、`errors` 计数

### 3. 完成阶段

- 当 `processed >= total` 时状态设为 `complete`
- 将结果写入 `jc_import_result` transient（有效期 60 秒）
- 调用 `rrmdir()` 清理临时目录
- 后续前端可通过 `import_notices()` 显示结果

---

## CSV 格式参考

### 产品 CSV 列

| 列名 | 必填 | 说明 |
|------|------|------|
| `ID` | 否 | 现有产品 ID，用于更新而非新建 |
| `Title` | 否 | 产品标题 |
| `Slug` | 否 | 产品 URL slug |
| `Status` | 否 | 产品状态（`publish`/`draft`/`pending`/`private`） |
| `Short Description` | 否 | 简短描述 → `post_excerpt` |
| `Description` | 否 | 详细描述 → `post_content`，支持 `[filename.ext]` 图片占位符 |
| `Featured Image` | 否 | 特色图文件名（可含相对路径），留空清空 |
| `Gallery Images` | 否 | 画廊图片，逗号分隔，留空清空 |
| `Categories` | 否 | 分类名称路径，`\|` 分隔多分类，`>` 表示层级（如 `父分类 > 子分类`） |
| `Category Slugs` | 否 | 分类 Slug 路径，斜杠 `/` 表示层级，`\|` 分隔多分类 |
| `Tags` | 否 | 标签，`\|` 分隔，留空清空 |
| `Status` | 否 | 产品状态 |
| `SKU` 或 `product_sku` | 否 | 产品 SKU |
| `FAQ_Q_1` ~ `FAQ_Q_N` | 否 | FAQ 问题 |
| `FAQ_A_1` ~ `FAQ_A_N` | 否 | FAQ 答案 |
| `FAQs` | 否 | FAQ 的 base64 JSON（备选格式） |
| `Attribute_Name_1` ~ `Attribute_Name_N` | 否 | 属性名 |
| `Attribute_Value_1` ~ `Attribute_Value_N` | 否 | 属性值 |
| `Attributes` | 否 | 属性的 base64 JSON（备选格式） |
| `Focus Keyword` | 否 | Rank Math 焦点关键词 |
| `SEO Title` | 否 | Rank Math SEO 标题 |
| `Meta Description` | 否 | Rank Math Meta 描述 |

### 分类 CSV 列

| 列名 | 必填 | 说明 |
|------|------|------|
| `Title` | 是* | 分类名称（若缺则从 Slug 生成） |
| `Slug` | 是* | 分类 Slug（若缺则从 Title 生成） |
| `Description` | 否 | 分类描述 |
| `Parent Slug` | 否 | 父级分类的 Slug |
| `Focus Keyword` | 否 | Rank Math 焦点关键词 |
| `SEO Title` | 否 | Rank Math SEO 标题 |
| `Meta Description` | 否 | Rank Math Meta 描述 |

\* Title 和 Slug 至少提供一个。

### CSV 格式要求

- 编码：UTF-8（支持 BOM）
- 分隔符：逗号
- 换行符：标准 CSV 换行

---

## Job 对象结构

```php
$job = array(
    'id'                  => 'uuid-string',         // 任务唯一 ID
    'user_id'             => int,                   // 操作用户 ID
    'status'              => 'pending|running|waiting|complete|error',
    'csv_file'            => '/path/to/products.csv',
    'temp_dir'            => '/path/to/jc_import_{id}/',
    'images_path'         => '/path/to/images/',
    'image_index_file'    => '/path/to/image-index.json',
    'headers'             => array(),               // CSV 表头数组
    'faq_count'           => int,                   // FAQ 列对数
    'attribute_count'     => int,                   // 属性列对数
    'offset'              => int,                   // 文件指针偏移（断点）
    'total'               => int,                   // CSV 数据总行数
    'processed'           => int,                   // 已处理行数
    'imported'            => int,                   // 成功导入数
    'errors'              => int,                   // 失败数
    'categories_imported' => int,                   // 分类导入数
    'category_errors'     => int,                   // 分类错误数
    'max_retries'         => int,                   // 每行最大重试次数
    'message'             => '状态消息',
    'created_at'          => timestamp,
    'updated_at'          => timestamp,
    // 运行时字段：
    'locked_at'           => timestamp,             // 锁时间戳
    'lock_token'          => 'uuid',                // 锁令牌
    'current_row'         => array(),               // 当前处理行信息
    'next_delay'          => int,                   // 前端重试延迟 ms
    'last_error'          => string,                // 最后错误消息
);
```

### Job 状态机

```
pending ──→ running ──→ complete
  │            │
  └────────────┼──→ error
               │
               └──→ waiting (锁冲突时)
```

---

## 错误处理与重试

### 分级重试策略

| 层级 | 说明 | 配置过滤器 |
|------|------|------------|
| **行级重试** | 单行处理失败时，立即重试 | `jc_import_row_max_retries`（默认 2）<br>`jc_import_row_retry_delay`（默认 250ms） |
| **批次级重试** | 批次整体异常（如超时），前端自动重新发起请求 | 前端通过 AJAX 轮询 |
| **崩溃恢复** | 请求中断导致锁过期，下次请求接管 | `jc_import_lock_wait_delay`（默认 2000ms）<br>`jc_import_fatal_row_grace_period`（默认 900s） |

### 错误代码

| 代码 | 含义 | 可重试 |
|------|------|--------|
| `missing_import_file` | 未上传 CSV 文件 | 否 |
| `invalid_csv_file` | CSV 格式无效 | 否 |
| `cannot_read_file` | 无法读取文件 | 否 |
| `file_upload_failed` | 文件上传失败 | 否 |
| `import_batch_failed` | 批次处理异常 | 是 |

### 行级重试执行流程

```
process_import_row_with_retries($row, ...)
    ├── attempt 1: 立即执行 process_import_row()
    │   ├── 成功 → return true
    │   └── 失败/异常 → 等待 retry_delay ms → attempt 2
    ├── attempt 2: 等待 retry_delay*2 ms → 重试
    ├── ...
    └── max_retries+1 次后 → return false（标记该行错误，跳过继续）
```

### 崩溃容错机制

当请求因超时等原因中止时：

1. `job['current_row']` 保留了最后处理的行信息（包含 CSV 偏移位置）
2. 下次请求检测到锁过期 → `handle_expired_import_lock()` 接管
3. 判断逻辑：
   - 如果当前行在宽限期（`grace_period`，默认 900 秒）内 → 返回 `waiting`（该行可能仍在处理）
   - 如果在宽限期外 → 根据 `fatal_attempts` 判断：
     - 未超过 `jc_import_fatal_row_max_retries` → 重试该行
     - 已超过 → 跳过该行，从行尾 `end_offset` 继续

---

## 图片处理

### 图片引用格式

CSV 中的图片列（Featured Image、Gallery Images）支持以下引用方式：

- 纯文件名：`product-image.jpg`
- 相对路径：`subfolder/product-image.jpg`
- 反斜杠路径：`subfolder\product-image.jpg`（自动转换为正斜杠）

Descriptions 中可使用 `[filename.ext]` 占位符，导入时自动替换为 `<img>` 标签。

### 图片查找策略

`resolve_import_image_path()` 按以下顺序查找：

1. 直接路径：`{images_path}/{reference}`
2. 图片索引精确匹配（原始引用）
3. 图片索引小写匹配
4. 仅文件名匹配（忽略目录结构）
5. 仅文件名小写匹配
6. 递归扫描子目录

### 图片去重

`import_image_as_attachment()` 使用 **MD5 文件哈希** 进行去重：
- 导入前计算文件 MD5
- 检查 `_jc_import_file_hash` post meta 是否已存在相同哈希的附件
- 存在则复用，避免重复上传

### 安全措施

- `sanitize_import_image_reference()` 拒绝绝对路径和 `../` 目录穿越
- `zip_has_safe_paths()` 校验 ZIP 内所有路径安全
- `copy_file_with_retries()` 带指数退避的文件复制重试

---

## 分类导入

### 产品分类（产品 CSV 行内）

支持两种分类指定方式：

**名称路径（Categories 列）：**
```
父分类 > 子分类 > 孙分类
```
多个分类用 `|` 分隔：
```
分类A > 子A1 | 分类B > 子B1
```

**Slug 路径（Category Slugs 列）：**
```
parent-slug/child-slug
```
多个用 `|` 分隔。

两种方式可以同时使用，按索引顺序配对。缺失的分类自动创建。

### 分类 CSV（独立导入）

`import_product_categories_from_csv()` 使用**多轮遍历**处理：
- 第一轮：无父级或父级已存在的分类 → 直接导入
- 后续轮次：父级被前一轮创建的分类 → 继续导入
- 直到所有分类导入完成或连续两轮无进展
- 最终仍未导入的（父级不存在）→ 标记为错误

**避免子分类先于父分类创建：** 使用 `'defer'` 返回值延迟处理。

---

## 日志系统

### 日志文件

路径由主类设置 `$this->log_file`，每次新任务开始时清空。

### 日志级别

| 级别 | 值 | 含义 |
|------|-----|------|
| `debug` | 0 | 详细调试信息 |
| `notice` | 1 | 一般操作记录 |
| `warning` | 2 | 警告（行失败、图片缺失等） |
| `error` | 3 | 错误（文件无法读写等） |

### 最低日志级别

通过 `jc_import_log_min_level` 过滤器控制，默认 `notice`。

### 日志格式

```
[YYYY-MM-DD HH:II:SS] [LEVEL] message
```

---

## 可用的 WordPress 过滤器

| 过滤器 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `jc_import_log_min_level` | string | `'notice'` | 最低日志级别 |
| `jc_import_prepare_time_limit` | int | `120` | 准备阶段时间限制（秒） |
| `jc_import_batch_size` | int | `5` | 每批处理行数 |
| `jc_import_batch_time_limit` | int | `20` | 每批时间限制（秒） |
| `jc_import_batch_lock_ttl` | int | `300` | 锁 TTL（秒） |
| `jc_import_lock_wait_delay` | int | `2000` | 锁等待重试延迟（毫秒） |
| `jc_import_row_max_retries` | int | `2` | 每行最大重试次数 |
| `jc_import_row_retry_delay` | int | `250` | 行重试延迟（毫秒） |
| `jc_import_fatal_row_grace_period` | int | `900` | 崩溃行宽限期（秒） |
| `jc_import_fatal_row_max_retries` | int | `0` | 崩溃行最大重试次数 |
| `jc_import_file_copy_retries` | int | `2` | 文件复制重试次数 |
| `jc_import_file_copy_retry_delay` | int | `250` | 文件复制延迟（毫秒） |
| `jc_import_temp_dir_ttl` | int | `172800` | 临时目录过期时间（秒，默认 2 天） |

---

## 数据存储

| 存储方式 | Key | 用途 | 有效期 |
|----------|-----|------|--------|
| Transient | `jc_import_job_{job_id}` | 导入任务状态 | `DAY_IN_SECONDS` |
| Transient | `jc_import_result` | 导入结果（供通知显示） | 60 秒 |
| Post Meta | `_jc_import_row_key` | 产品行幂等标记（MD5） | 永久 |
| Post Meta | `_jc_import_file_hash` | 附件文件哈希（去重） | 永久 |
| Post Meta | `_jc_import_source_reference` | 附件源引用文件名 | 永久 |
| Term Meta | `rank_math_focus_keyword` | 分类 SEO 关键词 | 永久 |
| Term Meta | `rank_math_title` | 分类 SEO 标题 | 永久 |
| Term Meta | `rank_math_description` | 分类 SEO 描述 | 永久 |
| Post Meta | `rank_math_*` | 产品 SEO 元数据 | 永久 |
| Post Meta | `_product_image_gallery` | 画廊图像 IDs | 永久 |
| Post Meta | `_product_faqs` | FAQ 数据 | 永久 |
| Post Meta | `_product_attributes` | 属性数据 | 永久 |
| Post Meta | `product_sku` | 产品 SKU | 永久 |
| 文件系统 | `wp-content/uploads/jc_import_{uuid}/` | 导入临时文件 | 任务结束后自动清理 |

---

## 安全性

1. **权限检查：** 所有入口方法要求 `manage_options` 权限
2. **Nonce 验证：** 所有提交请求校验 `jc_import_products` nonce
3. **路径安全：** 图片引用经过 `sanitize_import_image_reference()` 过滤，拒绝绝对路径和目录穿越
4. **ZIP 安全：** `zip_has_safe_paths()` 防止 ZIP slip 攻击
5. **文件类型验证：** 图片导入前通过 `wp_check_filetype()` 和 `getimagesize()` 双重校验
6. **锁机制：** 防止并发导入冲突
7. **临时目录清理：** `cleanup_stale_import_temp_dirs()` 防止废弃目录堆积
8. **用户隔离：** `get_import_job()` 验证 `user_id`，防止跨用户访问任务
