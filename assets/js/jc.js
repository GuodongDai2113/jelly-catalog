document.addEventListener("DOMContentLoaded", function () {
  // --- Swiper Gallery Logic ---
  const topElement = document.querySelector(".jc-gallery-top");
  if (topElement) {
    const thumbsElement = document.querySelector(".jc-gallery-thumbs");
    let thumbsSwiper = null;

    if (thumbsElement) {
      thumbsSwiper = new Swiper(".jc-gallery-thumbs", {
        spaceBetween: 16,
        slidesPerView: 4,
        freeMode: true,
        watchSlidesProgress: true,
      });
    }

    new Swiper(".jc-gallery-top", {
      spaceBetween: 16,
      navigation: {
        nextEl: ".jc-gallery-top .swiper-button-next",
        prevEl: ".jc-gallery-top .swiper-button-prev",
      },
      thumbs: thumbsSwiper ? { swiper: thumbsSwiper } : undefined,
    });
  }

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
