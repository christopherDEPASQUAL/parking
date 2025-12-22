import { z } from "zod";

export const reservationStatusSchema = z.enum([
  "pending_payment",
  "pending",
  "confirmed",
  "cancelled",
  "completed",
  "payment_failed",
]);

export const reservationSchema = z.object({
  id: z.string(),
  user_id: z.string().optional(),
  parking_id: z.string(),
  starts_at: z.string(),
  ends_at: z.string(),
  status: reservationStatusSchema,
  price_cents: z.number().int().optional(),
  currency: z.string().optional(),
});

export type Reservation = z.infer<typeof reservationSchema>;
export type ReservationStatus = z.infer<typeof reservationStatusSchema>;
