(function ($) {
  "use strict";

  const DEFAULTS = {
    id: "jc-modal",
    title: "Modal Title",
    description: "",
    bodyHtml: "",
    confirmText: "Confirm",
    cancelText: "Cancel",
    closeOnBackdrop: true,
    closeOnEsc: true,
    onConfirm: null,
    onCancel: null,
    onOpen: null,
    onClose: null,
  };

  class JellyCatalogModal {
    constructor(options) {
      this.options = $.extend({}, DEFAULTS, options);
      this.$modal = null;
      this.isOpen = false;
      this.bound = false;
      this.eventNamespace = `.jcModal.${this.options.id}`;
      this.ensureModal();
      this.bindEvents();
    }

    ensureModal() {
      const existing = $(`#${this.options.id}`);
      if (existing.length) {
        this.$modal = existing;
        this.refreshContent();
        return;
      }

      const modalHtml = `
        <div id="${this.options.id}" class="jc-modal">
          <div class="jc-modal__content">
            <h3 class="jc-modal__title"></h3>
            <p class="jc-modal__description"></p>
            <div class="jc-modal__body"></div>
            <div class="jc-modal__buttons">
              <button type="button" class="button button-secondary jc-modal__cancel"></button>
              <button type="button" class="button button-primary jc-modal__confirm"></button>
            </div>
          </div>
        </div>
      `;

      $("body").append(modalHtml);
      this.$modal = $(`#${this.options.id}`);
      this.refreshContent();
    }

    refreshContent() {
      if (!this.$modal) return;

      this.$modal.find(".jc-modal__title").text(this.options.title);
      const $description = this.$modal.find(".jc-modal__description");
      if (this.options.description) {
        $description.text(this.options.description).show();
      } else {
        $description.hide();
      }

      this.$modal.find(".jc-modal__body").html(this.options.bodyHtml);
      this.$modal.find(".jc-modal__confirm").text(this.options.confirmText);
      this.$modal.find(".jc-modal__cancel").text(this.options.cancelText);
    }

    bindEvents() {
      if (this.bound) return;
      this.bound = true;

      $(document).on(
        `click${this.eventNamespace}`,
        `#${this.options.id} .jc-modal__cancel`,
        (e) => {
          e.preventDefault();
          this.handleCancel();
        },
      );

      $(document).on(
        `click${this.eventNamespace}`,
        `#${this.options.id} .jc-modal__confirm`,
        (e) => {
          e.preventDefault();
          this.handleConfirm();
        },
      );

      $(document).on(
        `click${this.eventNamespace}`,
        `#${this.options.id}`,
        (e) => {
          if (!this.options.closeOnBackdrop) return;
          if (e.target.id === this.options.id) {
            this.close();
          }
        },
      );

      $(document).on(
        `click${this.eventNamespace}`,
        `#${this.options.id} .jc-modal__content`,
        (e) => {
          e.stopPropagation();
        },
      );

      $(document).on(`keydown${this.eventNamespace}`, (e) => {
        if (!this.isOpen || !this.options.closeOnEsc) return;
        if (e.key === "Escape") {
          this.close();
        }
      });
    }

    handleConfirm() {
      if (typeof this.options.onConfirm === "function") {
        const result = this.options.onConfirm(this);
        if (result === false) {
          return;
        }
      }
      this.close();
    }

    handleCancel() {
      if (typeof this.options.onCancel === "function") {
        this.options.onCancel(this);
      }
      this.close();
    }

    open() {
      if (!this.$modal) {
        this.ensureModal();
      }
      this.$modal.show();
      this.isOpen = true;
      if (typeof this.options.onOpen === "function") {
        this.options.onOpen(this);
      }
    }

    close() {
      if (!this.$modal) return;
      this.$modal.hide();
      this.isOpen = false;
      if (typeof this.options.onClose === "function") {
        this.options.onClose(this);
      }
    }

    find(selector) {
      return this.$modal ? this.$modal.find(selector) : $();
    }

    setBodyHtml(html) {
      this.options.bodyHtml = html;
      this.refreshContent();
    }
  }

  window.JellyCatalogModal = JellyCatalogModal;
})(jQuery);
