document.addEventListener("DOMContentLoaded", async () => {
  const form = document.querySelector("#reset-password-form");
  const newLink = document.querySelector("[data-new-link]");
  const token = new URLSearchParams(window.location.search).get("token") || "";

  if (!form) {
    return;
  }

  const disableForm = (message) => {
    form.classList.add("hidden");

    if (newLink) {
      newLink.classList.remove("hidden");
    }

    PortalVidaLivreAuth.showMessage(message, "error");
  };

  if (!token) {
    disableForm("O link informado e invalido ou expirou.");
    return;
  }

  try {
    await Promise.all([
      PortalVidaLivreApi.getCsrfToken(),
      PortalVidaLivreApi.get(`reset-password.php?token=${encodeURIComponent(token)}`),
    ]);
  } catch (error) {
    disableForm(error.message || "O link informado e invalido ou expirou.");
    return;
  }

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    PortalVidaLivreAuth.clearMessage();
    PortalVidaLivreAuth.clearFieldErrors(form);

    const data = PortalVidaLivreAuth.formDataToObject(form);
    const errors = {};

    data.token = token;

    const passwordErrors = PortalVidaLivreAuth.passwordStrengthErrors(data.password || "");
    if (passwordErrors.length > 0) {
      errors.password = passwordErrors;
    }

    if (!data.password_confirmation) {
      errors.password_confirmation = ["Confirme sua nova senha."];
    } else if (data.password !== data.password_confirmation) {
      errors.password_confirmation = ["A confirmacao deve ser igual a senha."];
    }

    if (Object.keys(errors).length > 0) {
      PortalVidaLivreAuth.applyErrors(form, errors);
      PortalVidaLivreAuth.showMessage("Verifique os campos informados.", "error");
      return;
    }

    try {
      const response = await PortalVidaLivreApi.post("reset-password.php", data, { csrf: true });
      PortalVidaLivreAuth.showMessage(response.message, "success");
      form.reset();
      window.setTimeout(() => {
        window.location.assign("/frontend/login.html?status=password-reset");
      }, 500);
    } catch (error) {
      PortalVidaLivreAuth.applyErrors(form, error.errors || {});
      PortalVidaLivreAuth.showMessage(error.message || "Nao foi possivel redefinir a senha.", "error");
    }
  });
});
