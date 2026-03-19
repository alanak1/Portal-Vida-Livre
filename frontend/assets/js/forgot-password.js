document.addEventListener("DOMContentLoaded", async () => {
  const form = document.querySelector("#forgot-password-form");

  if (!form) {
    return;
  }

  try {
    await PortalVidaLivreApi.getCsrfToken();
  } catch (error) {
    PortalVidaLivreAuth.showMessage("Nao foi possivel iniciar a sessao do formulario.", "error");
  }

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    PortalVidaLivreAuth.clearMessage();
    PortalVidaLivreAuth.clearFieldErrors(form);

    const data = PortalVidaLivreAuth.formDataToObject(form);
    const errors = {};
    data.email = (data.email || "").trim().toLowerCase();

    if (!PortalVidaLivreAuth.isValidEmail(data.email || "")) {
      errors.email = ["Informe um e-mail valido."];
    }

    if (Object.keys(errors).length > 0) {
      PortalVidaLivreAuth.applyErrors(form, errors);
      PortalVidaLivreAuth.showMessage("Verifique os campos informados.", "error");
      return;
    }

    try {
      const response = await PortalVidaLivreApi.post("forgot-password.php", data, { csrf: true });
      PortalVidaLivreAuth.showMessage(response.message, "success");
      form.reset();
    } catch (error) {
      PortalVidaLivreAuth.applyErrors(form, error.errors || {});
      PortalVidaLivreAuth.showMessage(error.message || "Nao foi possivel enviar o link.", "error");
    }
  });
});
