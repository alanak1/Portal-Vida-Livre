const PortalVidaLivreApi = (() => {
  const API_BASE = "/backend/api";
  const STORAGE_KEY = "portal-vida-livre-csrf";
  let csrfToken = sessionStorage.getItem(STORAGE_KEY) || "";

  const saveCsrfToken = (token) => {
    if (!token) {
      return;
    }

    csrfToken = token;
    sessionStorage.setItem(STORAGE_KEY, token);
  };

  const parseJson = async (response) => {
    let payload = {};

    try {
      payload = await response.json();
    } catch (error) {
      payload = {
        success: false,
        message: "Resposta invalida do servidor.",
        errors: {},
      };
    }

    if (payload?.data?.csrf_token) {
      saveCsrfToken(payload.data.csrf_token);
    }

    if (!response.ok || payload.success === false) {
      throw {
        status: response.status,
        message: payload.message || "Nao foi possivel processar a solicitacao.",
        errors: payload.errors || {},
        data: payload.data || {},
      };
    }

    return payload;
  };

  const request = async (path, options = {}) => {
    const settings = {
      method: options.method || "GET",
      credentials: "same-origin",
      headers: {
        Accept: "application/json",
        ...(options.headers || {}),
      },
    };

    if (options.csrf) {
      if (!csrfToken) {
        await getCsrfToken();
      }

      settings.headers["X-CSRF-Token"] = csrfToken;
    }

    if (options.body !== undefined) {
      settings.headers["Content-Type"] = "application/json";
      settings.body = JSON.stringify(options.body);
    }

    let response;

    try {
      response = await fetch(`${API_BASE}/${path}`, settings);
    } catch (error) {
      throw {
        status: 0,
        message: "Nao foi possivel conectar ao servidor.",
        errors: {},
        data: {},
      };
    }

    return parseJson(response);
  };

  const getCsrfToken = async (force = false) => {
    if (csrfToken && !force) {
      return csrfToken;
    }

    const response = await request("csrf.php");
    saveCsrfToken(response.data.csrf_token || "");

    return csrfToken;
  };

  return {
    request,
    get: (path) => request(path),
    post: (path, body, options = {}) =>
      request(path, { ...options, method: "POST", body }),
    getCsrfToken,
    saveCsrfToken,
  };
})();
