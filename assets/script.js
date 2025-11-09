(function () {
  "use strict";

  var widgetIds = {};

  function initRecaptchas() {
    var nodes = document.querySelectorAll(".g-recaptcha");
    if (!nodes.length) return;

    nodes.forEach(function (node, i) {
      if (!node.id) node.id = "recaptcha-auto-" + i;

      if (widgetIds[node.id] !== undefined && node.querySelector("iframe")) {
        return;
      }

      function renderNow() {
        widgetIds[node.id] = grecaptcha.render(node, {
          sitekey: window.recaptchaOptions.key,
        });
      }

      if (window.grecaptcha && typeof grecaptcha.render === "function") {
        renderNow();
      } else {
        setTimeout(initRecaptchas, 50);
      }
    });
  }

  window.globalRecaptcha = initRecaptchas;

  document.addEventListener("DOMContentLoaded", initRecaptchas);

  if (typeof BX !== "undefined") {
    BX.addCustomEvent("onAjaxSuccess", function () {
      setTimeout(initRecaptchas, 50);
    });
  }
})();
