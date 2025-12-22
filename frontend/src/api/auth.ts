import { z } from "zod";
import { request } from "./http";
import { authResponseSchema, userSchema } from "../entities/user";

const loginPayloadSchema = z.object({
  email: z.string().email(),
  password: z.string().min(6),
});

const registerPayloadSchema = z.object({
  email: z.string().email(),
  password: z.string().min(6),
  first_name: z.string().min(1),
  last_name: z.string().min(1),
  role: z.enum(["client", "proprietor"]),
});

const refreshResponseSchema = z.object({
  access_token: z.string().optional(),
  token: z.string().optional(),
});

export type LoginPayload = z.infer<typeof loginPayloadSchema>;
export type RegisterPayload = z.infer<typeof registerPayloadSchema>;

export async function login(payload: LoginPayload) {
  return request("/auth/login", {
    method: "POST",
    body: JSON.stringify(payload),
  }, authResponseSchema);
}

export async function register(payload: RegisterPayload) {
  return request("/auth/register", {
    method: "POST",
    body: JSON.stringify(payload),
  }, authResponseSchema);
}

export async function refreshToken(refreshTokenValue: string) {
  return request(
    "/auth/refresh",
    {
      method: "POST",
      body: JSON.stringify({ refresh_token: refreshTokenValue }),
    },
    refreshResponseSchema
  );
}

export async function fetchMe() {
  return request("/auth/me", { method: "GET" }, userSchema);
}
