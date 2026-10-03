document.querySelectorAll("form[data-confirm]").forEach((form) => {
  form.addEventListener("submit", (event) => {
    const message = form.getAttribute("data-confirm") || "Confirmer cette action ?";
    if (!window.confirm(message)) {
      event.preventDefault();
    }
  });
});

const invalid = document.querySelector("[aria-invalid='true']");
if (invalid instanceof HTMLElement) {
  invalid.focus();
}
