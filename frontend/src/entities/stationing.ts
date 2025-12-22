import { z } from "zod";

export const stationingSchema = z.object({
  id: z.string(),
  parking_id: z.string(),
  user_id: z.string().optional(),
  entered_at: z.string(),
  exited_at: z.string().nullable().optional(),
  amount_cents: z.number().int().nullable().optional(),
  status: z.string().optional(),
  reservation_id: z.string().nullable().optional(),
  subscription_id: z.string().nullable().optional(),
});

export type Stationing = z.infer<typeof stationingSchema>;
