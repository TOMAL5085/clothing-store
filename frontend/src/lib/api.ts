const API_URL = (import.meta.env.VITE_API_URL ?? "http://localhost:8000/api/v1").replace(/\/$/, "");

export class ApiError extends Error {
  status: number;
  errors?: Record<string, string[]>;

  constructor(message: string, status: number, errors?: Record<string, string[]>) {
    super(message);
    this.status = status;
    this.errors = errors;
  }
}

function token() {
  return localStorage.getItem("jaaj-api-token");
}

export function hasApiToken() {
  return Boolean(token());
}

export function setApiToken(value: string | null) {
  if (value) localStorage.setItem("jaaj-api-token", value);
  else localStorage.removeItem("jaaj-api-token");
}

export async function api<T>(path: string, options: RequestInit = {}): Promise<T> {
  const headers = new Headers(options.headers);
  headers.set("Accept", "application/json");
  if (!(options.body instanceof FormData)) headers.set("Content-Type", "application/json");
  const bearer = token();
  if (bearer) headers.set("Authorization", `Bearer ${bearer}`);

  const response = await fetch(`${API_URL}${path}`, { ...options, headers });
  const payload = await response.json().catch(() => ({}));

  if (!response.ok) {
    throw new ApiError(payload.message ?? "Request failed.", response.status, payload.errors);
  }

  return payload as T;
}

export type ApiCollection<T> = { data: T[] };
export type ApiItem<T> = { data: T };
