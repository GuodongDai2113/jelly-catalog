(function ($) {
  "use strict";

  if (typeof $ === "undefined") return;

  class JellyCatalogElementorEditor {
    constructor() {
      this.openMagicButton = this.openMagicButton.bind(this);
      this.injectButtonIntoTemplate();
    }

    injectButtonIntoTemplate() {
      var $template = $("#tmpl-elementor-add-section");
      if ($template.length <= 0) return false;

      var templateText = $template.text();

      templateText = templateText.replace(
        '<div class="elementor-add-section-drag-title',
        "" +
          '<div class="elementor-add-section-area-button elementor-add-jelly-catalog-button"' +
          ' title="Jelly Catalog" style="background-color: #0572d1;color:#fff;fill:#fff;">' +
          '<svg width="16" height="16" viewBox="0 0 100 100" fill="inherit" xmlns="http://www.w3.org/2000/svg">' +
          '<path d="M60 20L80 20L50 80L30 80L60 20Z" />' +
          '<path d="M40 20L55 20L25 80L10 80L40 20Z" opacity="0.8" />' +
          '<rect x="75" y="20" width="5" height="60" />' +
          "</svg>" +
          "</div>" +
          '<div class="elementor-add-section-drag-title'
      );

      $template.text(templateText);
      return true;
    }

    getRandomId() {
      return Math.random().toString(16).slice(2, 10);
    }

    openMagicButton(event) {
      let insertIndex = 0;
      if (elementorCommon) {
        insertIndex =
          jQuery(event.target).parents(".elementor-section-wrap").length > 0
            ? jQuery(this).parents(".elementor-add-section").index()
            : -1;
        if (insertIndex >= 0) {
          jQuery(event.target)
            .parents(".elementor-add-section-inline")
            .remove();
        }
      }

      const data = {
        content: [
          {
            id: this.getRandomId(),
            elType: "container",
            isInner: false,
            settings: {
              padding: {
                unit: "px",
                top: "20",
                right: "20",
                bottom: "20",
                left: "20",
                isLinked: true,
              },
            },
            elements: [],
          },
        ],
      };

      if (typeof elementor !== "undefined") {
        if (typeof $e !== "undefined") {
          const historyId = $e.internal("document/history/start-log", {
            type: "add",
            title: "Add Envato Elements Content",
          });
          for (let i = 0; i < data.content.length; i++) {
            $e.run("document/elements/create", {
              container: elementor.getPreviewContainer(),
              model: data.content[i],
              options: insertIndex >= 0 ? { at: insertIndex++ } : {},
            });
          }
          $e.internal("document/history/end-log", {
            id: historyId,
          });
        } else {
          const model = new Backbone.Model({
            getTitle() {
              return "Test";
            },
          });
          elementor.channels.data.trigger("template:before:insert", model);
          for (let i = 0; i < data.content.length; i++) {
            elementor
              .getPreviewView()
              .addChildElement(
                data.content[i],
                insertIndex >= 0 ? { at: insertIndex++ } : null
              );
          }
          elementor.channels.data.trigger("template:after:insert", {});
        }
      }
    }
  }

  const editor = new JellyCatalogElementorEditor();
  if (editor && typeof elementor !== "undefined") {
    elementor.on("preview:loaded", function () {
      var $previewBody = $(elementor.$previewContents[0].body);

      $previewBody.on(
        "click",
        ".elementor-add-jelly-catalog-button",
        editor.openMagicButton
      );
    });
  }
})(jQuery);
