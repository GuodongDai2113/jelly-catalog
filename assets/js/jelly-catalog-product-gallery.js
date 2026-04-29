(function ($) {
  "use strict";

  const initProductGallery = () => {
    console.log("initProductGallery");

    const $galleries = $(
      '[data-widget="product-gallery"].product-gallery--interactive'
    );

    if (!$galleries.length) {
      return;
    }

    const clampValue = (value, min, max) => Math.min(Math.max(value, min), max);

    const getClientCoordinate = (event, axis = "x") => {
      const originalEvent = event.originalEvent || event;
      const touch =
        (originalEvent.touches && originalEvent.touches[0]) ||
        (originalEvent.changedTouches && originalEvent.changedTouches[0]) ||
        originalEvent;

      return axis === "y" ? touch.clientY : touch.clientX;
    };

    $galleries.each(function (galleryIndex) {
      const $gallery = $(this);

      if ($gallery.data("jellyCatalogProductGalleryReady")) {
        return;
      }

      $gallery.data("jellyCatalogProductGalleryReady", true);

      const $viewport = $gallery.find("[data-gallery-viewport]");
      const $track = $gallery.find("[data-gallery-track]");
      const $slides = $gallery.find("[data-gallery-slide]");
      const $thumbsViewport = $gallery.find("[data-gallery-thumbs-viewport]");
      const $thumbs = $gallery.find("[data-gallery-thumb]");
      const $dots = $gallery.find("[data-gallery-dot]");
      const $prevButton = $gallery.find("[data-gallery-prev]");
      const $nextButton = $gallery.find("[data-gallery-next]");
      const maxIndex = $slides.length - 1;
      const visibleThumbs = parseInt($gallery.data("visible-thumbs"), 10) || 4;
      const galleryElement = $gallery[0];
      const thumbsViewportElement = $thumbsViewport[0];
      const dragNamespace = `.productGallery${galleryIndex}`;
      const mobileMedia = window.matchMedia("(max-width: 767px)");
      let currentIndex = 0;
      let startX = 0;
      let startY = 0;
      let deltaX = 0;
      let isDragging = false;
      let hasDragged = false;
      let dragAxis = "";

      if (
        !$viewport.length ||
        !$track.length ||
        !$thumbsViewport.length ||
        !$thumbs.length ||
        maxIndex < 1
      ) {
        return;
      }

      const isMobile = () => mobileMedia.matches;

      const getCssLengthInPixels = (
        styles,
        propertyName,
        fallbackValue = 0
      ) => {
        const rawValue = styles.getPropertyValue(propertyName);
        const trimmedValue = rawValue.trim();

        if (!trimmedValue) {
          return fallbackValue;
        }

        const parsedValue = parseFloat(trimmedValue);

        if (!Number.isFinite(parsedValue)) {
          return fallbackValue;
        }

        if (trimmedValue.endsWith("rem")) {
          const rootFontSize = parseFloat(
            window.getComputedStyle(document.documentElement).fontSize
          );
          return (
            parsedValue * (Number.isFinite(rootFontSize) ? rootFontSize : 16)
          );
        }

        if (trimmedValue.endsWith("em")) {
          const elementFontSize = parseFloat(
            window.getComputedStyle($gallery[0]).fontSize
          );
          return (
            parsedValue *
            (Number.isFinite(elementFontSize) ? elementFontSize : 16)
          );
        }

        return parsedValue;
      };

      const syncMetrics = () => {
        if (isMobile()) {
          galleryElement.style.removeProperty("--product-gallery-thumb-size");
          galleryElement.style.removeProperty(
            "--product-gallery-thumbs-height"
          );
          return;
        }

        const styles = window.getComputedStyle(galleryElement);
        const galleryWidth = $gallery.innerWidth() || 0;
        const columnGap = getCssLengthInPixels(
          styles,
          "--product-gallery-column-gap",
          16
        );
        const thumbGap = getCssLengthInPixels(
          styles,
          "--product-gallery-thumb-gap",
          12
        );
        const visibleCount = Math.max(1, visibleThumbs);

        if (!galleryWidth || visibleCount <= 0) {
          return;
        }

        const thumbSize =
          (galleryWidth - columnGap - thumbGap * (visibleCount - 1)) /
          (visibleCount + 1);
        const safeThumbSize = Math.max(0, thumbSize);
        const thumbsHeight =
          safeThumbSize * visibleCount + thumbGap * (visibleCount - 1);

        galleryElement.style.setProperty(
          "--product-gallery-thumb-size",
          `${safeThumbSize}px`
        );
        galleryElement.style.setProperty(
          "--product-gallery-thumbs-height",
          `${thumbsHeight}px`
        );
      };

      const scrollThumbsIntoView = (animate = true) => {
        const thumbElement = $thumbs.get(currentIndex);
        if (!thumbElement || !thumbsViewportElement) {
          return;
        }

        const mobile = isMobile();
        const viewportSize = mobile
          ? thumbsViewportElement.clientWidth
          : thumbsViewportElement.clientHeight;
        const trackSize = mobile
          ? thumbsViewportElement.scrollWidth
          : thumbsViewportElement.scrollHeight;
        const thumbOffset = mobile
          ? thumbElement.offsetLeft
          : thumbElement.offsetTop;
        const thumbSize = mobile
          ? thumbElement.offsetWidth
          : thumbElement.offsetHeight;
        const maxScroll = Math.max(0, trackSize - viewportSize);
        const targetScroll = clampValue(
          thumbOffset - (viewportSize - thumbSize) / 2,
          0,
          maxScroll
        );
        const behavior = animate ? "smooth" : "auto";

        try {
          thumbsViewportElement.scrollTo(
            mobile
              ? { left: targetScroll, behavior }
              : { top: targetScroll, behavior }
          );
        } catch (error) {
          if (mobile) {
            thumbsViewportElement.scrollLeft = targetScroll;
          } else {
            thumbsViewportElement.scrollTop = targetScroll;
          }
        }
      };

      const syncSlideState = () => {
        $slides.each(function (index) {
          const isActive = index === currentIndex;
          $(this)
            .toggleClass("is-active", isActive)
            .attr("aria-hidden", isActive ? "false" : "true");
        });
      };

      const syncPressableState = ($elements) => {
        $elements.each(function (index) {
          const isActive = index === currentIndex;
          $(this)
            .toggleClass("is-active", isActive)
            .attr("aria-pressed", isActive ? "true" : "false");
        });
      };

      const applyEdgeResistance = (distance) => {
        const beyondFirst = currentIndex === 0 && distance > 0;
        const beyondLast = currentIndex === maxIndex && distance < 0;

        if (beyondFirst || beyondLast) {
          return distance * 0.35;
        }

        return distance;
      };

      const resetDragging = () => {
        isDragging = false;
        deltaX = 0;
        dragAxis = "";
        $track.removeClass("is-dragging");
        $(document).off(dragNamespace);
      };

      const render = (
        animate = true,
        shouldSyncMetrics = true,
        shouldScrollThumbs = true
      ) => {
        if (shouldSyncMetrics) {
          syncMetrics();
        }

        const viewportWidth = $viewport.outerWidth() || 0;
        const translateX = -currentIndex * viewportWidth + deltaX;

        $track.toggleClass("is-dragging", !animate);
        $track.css("transform", `translate3d(${translateX}px, 0, 0)`);
        syncSlideState();
        syncPressableState($thumbs);
        syncPressableState($dots);

        if (shouldScrollThumbs) {
          scrollThumbsIntoView(animate);
        }

        $prevButton.prop("disabled", currentIndex === 0);
        $nextButton.prop("disabled", currentIndex === maxIndex);
      };

      const goTo = (index, animate = true) => {
        currentIndex = clampValue(index, 0, maxIndex);
        deltaX = 0;
        render(animate);
      };

      const stopDragging = () => {
        if (!isDragging) {
          return;
        }

        const viewportWidth = $viewport.outerWidth() || 1;
        const travelRatio = deltaX / viewportWidth;
        let nextIndex = currentIndex;

        if (hasDragged && Math.abs(travelRatio) > 0.12) {
          const direction = travelRatio < 0 ? 1 : -1;
          const step = Math.max(1, Math.round(Math.abs(travelRatio)));
          nextIndex = clampValue(currentIndex + direction * step, 0, maxIndex);
        }

        hasDragged = false;
        resetDragging();
        goTo(nextIndex, true);
      };

      const bindIndexedNavigation = ($elements, dataKey) => {
        $elements.on("click", function () {
          const targetIndex = Number($(this).data(dataKey));
          if (Number.isNaN(targetIndex)) {
            return;
          }

          goTo(targetIndex);
        });
      };

      bindIndexedNavigation($thumbs, "gallery-thumb");
      bindIndexedNavigation($dots, "gallery-dot");

      $prevButton.on("click", function () {
        goTo(currentIndex - 1);
      });

      $nextButton.on("click", function () {
        goTo(currentIndex + 1);
      });

      $viewport.on("keydown", function (event) {
        if (event.key === "ArrowLeft") {
          event.preventDefault();
          goTo(currentIndex - 1);
        }

        if (event.key === "ArrowRight") {
          event.preventDefault();
          goTo(currentIndex + 1);
        }
      });

      $viewport.on("dragstart", function (event) {
        event.preventDefault();
      });

      $viewport.on("mousedown touchstart", function (event) {
        if (event.type === "mousedown" && event.which !== 1) {
          return;
        }

        if ($(event.target).closest("iframe").length) {
          return;
        }

        startX = getClientCoordinate(event, "x");
        startY = getClientCoordinate(event, "y");
        deltaX = 0;
        isDragging = true;
        hasDragged = false;
        dragAxis = "";

        $track.addClass("is-dragging");

        $(document)
          .off(dragNamespace)
          .on(
            `mousemove${dragNamespace} touchmove${dragNamespace}`,
            (moveEvent) => {
              if (!isDragging) {
                return;
              }

              const moveX = getClientCoordinate(moveEvent, "x") - startX;
              const moveY = getClientCoordinate(moveEvent, "y") - startY;

              if (!dragAxis) {
                if (Math.abs(moveX) < 6 && Math.abs(moveY) < 6) {
                  return;
                }

                dragAxis = Math.abs(moveX) > Math.abs(moveY) ? "x" : "y";
              }

              if (dragAxis !== "x") {
                hasDragged = false;
                resetDragging();
                return;
              }

              deltaX = applyEdgeResistance(moveX);
              hasDragged = Math.abs(deltaX) > 6;

              if (!hasDragged) {
                return;
              }

              render(false, false, false);

              if ((moveEvent.originalEvent || moveEvent).cancelable !== false) {
                moveEvent.preventDefault();
              }
            }
          )
          .on(
            `mouseup${dragNamespace} touchend${dragNamespace} touchcancel${dragNamespace}`,
            stopDragging
          );
      });

      $viewport.on("click", function (event) {
        if (hasDragged) {
          event.preventDefault();
          hasDragged = false;
        }
      });

      $(window).on(`resize${dragNamespace}`, function () {
        render(false, true);
      });

      render(false, true);

      const requestFrame =
        window.requestAnimationFrame ||
        function (callback) {
          return window.setTimeout(callback, 16);
        };

      requestFrame(() => {
        requestFrame(() => {
          $gallery.addClass("is-ready");
        });
      });
    });
  };

  $(initProductGallery);

  $(window).on("elementor/frontend/init", function () {
    if (!window.elementorFrontend || !window.elementorFrontend.hooks) {
      return;
    }

    window.elementorFrontend.hooks.addAction(
      "frontend/element_ready/product_gallery.default",
      initProductGallery
    );
  });
})(jQuery);
