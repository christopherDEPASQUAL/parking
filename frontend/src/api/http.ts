import { z } from "zod";

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8000";
const ACCESS_TOKEN_KEY = "parking.token";
const REFRESH_TOKEN_KEY = "parking.refreshToken";
let refreshPromise: Promise<string | null> | null = null;

export class ApiError extends Error {
  status: number;
  payload?: unknown;

  constructor(message: string, status: number, payload?: unknown) {
    super(message);
    this.status = status;
    this.payload = payload;
  }
}

export async function request<T>(
  path: string,
  options: RequestInit = {},
  schema?: z.ZodSchema<T>,
  retry: boolean = true
): Promise<T> {
  const token = localStorage.getItem(ACCESS_TOKEN_KEY);
  const headers = new Headers(options.headers);
  const isFormData = typeof FormData !== "undefined" && options.body instanceof FormData;
  if (!headers.has("Content-Type") && !isFormData) {
    headers.set("Content-Type", "application/json");
  }
  if (token) {
    headers.set("Authorization", `Bearer ${token}`);
  }

  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    headers,
  });

  const contentType = response.headers.get("content-type") || "";
  const text = await response.text();
  const data = contentType.includes("application/json") && text ? JSON.parse(text) : text;

  const shouldAttemptRefresh =
    retry &&
    response.status === 401 &&
    !path.startsWith("/auth/login") &&
    !path.startsWith("/auth/register") &&
    !path.startsWith("/auth/refresh");

  if (shouldAttemptRefresh) {
    const refreshed = await refreshAccessToken();
    if (refreshed) {
      return request(path, options, schema, false);
    }
  }

  if (!response.ok) {
    const message = typeof data === "string" ? data : (data as any)?.message || "Request failed";
    throw new ApiError(message, response.status, data);
  }

  if (schema) {
    return schema.parse(data);
  }

  return data as T;
}

async function refreshAccessToken(): Promise<string | null> {
  const refreshToken = localStorage.getItem(REFRESH_TOKEN_KEY);
  if (!refreshToken) {
    return null;
  }

  if (!refreshPromise) {
    refreshPromise = (async () => {
      try {
        const response = await fetch(`${API_BASE_URL}/auth/refresh`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ refresh_token: refreshToken }),
        });
        const contentType = response.headers.get("content-type") || "";
        const text = await response.text();
        const data = contentType.includes("application/json") && text ? JSON.parse(text) : null;

        if (!response.ok) {
          throw new Error((data as any)?.message || "Refresh failed");
        }

        const newToken = (data as any)?.token || (data as any)?.access_token;
        if (typeof newToken === "string" && newToken.length > 0) {
          localStorage.setItem(ACCESS_TOKEN_KEY, newToken);
          return newToken;
        }

        throw new Error("Missing access token");
      } catch {
        localStorage.removeItem(ACCESS_TOKEN_KEY);
        localStorage.removeItem(REFRESH_TOKEN_KEY);
        return null;
      } finally {
        refreshPromise = null;
      }
    })();
  }

  return refreshPromise;
}
