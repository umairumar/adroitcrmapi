import axios from "axios";

const apiBaseURL = (import.meta.env.VITE_API_BASE_URL as string) || "";

/** Relative /api path = Vite proxy to local Laravel; use Sanctum cookies + CSRF. */
export const usesApiProxy = apiBaseURL.startsWith("/");

const rootBaseURL = usesApiProxy
  ? ""
  : apiBaseURL.replace(/\/api\/v\d+\/?$/i, "");

const clientDefaults = {
  withCredentials: usesApiProxy,
  xsrfCookieName: usesApiProxy ? "XSRF-TOKEN" : undefined,
  xsrfHeaderName: usesApiProxy ? "X-XSRF-TOKEN" : undefined,
  headers: {
    Accept: "application/json",
  },
};

export const rootApi = axios.create({
  baseURL: rootBaseURL,
  ...clientDefaults,
});

const api = axios.create({
  baseURL: apiBaseURL,
  ...clientDefaults,
});

// Optional: Authorization token
api.interceptors.request.use((config) => {
  const token = localStorage.getItem("token");
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem("token");
      localStorage.removeItem("role");
      localStorage.removeItem("user");
      localStorage.removeItem("tenant");
      if (window.location.pathname !== "/login") {
        window.location.href = "/login";
      }
    }
    return Promise.reject(error);
  }
);

export default api;
