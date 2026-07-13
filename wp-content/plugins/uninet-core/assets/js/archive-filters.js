(function () {
  "use strict";

  var panel = document.querySelector(".uninet-archive-filters");
  var backdrop = document.querySelector(".uninet-filter-backdrop");
  var openButtons = Array.prototype.slice.call(
    document.querySelectorAll("[data-uninet-filter-open]")
  );
  var closeButtons = Array.prototype.slice.call(
    document.querySelectorAll("[data-uninet-filter-close]")
  );
  var mobileFilters = window.matchMedia("(max-width: 767px)");
  var lastFocusedElement = null;

  if (!panel || !backdrop || !openButtons.length) {
    return;
  }

  document.documentElement.classList.add("uninet-filters-js");

  function focusableElements() {
    return Array.prototype.slice.call(
      panel.querySelectorAll(
        'button:not([disabled]), a[href], input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
      )
    );
  }

  function setOpen(isOpen) {
    panel.classList.toggle("is-open", isOpen);
    backdrop.classList.toggle("is-open", isOpen);
    document.body.classList.toggle("uninet-filter-drawer-open", isOpen);

    openButtons.forEach(function (button) {
      button.setAttribute("aria-expanded", isOpen ? "true" : "false");
    });

    if (isOpen) {
      lastFocusedElement = document.activeElement;

      var firstControl = panel.querySelector("[data-uninet-filter-close]");

      if (firstControl) {
        firstControl.focus({ preventScroll: true });
      }

      return;
    }

    if (lastFocusedElement && document.contains(lastFocusedElement)) {
      lastFocusedElement.focus({ preventScroll: true });
    }

    lastFocusedElement = null;
  }

  openButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      setOpen(true);
    });
  });

  closeButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      setOpen(false);
    });
  });

  document.addEventListener("keydown", function (event) {
    if (!panel.classList.contains("is-open")) {
      return;
    }

    if ("Escape" === event.key) {
      event.preventDefault();
      setOpen(false);
      return;
    }

    if ("Tab" !== event.key || !mobileFilters.matches) {
      return;
    }

    var focusable = focusableElements();

    if (!focusable.length) {
      event.preventDefault();
      return;
    }

    var first = focusable[0];
    var last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  });

  mobileFilters.addEventListener("change", function (event) {
    if (!event.matches) {
      setOpen(false);
    }
  });
})();
