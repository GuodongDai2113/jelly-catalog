(function ($) {
  $(function () {
    if (!$(".post-type-product").length) {
      return;
    }
    const $titleAction = $(".wrap .page-title-action").first();

    if ($titleAction.length) {
      const $exportBtn = $("<a>", {
        href: "#",
        class: "page-title-action jc-export-products",
        text: "导出产品",
      });

      const $importBtn = $("<a>", {
        href: "#",
        class: "page-title-action jc-import-products",
        text: "导入产品",
      });

      $exportBtn.insertAfter($titleAction);
      $importBtn.insertAfter($exportBtn);
    }

    let $fileInput = $("#jc-catalog-import-file");
    if (!$fileInput.length) {
      $fileInput = $("<input>", {
        type: "file",
        id: "jc-catalog-import-file",
        accept: ".csv",
        style: "display:none;",
      }).appendTo("body");
    }

    $(".jc-export-products").on("click", function (event) {
      event.preventDefault();
      if (!window.jc_ajax) {
        window.alert("缺少 AJAX 配置。");
        return;
      }

      const $btn = $(this)
        .addClass("disabled")
        .attr("aria-disabled", "true")
        .text("正在导出…");

      $.post(window.jc_ajax.ajax_url, {
        action: "jc_catalog_export",
        nonce: window.jc_ajax.nonce,
      })
        .done(function (response) {
          if (
            response &&
            response.success &&
            response.data &&
            response.data.url
          ) {
            window.location.href = response.data.url;
          } else {
            window.alert(
              response.data && response.data.message
                ? response.data.message
                : "导出失败。"
            );
          }
        })
        .fail(function () {
          window.alert("导出请求失败，请稍后重试。");
        })
        .always(function () {
          $btn
            .removeClass("disabled")
            .removeAttr("aria-disabled")
            .text("导出产品");
        });
    });

    $(".jc-import-products").on("click", function (event) {
      event.preventDefault();
      if (!window.jc_ajax) {
        window.alert("缺少 AJAX 配置。");
        return;
      }
      $fileInput.trigger("click");
    });

    $fileInput.on("change", function () {
      const file = this.files && this.files[0];
      if (!file) {
        return;
      }

      const formData = new FormData();
      formData.append("action", "jc_catalog_import");
      formData.append("nonce", window.jc_ajax.nonce);
      formData.append("file", file);

      const $btn = $(".jc-import-products")
        .addClass("disabled")
        .attr("aria-disabled", "true")
        .text("正在导入…");

      $.ajax({
        url: window.jc_ajax.ajax_url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
      })
        .done(function (response) {
          if (response && response.success) {
            window.alert(
              "导入完成，新增：" +
                (response.data.imported || 0) +
                "，更新：" +
                (response.data.updated || 0)
            );
            window.location.reload();
          } else {
            window.alert(
              response.data && response.data.message
                ? response.data.message
                : "导入失败。"
            );
          }
        })
        .fail(function () {
          window.alert("导入请求失败，请稍后重试。");
        })
        .always(function () {
          $btn
            .removeClass("disabled")
            .removeAttr("aria-disabled")
            .text("导入产品");
          $fileInput.val("");
        });
    });
  });
})(jQuery);
