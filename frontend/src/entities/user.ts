import { z } from "zod";

export const userSchema = z.object({
  id: z.string(),
  email: z.string().email(),
  first_name: z.string().optional(),
  last_name: z.string().optional(),
  role: z.enum(["admin", "client", "proprietor"]),
});

export type User = z.infer<typeof userSchema>;

export const authResponseSchema = z.object({
  token: z.string().optional(),
  user: userSchema,
});

export type AuthResponse = z.infer<typeof authResponseSchema>;
