(function () {
  "use strict";

  function track(name, params) {
    if (typeof window.uninetTrack === "function") {
      window.uninetTrack(name, params || {});
    }
  }

  document.addEventListener("click", function (event) {
    var phoneLink = event.target.closest('[href^="tel:"]');
    var whatsappLink = event.target.closest('a[href*="wa.me"], a[href*="whatsapp"]');

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
})();
