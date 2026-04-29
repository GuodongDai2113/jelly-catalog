document.addEventListener("DOMContentLoaded", function () {
  // --- Accordion Category List Logic ---
  const catLists = document.querySelectorAll(".jc-cat-list");
  catLists.forEach((list) => {
    list.addEventListener("click", function (e) {
      const toggle = e.target.closest(".jc-cat-list__toggle");
      if (!toggle) return;

      e.preventDefault();
      e.stopPropagation();

      const item = toggle.closest(".jc-cat-list__item");
      if (item) {
        const isExpanded = item.classList.contains("is-expanded");
        item.classList.toggle("is-expanded");
        toggle.setAttribute("aria-expanded", !isExpanded);
      }
    });
  });
});
