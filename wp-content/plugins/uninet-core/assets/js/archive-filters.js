(function () {
  "use strict";

  function numberValue(input, fallback) {
    var value = Number(input.value);

    return Number.isFinite(value) ? value : fallback;
  }

  function clamp(value, minimum, maximum) {
    return Math.min(maximum, Math.max(minimum, value));
  }

  function formatPrice(value) {
    return "KSh " + Math.round(value).toLocaleString("en-KE");
  }

  function initPriceFilter(filter) {
    var minimumRange = filter.querySelector("[data-uninet-price-min-range]");
    var maximumRange = filter.querySelector("[data-uninet-price-max-range]");
    var minimumInput = filter.querySelector("[data-uninet-price-min-input]");
    var maximumInput = filter.querySelector("[data-uninet-price-max-input]");
    var minimumOutput = filter.querySelector("[data-uninet-price-min-output]");
    var maximumOutput = filter.querySelector("[data-uninet-price-max-output]");
    var track = filter.querySelector("[data-uninet-price-track]");

    if (
      !minimumRange ||
      !maximumRange ||
      !minimumInput ||
      !maximumInput ||
      !minimumOutput ||
      !maximumOutput ||
      !track
    ) {
      return;
    }

    var lowerBound = Number(minimumRange.min);
    var upperBound = Number(maximumRange.max);
    var span = Math.max(1, upperBound - lowerBound);

    function updateVisuals() {
      var minimum = numberValue(minimumRange, lowerBound);
      var maximum = numberValue(maximumRange, upperBound);
      var minimumPercent = ((minimum - lowerBound) / span) * 100;
      var maximumPercent = ((maximum - lowerBound) / span) * 100;

      track.style.left = minimumPercent + "%";
      track.style.right = 100 - maximumPercent + "%";
      minimumOutput.textContent = formatPrice(minimum);
      maximumOutput.textContent = formatPrice(maximum);
      minimumRange.setAttribute("aria-valuetext", formatPrice(minimum));
      maximumRange.setAttribute("aria-valuetext", formatPrice(maximum));
    }

    minimumRange.addEventListener("input", function () {
      var minimum = numberValue(minimumRange, lowerBound);
      var maximum = numberValue(maximumRange, upperBound);

      if (minimum > maximum) {
        minimum = maximum;
        minimumRange.value = String(minimum);
      }

      minimumInput.value = String(minimum);
      updateVisuals();
    });

    maximumRange.addEventListener("input", function () {
      var minimum = numberValue(minimumRange, lowerBound);
      var maximum = numberValue(maximumRange, upperBound);

      if (maximum < minimum) {
        maximum = minimum;
        maximumRange.value = String(maximum);
      }

      maximumInput.value = String(maximum);
      updateVisuals();
    });

    function syncNumberInputs(changedInput) {
      var minimum = minimumInput.value
        ? clamp(numberValue(minimumInput, lowerBound), lowerBound, upperBound)
        : lowerBound;
      var maximum = maximumInput.value
        ? clamp(numberValue(maximumInput, upperBound), lowerBound, upperBound)
        : upperBound;

      if (minimum > maximum) {
        if (changedInput === minimumInput) {
          maximum = minimum;
          maximumInput.value = String(maximum);
        } else {
          minimum = maximum;
          minimumInput.value = String(minimum);
        }
      }

      minimumRange.value = String(minimum);
      maximumRange.value = String(maximum);
      updateVisuals();
    }

    minimumInput.addEventListener("input", function () {
      syncNumberInputs(minimumInput);
    });

    maximumInput.addEventListener("input", function () {
      syncNumberInputs(maximumInput);
    });

    updateVisuals();
  }

  Array.prototype.forEach.call(
    document.querySelectorAll("[data-uninet-price-filter]"),
    initPriceFilter
  );

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
