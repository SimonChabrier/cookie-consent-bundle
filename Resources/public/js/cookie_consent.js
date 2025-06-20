document.addEventListener("DOMContentLoaded", () => {
  const cookieConsent = document.querySelector(".ch-cookie-consent");
  const manageBtn = document.querySelector(".ch-cookie-consent__manage-btn");
  const cookieConsentForm = document.querySelector(".ch-cookie-consent__form");
  const cookieConsentFormBtn = document.querySelectorAll(".ch-cookie-consent__btn");
  const cookieConsentCategoryDetails = document.querySelector(".ch-cookie-consent__category-group");
  const cookieConsentCategoryDetailsToggle = document.querySelector(".ch-cookie-consent__toggle-details");

  // If cookie consent is direct child of body, assume it should be placed on top of the site pushing down the rest of the website
  if (cookieConsent && cookieConsent.parentNode.nodeName === "BODY") {
    if (cookieConsent.classList.contains("ch-cookie-consent--top")) {
      document.body.style.marginTop = `${cookieConsent.offsetHeight}px`;

      cookieConsent.style.position = "absolute";
      cookieConsent.style.top = "0";
      cookieConsent.style.left = "0";
    } else {
      document.body.style.marginBottom = `${cookieConsent.offsetHeight}px`;

      cookieConsent.style.position = "fixed";
      cookieConsent.style.bottom = "0";
      cookieConsent.style.left = "0";
    }
  }

  if (manageBtn) {
    manageBtn.addEventListener("click", () => {
      console.warn("Manage cookies button clicked");
      console.warn("cookieConsent", cookieConsent);
      manageBtn.style.display = "none";
      if (cookieConsent) {
        cookieConsent.style.display = "block";
      }
    });
  }

  if (cookieConsentForm) {
    // Submit form via ajax
    cookieConsentFormBtn.forEach(btn => {
      btn.addEventListener(
        "click",
        event => {
          event.preventDefault();

          const formAction = cookieConsentForm.action ? cookieConsentForm.action : location.href;

          fetch(formAction, {
            method: "POST",
            headers: {
              "Content-Type": "application/x-www-form-urlencoded",
              "X-Requested-With": "XMLHttpRequest"
            },
            body: serializeForm(cookieConsentForm, event.target)
          }).then(response => {
            if (response.ok) {
              cookieConsent.style.display = "none";
              if (manageBtn) manageBtn.style.display = "block";
              const buttonEvent = new CustomEvent("cookie-consent-form-submit-successful", {
                detail: event.target
              });
              document.dispatchEvent(buttonEvent);
              // Force <head> refresh to update when using UXTurbo or Turbo
              window.location.reload();
            }
          });

          // Clear all styles from body to prevent the white margin at the end of the page
          document.body.style.marginBottom = null;
          document.body.style.marginTop = null;
        },
        false
      );
    });
  }

  if (cookieConsentCategoryDetails && cookieConsentCategoryDetailsToggle) {
    cookieConsentCategoryDetailsToggle.addEventListener("click", () => {
      const detailsIsHidden = cookieConsentCategoryDetails.style.display !== "block";
      cookieConsentCategoryDetails.style.display = detailsIsHidden ? "block" : "none";
      cookieConsentCategoryDetailsToggle.querySelector(".ch-cookie-consent__toggle-details-hide").style.display = detailsIsHidden ? "block" : "none";
      cookieConsentCategoryDetailsToggle.querySelector(".ch-cookie-consent__toggle-details-show").style.display = detailsIsHidden ? "none" : "block";
    });
  }
});

const serializeForm = (form, clickedButton) => {
  const formData = new FormData(form);
  formData.append(clickedButton.getAttribute("name"), "");

  const params = new URLSearchParams();
  for (const [key, value] of formData.entries()) {
    params.append(key, value);
  }

  return params.toString();
};

if (typeof window.CustomEvent !== "function") {
  const CustomEventPolyfill = (event, params = { bubbles: false, cancelable: false, detail: undefined }) => {
    const evt = document.createEvent("CustomEvent");
    evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
    return evt;
  };

  CustomEventPolyfill.prototype = window.Event.prototype;

  window.CustomEvent = CustomEventPolyfill;
}
