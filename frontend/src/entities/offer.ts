import { z } from "zod";
import { openingSlotSchema } from "./parking";

export const offerStatusSchema = z.enum(["active", "inactive"]);

export const subscriptionOfferSchema = z.object({
  offer_id: z.string(),
  parking_id: z.string(),
  label: z.string(),
  type: z.enum(["full", "weekend", "evening", "custom"]),
  price_cents: z.number().int(),
  status: offerStatusSchema,
  weekly_time_slots: z.array(openingSlotSchema),
});

export type SubscriptionOffer = z.infer<typeof subscriptionOfferSchema>;
