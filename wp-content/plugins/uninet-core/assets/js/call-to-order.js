(function () {
  "use strict";

  var modal = document.querySelector("[data-uninet-call-modal]");
  var form = document.querySelector("[data-uninet-call-form]");
  var status = document.querySelector("[data-uninet-call-status]");
  var success = document.querySelector("[data-uninet-call-success]");
  var productIdInput = document.querySelector("[data-uninet-call-product-id]");
  var productName = document.querySelector("[data-uninet-call-product-name]");
  var businessNameInput = form ? form.querySelector('[name="business_name"]') : null;
  var emailInput = form ? form.querySelector('[name="email"]') : null;
  var businessPurchaseInput = form ? form.querySelector("[data-uninet-business-purchase]") : null;
  var businessFields = form ? form.querySelector("[data-uninet-business-fields]") : null;
  var submitButton = document.querySelector("[data-uninet-call-submit]");
  var stickyOrder = document.querySelector("[data-uninet-sticky-order]");
  var stickyOrderButton = stickyOrder ? stickyOrder.querySelector("[data-uninet-call-open]") : null;
  var primaryOrderCallout = document.querySelector(".uninet-product-callout");
  var lastActiveElement = null;

  function track(name, params) {
    if (typeof window.uninetTrack === "function") {
      window.uninetTrack(name, params || {});
    }
  }

  function setStatus(message, type) {
    if (!status) {
      return;
    }

    status.textContent = message || "";
    status.dataset.state = type || "";
  }

  function setSubmitting(isSubmitting) {
    if (!submitButton) {
      return;
    }

    submitButton.disabled = isSubmitting;
    submitButton.textContent = isSubmitting ? "Saving order..." : "Finish order to call";
  }

  function updateBusinessEmailRequirement() {
    if (!businessNameInput || !emailInput) {
      return;
    }

    var requiresEmail = businessNameInput.value.trim().length > 0;
    var emailField = emailInput.closest(".uninet-call-form__field");
    var emailBadge = emailField ? emailField.querySelector(".uninet-call-form__optional") : null;

    emailInput.required = requiresEmail;
    emailInput.setAttribute("aria-required", requiresEmail ? "true" : "false");

    if (emailField) {
      emailField.classList.toggle("is-required", requiresEmail);
    }

    if (emailBadge) {
      emailBadge.textContent = requiresEmail ? "Required" : "Conditional";
    }
  }

  function clearBusinessFields() {
    if (!businessFields) {
      return;
    }

    businessFields.querySelectorAll("input, textarea, select").forEach(function (field) {
      if (field.type === "checkbox" || field.type === "radio") {
        field.checked = false;
      } else {
        field.value = "";
      }
    });
  }

  function updateBusinessFields() {
    if (!businessPurchaseInput || !businessFields) {
      return;
    }

    var isBusinessPurchase = businessPurchaseInput.checked;

    businessFields.hidden = !isBusinessPurchase;
    businessFields.disabled = !isBusinessPurchase;

    if (!isBusinessPurchase) {
      clearBusinessFields();
    }

    updateBusinessEmailRequirement();
  }

  function openModal(button) {
    if (!modal || !form || !productIdInput || !productName) {
      return;
    }

    lastActiveElement = document.activeElement;
    form.reset();
    form.hidden = false;

    if (success) {
      success.hidden = true;
      success.textContent = "";
    }

    setStatus("", "");
    setSubmitting(false);

    productIdInput.value = button.getAttribute("data-product-id") || "";
    productName.textContent = button.getAttribute("data-product-name") || "";
    updateBusinessFields();
    updateBusinessEmailRequirement();

    modal.hidden = false;
    document.documentElement.classList.add("uninet-modal-open");

    var firstInput = form.querySelector('input:not([type="hidden"]), textarea, button');
    if (firstInput) {
      firstInput.focus();
    }

    if (window.uninetCore && window.uninetCore.events) {
      track(window.uninetCore.events.callOrderOpen, {
        product_id: productIdInput.value
      });
    }
  }

  function closeModal() {
    if (!modal) {
      return;
    }

    modal.hidden = true;
    document.documentElement.classList.remove("uninet-modal-open");

    if (lastActiveElement && typeof lastActiveElement.focus === "function") {
      lastActiveElement.focus();
    }
  }

  function setStickyOrderVisible(isVisible) {
    if (!stickyOrder) {
      return;
    }

    stickyOrder.classList.toggle("is-visible", isVisible);
    stickyOrder.setAttribute("aria-hidden", isVisible ? "false" : "true");

    if (stickyOrderButton) {
      stickyOrderButton.tabIndex = isVisible ? 0 : -1;
    }
  }

  function updateStickyOrder() {
    if (!stickyOrder || !primaryOrderCallout) {
      return;
    }

    var calloutRect = primaryOrderCallout.getBoundingClientRect();
    setStickyOrderVisible(calloutRect.bottom <= 0);
  }

  function initStickyOrder() {
    if (!stickyOrder || !primaryOrderCallout) {
      return;
    }

    if ("IntersectionObserver" in window) {
      var observer = new IntersectionObserver(function () {
        updateStickyOrder();
      });

      observer.observe(primaryOrderCallout);
    } else {
      window.addEventListener("scroll", updateStickyOrder, { passive: true });
      window.addEventListener("resize", updateStickyOrder);
    }

    updateStickyOrder();
  }

  function focusInvalidField(fieldName) {
    if (!form || !fieldName) {
      return;
    }

    var field = form.querySelector('[name="' + fieldName + '"]');
    if (field && typeof field.focus === "function") {
      field.focus();
    }
  }

  function buildSuccess(data) {
    if (!success || !form) {
      return;
    }

    var payload = data || {};
    var title = document.createElement("h3");
    var message = document.createElement("p");
    var orderNumber = document.createElement("p");

    success.textContent = "";
    title.textContent = "Order request saved";
    message.textContent = payload.message || "Your order request has been saved. Call our sales team to finish confirmation.";
    orderNumber.className = "uninet-call-success__order";
    orderNumber.textContent = payload.orderNumber ? "Reference #" + payload.orderNumber : "";

    success.appendChild(title);
    success.appendChild(message);

    if (orderNumber.textContent) {
      success.appendChild(orderNumber);
    }

    if (payload.salesPhone && payload.telUrl) {
      var callLink = document.createElement("a");
      callLink.className = "button uninet-call-success__phone";
      callLink.href = payload.telUrl;
      callLink.textContent = "Call to finish: " + payload.salesPhone;
      success.appendChild(callLink);
    } else {
      var fallback = document.createElement("p");
      fallback.className = "uninet-call-success__fallback";
      fallback.textContent = "Sales phone is not configured yet. Your pending order was still created.";
      success.appendChild(fallback);
    }

    form.hidden = true;
    success.hidden = false;

    var firstAction = success.querySelector("a, button");
    if (firstAction) {
      firstAction.focus();
    }
  }

  function submitOrder(event) {
    event.preventDefault();

    if (!form || !window.uninetCore) {
      return;
    }

    updateBusinessEmailRequirement();

    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    var formData = new FormData(form);
    formData.append("action", "uninet_call_to_order");
    formData.append("nonce", window.uninetCore.nonce || "");

    setSubmitting(true);
    setStatus("Saving your pending order...", "working");

    fetch(window.uninetCore.ajaxUrl, {
      method: "POST",
      credentials: "same-origin",
      body: formData
    })
      .then(function (response) {
        return response.json().then(function (json) {
          if (!response.ok || !json.success) {
            throw json;
          }

          return json;
        });
      })
      .then(function (json) {
        setStatus("", "");
        buildSuccess(json.data || {});

        if (window.uninetCore.events) {
          track(window.uninetCore.events.callOrderSubmit, {
            product_id: productIdInput ? productIdInput.value : "",
            order_id: json.data ? json.data.orderId : ""
          });
        }
      })
      .catch(function (error) {
        var data = error && error.data ? error.data : {};
        var message = data.message || "We could not save the order. Please check the form and try again.";

        setStatus(message, "error");
        focusInvalidField(data.field || "");
      })
      .finally(function () {
        setSubmitting(false);
      });
  }

  if (businessNameInput) {
    businessNameInput.addEventListener("input", updateBusinessEmailRequirement);
  }

  if (businessPurchaseInput) {
    businessPurchaseInput.addEventListener("change", updateBusinessFields);
  }

  if (form) {
    form.addEventListener("submit", submitOrder);
  }

  initStickyOrder();

  document.addEventListener("click", function (event) {
    var openButton = event.target.closest("[data-uninet-call-open]");
    var closeButton = event.target.closest("[data-uninet-call-close]");
    var phoneLink = event.target.closest('[href^="tel:"]');
    var whatsappLink = event.target.closest('a[href*="wa.me"], a[href*="whatsapp"]');

    if (openButton) {
      event.preventDefault();
      openModal(openButton);
    }

    if (closeButton) {
      event.preventDefault();
      closeModal();
    }

    if (event.target === modal) {
      closeModal();
    }

    if (phoneLink && window.uninetCore && window.uninetCore.events) {
      track(window.uninetCore.events.phoneClick, {
        href: phoneLink.getAttribute("href")
      });
    }

    if (whatsappLink && window.uninetCore && window.uninetCore.events) {
      track(window.uninetCore.events.whatsappClick, {
        href: whatsappLink.getAttribute("href")
      });
    }
  });

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape" && modal && !modal.hidden) {
      closeModal();
    }
  });
})();
