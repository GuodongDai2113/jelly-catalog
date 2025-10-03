jQuery(document).ready(function ($) {
  let hotInstance;
  let originalData = [];
  let currentPage = 1;
  let totalPages = 1;
  let perPage = 5;
  
  // 加载数据的函数
  function loadProducts(page = 1) {
    $.post(
      jc_ajax.ajax_url,
      {
        action: "get_products_sheet",
        nonce: jc_ajax.nonce,
        page: page,
        per_page: perPage
      },
      function (res) {
        const container = document.getElementById("hot");
        originalData = JSON.parse(JSON.stringify(res.products)); // Deep copy of original data
        
        // 更新分页信息
        currentPage = res.page;
        totalPages = res.total_pages;
        $('#current-page').text(currentPage);
        $('#total-pages').text(totalPages);
        
        // 更新按钮状态
        updatePaginationButtons();
        
        if (hotInstance) {
          // 如果实例已存在，更新数据
          hotInstance.updateSettings({
            data: res.products
          });
        } else {
          // 创建新的Handsontable实例
          hotInstance = new Handsontable(container, {
            data: res.products,
            rowHeaders: true,
            colHeaders: ["ID", "名称", "摘要", "分类"],
            columns: [
              { data: "ID", readOnly: true, width: 50 },
              { data: "post_title", width: 200 },
              { data: "post_excerpt", width: 300 },
              { data: "categories", width: 200 },
            ],
            colWidths: [50, 200, 300, 200],
            wordWrap: true,
            licenseKey: "non-commercial-and-evaluation",
          });
        }
      }
    );
  }
  
  // 更新分页按钮状态
  function updatePaginationButtons() {
    $('#first-page, #prev-page').prop('disabled', currentPage <= 1);
    $('#next-page, #last-page').prop('disabled', currentPage >= totalPages);
  }
  
  // 初始化加载第一页数据
  loadProducts(1);
  
  // 分页按钮事件处理
  $('#first-page').on('click', function() {
    if (currentPage > 1) {
      loadProducts(1);
    }
  });
  
  $('#prev-page').on('click', function() {
    if (currentPage > 1) {
      loadProducts(currentPage - 1);
    }
  });
  
  $('#next-page').on('click', function() {
    if (currentPage < totalPages) {
      loadProducts(currentPage + 1);
    }
  });
  
  $('#last-page').on('click', function() {
    if (currentPage < totalPages) {
      loadProducts(totalPages);
    }
  });
  
  $('#goto-page-btn').on('click', function() {
    const page = parseInt($('#goto-page').val());
    if (page >= 1 && page <= totalPages && page !== currentPage) {
      loadProducts(page);
    }
  });
  
  $('#goto-page').on('keyup', function(e) {
    if (e.key === 'Enter') {
      $('#goto-page-btn').click();
    }
  });
  
  // 保存按钮事件处理
  $('#save-sheet').on('click', function() {
    if (!hotInstance) return;
    
    const newData = hotInstance.getData();
    const changedData = [];
    
    // 获取所有数据（包括ID）
    const productsData = hotInstance.getSourceData();
    
    // 比较原始数据和新数据
    productsData.forEach((row, rowIndex) => {
      const productId = row.ID;
      
      // 查找原始行数据
      const originalRow = originalData.find(item => item.ID == productId);
      
      if (originalRow) {
        const changes = {};
        let hasChanges = false;
        
        // 检查名称是否有变化
        const newName = hotInstance.getDataAtCell(rowIndex, 1); // 第二列是名称
        if (newName !== originalRow.post_title) {
          changes.post_title = newName;
          hasChanges = true;
        }
        
        // 检查摘要是否有变化
        const newExcerpt = hotInstance.getDataAtCell(rowIndex, 2); // 第三列是摘要
        if (newExcerpt !== originalRow.post_excerpt) {
          changes.post_excerpt = newExcerpt;
          hasChanges = true;
        }
        
        // 如果有变化，添加到待保存列表
        if (hasChanges) {
          changes.ID = productId;
          changedData.push(changes);
        }
      }
    });
    
    // 发送更改到服务器
    if (changedData.length > 0) {
      $.post(
        jc_ajax.ajax_url,
        {
          action: "save_products_sheet",
          nonce: jc_ajax.nonce,
          data: changedData
        },
        function (res) {
          if (res.success) {
            alert('保存成功');
            // 更新原始数据副本
            productsData.forEach((row, rowIndex) => {
              const productId = row.ID;
              const originalRow = originalData.find(item => item.ID == productId);
              
              if (originalRow) {
                originalRow.post_title = hotInstance.getDataAtCell(rowIndex, 1);
                originalRow.post_excerpt = hotInstance.getDataAtCell(rowIndex, 2);
              }
            });
          } else {
            alert('保存失败: ' + res.data);
          }
        }
      );
    } else {
      alert('没有更改需要保存');
    }
  });
});