import { z } from "zod";

export const openingSlotSchema = z.object({
  start_day: z.number().int().min(0).max(6),
  end_day: z.number().int().min(0).max(6),
  start_time: z.string(),
  end_time: z.string(),
});

export const pricingTierSchema = z.object({
  upToMinutes: z.number().int().positive(),
  pricePerStepCents: z.number().int().nonnegative(),
});

export const pricingPlanSchema = z.object({
  tiers: z.array(pricingTierSchema),
  defaultPricePerStepCents: z.number().int().nonnegative(),
  overstayPenaltyCents: z.number().int().nonnegative().optional(),
  stepMinutes: z.number().int().optional(),
  subscriptionPrices: z.record(z.number().int()).optional(),
});

export const parkingSchema = z.object({
  id: z.string(),
  owner_id: z.string().optional(),
  name: z.string(),
  address: z.string(),
  description: z.string().nullable().optional(),
  latitude: z.number().optional(),
  longitude: z.number().optional(),
  capacity: z.number().int().optional(),
  opening_schedule: z.array(openingSlotSchema).optional(),
  pricing_plan: pricingPlanSchema.optional(),
});

export type Parking = z.infer<typeof parkingSchema>;
export type OpeningSlot = z.infer<typeof openingSlotSchema>;
export type PricingPlan = z.infer<typeof pricingPlanSchema>;
