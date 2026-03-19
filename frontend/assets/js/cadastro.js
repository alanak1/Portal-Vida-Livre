document.addEventListener("DOMContentLoaded", async () => {
  const form = document.querySelector("#register-form");

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
    data.name = (data.name || "").trim();
    data.email = (data.email || "").trim().toLowerCase();

    if (!data.name || data.name.length < 3) {
      errors.name = ["Informe seu nome com pelo menos 3 caracteres."];
    } else if (data.name.length > 120) {
      errors.name = ["O nome deve ter no maximo 120 caracteres."];
    }

    if (!PortalVidaLivreAuth.isValidEmail(data.email || "")) {
      errors.email = ["Informe um e-mail valido."];
    }

    const passwordErrors = PortalVidaLivreAuth.passwordStrengthErrors(data.password || "");
    if (passwordErrors.length > 0) {
      errors.password = passwordErrors;
    }

    if (!data.password_confirmation) {
      errors.password_confirmation = ["Confirme sua senha."];
    } else if (data.password !== data.password_confirmation) {
      errors.password_confirmation = ["A confirmacao deve ser igual a senha."];
    }

    if (Object.keys(errors).length > 0) {
      PortalVidaLivreAuth.applyErrors(form, errors);
      PortalVidaLivreAuth.showMessage("Verifique os campos informados.", "error");
      return;
    }

    try {
      const response = await PortalVidaLivreApi.post("register.php", data, { csrf: true });
      PortalVidaLivreAuth.showMessage(response.message, "success");
      form.reset();
      window.setTimeout(() => {
        window.location.assign("/frontend/login.html?status=registered");
      }, 500);
    } catch (error) {
      PortalVidaLivreAuth.applyErrors(form, error.errors || {});
      PortalVidaLivreAuth.showMessage(error.message || "Nao foi possivel concluir o cadastro.", "error");
    }
  });
});
