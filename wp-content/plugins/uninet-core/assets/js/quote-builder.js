(function () {
  "use strict";

  var builder = document.querySelector("[data-uninet-quote-builder]");

  if (!builder || !window.uninetQuote) {
    return;
  }

  var config = window.uninetQuote;
  var entrySource = builder.getAttribute("data-uninet-quote-source") || "direct";
  var initialProduct = parseInitialProduct(builder.getAttribute("data-uninet-quote-initial-product"));
  var form = builder.querySelector("[data-uninet-quote-form]");
  var searchInput = builder.querySelector("[data-uninet-quote-search]");
  var searchWrap = builder.querySelector("[data-uninet-quote-search-wrap]");
  var searchClear = builder.querySelector("[data-uninet-quote-search-clear]");
  var resultsPanel = builder.querySelector("[data-uninet-quote-results]");
  var resultsList = builder.querySelector("[data-uninet-quote-result-list]");
  var searchStatus = builder.querySelector("[data-uninet-quote-search-status]");
  var categoryButtons = builder.querySelectorAll("[data-uninet-quote-category]");
  var emptyState = builder.querySelector("[data-uninet-quote-empty]");
  var linesTable = builder.querySelector("[data-uninet-quote-lines]");
  var linesList = builder.querySelector("[data-uninet-quote-line-list]");
  var priceNote = builder.querySelector("[data-uninet-quote-price-note]");
  var itemsInput = builder.querySelector("[data-uninet-quote-items]");
  var lineCount = builder.querySelector("[data-uninet-quote-line-count]");
  var summaryCount = builder.querySelector("[data-uninet-quote-summary-count]");
  var subtotalOutput = builder.querySelector("[data-uninet-quote-subtotal]");
  var unpricedOutput = builder.querySelector("[data-uninet-quote-unpriced]");
  var mobileBar = builder.querySelector("[data-uninet-quote-mobile-bar]");
  var mobileCount = builder.querySelector("[data-uninet-quote-mobile-count]");
  var mobileTotal = builder.querySelector("[data-uninet-quote-mobile-total]");
  var reviewButtons = builder.querySelectorAll("[data-uninet-quote-review]");
  var dialog = builder.querySelector("[data-uninet-quote-dialog]");
  var reviewContent = builder.querySelector("[data-uninet-quote-review-content]");
  var submitButton = builder.querySelector("[data-uninet-quote-submit]");
  var submitStatus = builder.querySelector("[data-uninet-quote-submit-status]");
  var errors = builder.querySelector("[data-uninet-quote-errors]");
  var success = builder.querySelector("[data-uninet-quote-success]");
  var successMessage = builder.querySelector("[data-uninet-quote-success-message]");
  var referenceOutput = builder.querySelector("[data-uninet-quote-reference]");
  var whatsappLink = builder.querySelector("[data-uninet-quote-whatsapp]");
  var storageKey = "uninetQuoteItemsV1";
  var activeCategory = "";
  var items = restoreItems();
  var currentResults = [];
  var searchTimer = null;
  var searchRequest = 0;
  var submitting = false;

  function track(name, params) {
    if (name && typeof window.uninetTrack === "function") {
      window.uninetTrack(name, params || {});
    }
  }

  function create(tag, className, text) {
    var node = document.createElement(tag);

    if (className) {
      node.className = className;
    }

    if (typeof text === "string") {
      node.textContent = text;
    }

    return node;
  }

  function parseInitialProduct(value) {
    if (!value) {
      return null;
    }

    try {
      var product = JSON.parse(value);

      if (!product || Number(product.id) <= 0 || !product.name) {
        return null;
      }

      return product;
    } catch (error) {
      return null;
    }
  }

  function formatPrice(amount) {
    var value = Number(amount || 0);

    try {
      return new Intl.NumberFormat(config.locale || "en-KE", {
        style: "currency",
        currency: config.currency || "KES",
        maximumFractionDigits: 0
      }).format(value);
    } catch (error) {
      return "KSh " + Math.round(value).toLocaleString();
    }
  }

  function productCountLabel(count) {
    return count === 1 ? "1 product" : count + " products";
  }

  function restoreItems() {
    try {
      var saved = JSON.parse(window.sessionStorage.getItem(storageKey) || "[]");

      if (!Array.isArray(saved)) {
        return [];
      }

      return saved
        .filter(function (item) {
          return item && Number(item.id) > 0 && item.name;
        })
        .slice(0, Number(config.maxItems || 40))
        .map(function (item) {
          return {
            id: Number(item.id),
            name: String(item.name),
            sku: String(item.sku || ""),
            image: String(item.image || ""),
            price: item.price === null || item.price === "" ? null : Number(item.price),
            pricePrefix: String(item.pricePrefix || ""),
            availability: String(item.availability || "Staff confirms availability"),
            permalink: String(item.permalink || ""),
            quantity: Math.max(1, Math.min(999, Number(item.quantity || 1))),
            note: String(item.note || "").slice(0, 500)
          };
        });
    } catch (error) {
      return [];
    }
  }

  function saveItems() {
    try {
      window.sessionStorage.setItem(storageKey, JSON.stringify(items));
    } catch (error) {
      // The builder still works when browser storage is unavailable.
    }
  }

  function requestItems() {
    return items.map(function (item) {
      return {
        product_id: item.id,
        quantity: item.quantity,
        note: item.note
      };
    });
  }

  function totals() {
    return items.reduce(function (result, item) {
      if (item.price === null || !Number.isFinite(Number(item.price))) {
        result.unpriced += 1;
      } else {
        result.subtotal += Number(item.price) * Number(item.quantity);
      }

      result.units += Number(item.quantity);
      return result;
    }, { subtotal: 0, unpriced: 0, units: 0 });
  }

  function findItem(productId) {
    return items.find(function (item) {
      return item.id === Number(productId);
    });
  }

  function showError(message, fieldName) {
    if (!errors) {
      return;
    }

    errors.textContent = message || "Please check the quote request and try again.";
    errors.hidden = false;
    errors.focus();

    if (!fieldName) {
      return;
    }

    if (fieldName === "items") {
      searchInput.focus();
      return;
    }

    var field = form.querySelector('[name="' + fieldName + '"]');

    if (field && typeof field.focus === "function") {
      field.focus();
    }
  }

  function clearError() {
    if (errors) {
      errors.hidden = true;
      errors.textContent = "";
    }
  }

  function updateSummary() {
    var count = items.length;
    var quoteTotals = totals();
    var label = productCountLabel(count);

    itemsInput.value = JSON.stringify(requestItems());
    lineCount.textContent = label;
    summaryCount.textContent = label;
    subtotalOutput.textContent = formatPrice(quoteTotals.subtotal);
    mobileCount.textContent = label;
    mobileTotal.textContent = formatPrice(quoteTotals.subtotal) + " pre-tax";
    mobileBar.hidden = count === 0;

    reviewButtons.forEach(function (button) {
      button.disabled = count === 0;
    });

    if (quoteTotals.unpriced > 0) {
      unpricedOutput.textContent = quoteTotals.unpriced === 1
        ? "1 product needs staff pricing and is excluded from this subtotal."
        : quoteTotals.unpriced + " products need staff pricing and are excluded from this subtotal.";
      unpricedOutput.hidden = false;
    } else {
      unpricedOutput.hidden = true;
      unpricedOutput.textContent = "";
    }
  }

  function quantityControl(item) {
    var control = create("div", "uninet-quote-quantity");
    var decrease = create("button", "", "\u2212");
    var input = create("input");
    var increase = create("button", "", "+");

    decrease.type = "button";
    decrease.setAttribute("aria-label", "Decrease quantity for " + item.name);
    decrease.disabled = item.quantity <= 1;
    input.type = "number";
    input.min = "1";
    input.max = "999";
    input.step = "1";
    input.value = String(item.quantity);
    input.setAttribute("aria-label", "Quantity for " + item.name);
    increase.type = "button";
    increase.setAttribute("aria-label", "Increase quantity for " + item.name);

    decrease.addEventListener("click", function () {
      setQuantity(item.id, item.quantity - 1);
    });

    increase.addEventListener("click", function () {
      setQuantity(item.id, item.quantity + 1);
    });

    input.addEventListener("change", function () {
      setQuantity(item.id, Number(input.value));
    });

    control.appendChild(decrease);
    control.appendChild(input);
    control.appendChild(increase);
    return control;
  }

  function renderLine(item) {
    var wrapper = create("div", "uninet-quote-line");
    var row = create("div", "uninet-quote-line__row");
    var product = create("div", "uninet-quote-line__product");
    var image = create("img");
    var productText = create("div");
    var name = item.permalink ? create("a", "uninet-quote-line__name", item.name) : create("strong", "uninet-quote-line__name", item.name);
    var sku = create("span", "uninet-quote-line__sku", item.sku ? "SKU: " + item.sku : "SKU not listed");
    var availability = create("span", "uninet-quote-line__availability", item.availability);
    var quantity = create("div", "uninet-quote-line__quantity");
    var unit = create("div", "uninet-quote-line__price");
    var lineTotal = create("div", "uninet-quote-line__total");
    var actions = create("div", "uninet-quote-line__actions");
    var noteButton = create("button", "uninet-quote-line__note-button", item.note ? "Edit note" : "Add note");
    var removeButton = create("button", "uninet-quote-line__remove");
    var removeSymbol = create("span", "", "\u00d7");
    var removeText = create("span", "screen-reader-text", "Remove " + item.name);
    var noteWrap = create("div", "uninet-quote-line__note");
    var noteLabel = create("label", "", "Requirement for " + item.name);
    var note = create("textarea");

    wrapper.dataset.productId = String(item.id);
    image.src = item.image;
    image.alt = "";
    image.width = 72;
    image.height = 72;
    image.loading = "lazy";

    if (item.permalink) {
      name.href = item.permalink;
      name.target = "_blank";
      name.rel = "noopener noreferrer";
    }

    productText.appendChild(name);
    productText.appendChild(sku);
    productText.appendChild(availability);
    product.appendChild(image);
    product.appendChild(productText);

    quantity.appendChild(quantityControl(item));
    quantity.setAttribute("data-label", "Quantity");

    unit.setAttribute("data-label", "Pre-tax price");
    unit.textContent = item.price === null
      ? "Staff confirmation"
      : (item.pricePrefix ? item.pricePrefix + " " : "") + formatPrice(item.price);

    lineTotal.setAttribute("data-label", "Line total");
    lineTotal.textContent = item.price === null ? "Not included" : formatPrice(item.price * item.quantity);

    noteButton.type = "button";
    noteButton.setAttribute("aria-expanded", item.note ? "true" : "false");
    noteButton.addEventListener("click", function () {
      var willOpen = noteWrap.hidden;
      noteWrap.hidden = !willOpen;
      noteButton.setAttribute("aria-expanded", willOpen ? "true" : "false");
      if (willOpen) {
        note.focus();
      }
    });

    removeButton.type = "button";
    removeButton.setAttribute("aria-label", "Remove " + item.name);
    removeButton.appendChild(removeSymbol);
    removeButton.appendChild(removeText);
    removeButton.addEventListener("click", function () {
      removeItem(item.id);
    });

    actions.appendChild(noteButton);
    actions.appendChild(removeButton);

    note.id = "uninet-quote-note-" + item.id;
    note.maxLength = 500;
    note.rows = 2;
    note.value = item.note;
    note.placeholder = "Optional: required specification, compatibility, colour, or configuration.";
    noteLabel.htmlFor = note.id;
    note.addEventListener("input", function () {
      item.note = note.value.slice(0, 500);
      noteButton.textContent = item.note ? "Edit note" : "Add note";
      itemsInput.value = JSON.stringify(requestItems());
      saveItems();
    });

    noteWrap.hidden = !item.note;
    noteWrap.appendChild(noteLabel);
    noteWrap.appendChild(note);

    row.appendChild(product);
    row.appendChild(quantity);
    row.appendChild(unit);
    row.appendChild(lineTotal);
    row.appendChild(actions);
    wrapper.appendChild(row);
    wrapper.appendChild(noteWrap);
    return wrapper;
  }

  function renderLines() {
    linesList.textContent = "";

    items.forEach(function (item) {
      linesList.appendChild(renderLine(item));
    });

    emptyState.hidden = items.length > 0;
    linesTable.hidden = items.length === 0;
    priceNote.hidden = items.length === 0;
    updateSummary();
    saveItems();
    renderResults(currentResults);
  }

  function setQuantity(productId, quantity) {
    var item = findItem(productId);

    if (!item) {
      return;
    }

    item.quantity = Math.max(1, Math.min(999, Number(quantity) || 1));
    renderLines();
  }

  function addProduct(product, options) {
    options = options || {};
    var existing = findItem(product.id);

    if (existing) {
      if (options.prefill) {
        return false;
      }

      setQuantity(existing.id, existing.quantity + 1);
      return true;
    }

    if (items.length >= Number(config.maxItems || 40)) {
      showError("This quote request has reached the maximum number of product lines.", "items");
      return false;
    }

    items.push({
      id: Number(product.id),
      name: String(product.name),
      sku: String(product.sku || ""),
      image: String(product.image || ""),
      price: product.price === null ? null : Number(product.price),
      pricePrefix: String(product.pricePrefix || ""),
      availability: String(product.availability || "Staff confirms availability"),
      permalink: String(product.permalink || ""),
      quantity: 1,
      note: ""
    });

    clearError();
    renderLines();
    track(config.events && config.events[options.prefill ? "quotePrefill" : "quoteAddProduct"], {
      product_id: product.id,
      source: entrySource
    });

    var selected = linesList.querySelector('[data-product-id="' + product.id + '"]');
    if (selected && options.scroll !== false) {
      selected.scrollIntoView({ behavior: "smooth", block: "nearest" });
    }

    return true;
  }

  function removeItem(productId) {
    var removed = findItem(productId);
    items = items.filter(function (item) {
      return item.id !== Number(productId);
    });
    renderLines();

    if (removed) {
      searchInput.focus();
    }
  }

  function renderResult(product) {
    var result = create("article", "uninet-quote-result");
    var image = create("img");
    var details = create("div", "uninet-quote-result__details");
    var name = create("strong", "", product.name);
    var sku = create("span", "", product.sku ? "SKU: " + product.sku : "SKU not listed");
    var availability = create("span", "uninet-quote-result__availability", product.availability);
    var price = create("div", "uninet-quote-result__price");
    var button = create("button", "button", findItem(product.id) ? "Added" : "Add product");

    image.src = product.image;
    image.alt = "";
    image.width = 64;
    image.height = 64;
    image.loading = "lazy";
    details.appendChild(name);
    details.appendChild(sku);
    details.appendChild(availability);
    price.textContent = product.price === null
      ? "Price confirmed by staff"
      : (product.pricePrefix ? product.pricePrefix + " " : "") + formatPrice(product.price) + " pre-tax";
    button.type = "button";
    button.disabled = Boolean(findItem(product.id));
    button.setAttribute("aria-label", (button.disabled ? "Already added " : "Add ") + product.name);
    button.addEventListener("click", function () {
      addProduct(product);
    });

    result.appendChild(image);
    result.appendChild(details);
    result.appendChild(price);
    result.appendChild(button);
    return result;
  }

  function renderResults(products) {
    currentResults = Array.isArray(products) ? products : [];
    resultsList.textContent = "";

    currentResults.forEach(function (product) {
      resultsList.appendChild(renderResult(product));
    });

    if (currentResults.length === 0 && !searchStatus.textContent) {
      searchStatus.textContent = "No matching catalogue products found. Try another model, SKU, or category.";
    }
  }

  function setSearchState(message, isOpen) {
    searchStatus.textContent = message || "";
    resultsPanel.hidden = !isOpen;
  }

  function searchProducts() {
    var query = searchInput.value.trim();
    var requestId;
    var params;

    searchClear.hidden = query.length === 0;

    if (query.length < 2 && !activeCategory) {
      currentResults = [];
      setSearchState("", false);
      return;
    }

    requestId = ++searchRequest;
    params = new URLSearchParams({
      action: config.searchAction,
      nonce: config.nonce,
      query: query,
      category: activeCategory
    });

    setSearchState("Searching the current catalogue...", true);

    fetch(config.ajaxUrl + "?" + params.toString(), {
      method: "GET",
      credentials: "same-origin",
      headers: { "Accept": "application/json" }
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
        var products;

        if (requestId !== searchRequest) {
          return;
        }

        products = json.data && Array.isArray(json.data.products) ? json.data.products : [];
        searchStatus.textContent = products.length
          ? products.length + (products.length === 1 ? " catalogue product found." : " catalogue products found.")
          : "";
        renderResults(products);
        resultsPanel.hidden = false;
        track(config.events && config.events.quoteSearch, {
          search_term: query,
          product_category: activeCategory,
          result_count: products.length,
          source: entrySource
        });
      })
      .catch(function (error) {
        if (requestId !== searchRequest) {
          return;
        }

        currentResults = [];
        renderResults([]);
        searchStatus.textContent = error && error.data && error.data.message
          ? error.data.message
          : "We could not search the catalogue. Check your connection and try again.";
        resultsPanel.hidden = false;
        track(config.events && config.events.quoteSearchError, {
          product_category: activeCategory,
          source: entrySource
        });
      });
  }

  function scheduleSearch() {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(searchProducts, 280);
  }

  function renderReview() {
    var quoteTotals = totals();
    var organisation = form.querySelector('[name="organisation_name"]').value;
    var contact = form.querySelector('[name="full_name"]').value;
    var county = form.querySelector('[name="county"]').value;
    var town = form.querySelector('[name="town"]').value;
    var heading = create("h3", "", "Products (" + items.length + ")");
    var list = create("div", "uninet-quote-review__lines");
    var total = create("div", "uninet-quote-review__total");
    var totalLabel = create("span", "", "Indicative pre-tax subtotal");
    var totalValue = create("strong", "", formatPrice(quoteTotals.subtotal));
    var buyer = create("dl", "uninet-quote-review__buyer");

    reviewContent.textContent = "";
    reviewContent.appendChild(heading);

    items.forEach(function (item) {
      var row = create("div", "uninet-quote-review__line");
      var product = create("span", "", item.name + " x " + item.quantity);
      var amount = create("strong", "", item.price === null ? "Staff pricing" : formatPrice(item.price * item.quantity));
      row.appendChild(product);
      row.appendChild(amount);
      list.appendChild(row);
    });

    reviewContent.appendChild(list);
    total.appendChild(totalLabel);
    total.appendChild(totalValue);
    reviewContent.appendChild(total);

    if (quoteTotals.unpriced) {
      reviewContent.appendChild(create("p", "uninet-quote-review__unpriced", quoteTotals.unpriced + (quoteTotals.unpriced === 1 ? " product is" : " products are") + " excluded until staff confirms pricing."));
    }

    [
      ["Organisation", organisation],
      ["Contact", contact],
      ["Location", town + ", " + county]
    ].forEach(function (entry) {
      var term = create("dt", "", entry[0]);
      var description = create("dd", "", entry[1]);
      buyer.appendChild(term);
      buyer.appendChild(description);
    });

    reviewContent.appendChild(buyer);
  }

  function openReview() {
    clearError();

    if (items.length === 0) {
      showError("Add at least one catalogue product before reviewing the quote request.", "items");
      return;
    }

    if (!form.checkValidity()) {
      form.reportValidity();
      var invalid = form.querySelector(":invalid");
      if (invalid) {
        invalid.focus();
      }
      return;
    }

    renderReview();
    submitStatus.textContent = "";

    if (typeof dialog.showModal === "function") {
      dialog.showModal();
    } else {
      dialog.setAttribute("open", "open");
    }

    document.documentElement.classList.add("uninet-quote-dialog-open");
    var close = dialog.querySelector("[data-uninet-quote-dialog-close]");
    if (close) {
      close.focus();
    }
  }

  function closeReview() {
    if (typeof dialog.close === "function" && dialog.open) {
      dialog.close();
    } else {
      dialog.removeAttribute("open");
    }

    document.documentElement.classList.remove("uninet-quote-dialog-open");
  }

  function setSubmitting(isSubmitting) {
    submitting = isSubmitting;
    submitButton.disabled = isSubmitting;
    submitButton.textContent = isSubmitting ? "Saving quote request..." : "Send quote request";
  }

  function submitQuote() {
    var data;
    var submissionItemCount;
    var submissionTotals;

    if (submitting) {
      return;
    }

    itemsInput.value = JSON.stringify(requestItems());
    data = new FormData(form);
    submissionItemCount = items.length;
    submissionTotals = totals();
    setSubmitting(true);
    submitStatus.textContent = "Saving the request securely...";

    fetch(config.ajaxUrl, {
      method: "POST",
      credentials: "same-origin",
      body: data,
      headers: { "Accept": "application/json" }
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
        var payload = json.data || {};

        closeReview();
        form.hidden = true;
        success.hidden = false;
        successMessage.textContent = payload.message || "We saved your quote request for staff review.";
        referenceOutput.textContent = payload.reference ? "Quote reference: " + payload.reference : "";
        whatsappLink.href = payload.whatsappUrl || "https://wa.me/254770313200";
        success.focus();
        track(config.events && config.events.quoteSubmit, {
          quote_id: payload.quoteId || "",
          quote_reference: payload.reference || "",
          item_count: submissionItemCount,
          quantity: submissionTotals.units,
          value: submissionTotals.subtotal,
          currency: config.currency || "KES",
          unpriced_count: submissionTotals.unpriced,
          source: entrySource
        });
        items = [];
        saveItems();
      })
      .catch(function (error) {
        var payload = error && error.data ? error.data : {};
        var message = payload.message || "We could not save the quote request. Check your connection and try again.";

        submitStatus.textContent = message;

        if (payload.field) {
          closeReview();
          showError(message, payload.field);
        }

        track(config.events && config.events.quoteSubmitError, {
          error_field: payload.field || "request",
          source: entrySource
        });
      })
      .finally(function () {
        setSubmitting(false);
      });
  }

  searchInput.addEventListener("input", scheduleSearch);
  searchInput.addEventListener("focus", function () {
    if (currentResults.length || searchStatus.textContent) {
      resultsPanel.hidden = false;
    }
  });

  searchClear.addEventListener("click", function () {
    searchInput.value = "";
    searchClear.hidden = true;
    searchInput.focus();
    searchProducts();
  });

  categoryButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      activeCategory = button.getAttribute("data-uninet-quote-category") || "";
      categoryButtons.forEach(function (candidate) {
        var isActive = candidate === button;
        candidate.classList.toggle("is-active", isActive);
        candidate.setAttribute("aria-pressed", isActive ? "true" : "false");
      });
      searchProducts();
    });
  });

  reviewButtons.forEach(function (button) {
    button.addEventListener("click", openReview);
  });

  builder.querySelectorAll("[data-uninet-quote-dialog-close]").forEach(function (button) {
    button.addEventListener("click", closeReview);
  });

  submitButton.addEventListener("click", submitQuote);

  dialog.addEventListener("cancel", function () {
    document.documentElement.classList.remove("uninet-quote-dialog-open");
  });

  form.addEventListener("input", function (event) {
    clearError();
    if (event.target && event.target.name === "kra_pin") {
      event.target.value = event.target.value.toUpperCase().slice(0, 11);
    }
  });

  form.addEventListener("submit", function (event) {
    event.preventDefault();
    openReview();
  });

  document.addEventListener("click", function (event) {
    if (!searchWrap.contains(event.target)) {
      resultsPanel.hidden = true;
    }
  });

  if (initialProduct && !findItem(initialProduct.id)) {
    addProduct(initialProduct, { prefill: true, scroll: false });
  } else {
    renderLines();

    if (initialProduct) {
      track(config.events && config.events.quotePrefill, {
        product_id: initialProduct.id,
        source: entrySource,
        already_present: true
      });
    }
  }
})();
