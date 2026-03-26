document.addEventListener("DOMContentLoaded", async () => {
  const statusText = document.querySelector("[data-status-text]");
  const loginLink = document.querySelector("[data-login-link]");
  const token = new URLSearchParams(window.location.search).get("token") || "";

  const updateStatus = (message, type) => {
    if (statusText) {
      statusText.textContent = message;
    }

    PortalVidaLivreAuth.showMessage(message, type);
  };

  if (!token) {
    updateStatus("O link informado e invalido ou expirou.", "error");
    return;
  }

  try {
    await PortalVidaLivreApi.getCsrfToken();
    const response = await PortalVidaLivreApi.post(
      "verify-email.php",
      { token },
      { csrf: true }
    );

    if (loginLink) {
      loginLink.setAttribute("href", "./login.html?status=email-verified");
    }

    updateStatus(response.message, "success");
  } catch (error) {
    updateStatus(error.message || "O link informado e invalido ou expirou.", "error");
  }
});
