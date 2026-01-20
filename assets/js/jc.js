document.addEventListener("DOMContentLoaded", function () {
    const topElement = document.querySelector(".jc-gallery-top");
    
    if (!topElement) return;

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
});