(function () {
  "use strict";

  document.documentElement.classList.add("uninet-js");

  var desktopMegaMenu = window.matchMedia("(min-width: 1024px)");
  var megaItems = Array.prototype.slice.call(
    document.querySelectorAll(".primary-navigation .uninet-mega-item")
  );

  function setMegaMenuState(item, isOpen) {
    var trigger = item.querySelector(":scope > .uninet-mega-trigger");

    item.classList.toggle("is-open", isOpen);

    if (isOpen) {
      item.classList.remove("is-dismissed");
    }

    if (trigger) {
      trigger.setAttribute("aria-expanded", isOpen ? "true" : "false");
    }
  }

  function closeOtherMegaMenus(activeItem) {
    megaItems.forEach(function (item) {
      if (item !== activeItem) {
        setMegaMenuState(item, false);
        item.classList.remove("is-dismissed");
      }
    });
  }

  megaItems.forEach(function (item) {
    var trigger = item.querySelector(":scope > .uninet-mega-trigger");

    if (!trigger) {
      return;
    }

    item.addEventListener("mouseenter", function () {
      if (!desktopMegaMenu.matches) {
        return;
      }

      closeOtherMegaMenus(item);
      setMegaMenuState(item, true);
    });

    item.addEventListener("mouseleave", function () {
      if (!desktopMegaMenu.matches || item.contains(document.activeElement)) {
        return;
      }

      setMegaMenuState(item, false);
    });

    item.addEventListener("focusin", function () {
      if (!desktopMegaMenu.matches) {
        return;
      }

      closeOtherMegaMenus(item);
      setMegaMenuState(item, true);
    });

    item.addEventListener("focusout", function (event) {
      if (!desktopMegaMenu.matches || item.contains(event.relatedTarget)) {
        return;
      }

      setMegaMenuState(item, false);
      item.classList.remove("is-dismissed");
    });

    item.addEventListener("keydown", function (event) {
      if (!desktopMegaMenu.matches) {
        return;
      }

      if ("ArrowDown" === event.key && event.target === trigger) {
        var firstPanelLink = item.querySelector(
          ":scope > .sub-menu a, :scope > .uninet-mega-feature a"
        );

        if (firstPanelLink) {
          event.preventDefault();
          setMegaMenuState(item, true);
          firstPanelLink.focus();
        }
      }

      if ("Escape" === event.key) {
        event.preventDefault();
        trigger.focus({ preventScroll: true });
        setMegaMenuState(item, false);
        item.classList.add("is-dismissed");
      }
    });
  });

  desktopMegaMenu.addEventListener("change", function (event) {
    if (event.matches) {
      return;
    }

    megaItems.forEach(function (item) {
      setMegaMenuState(item, false);
      item.classList.remove("is-dismissed");
    });
  });
})();
