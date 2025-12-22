import { z } from "zod";
import { request } from "./http";

const createSubscriptionResponseSchema = z.object({
  abonnement_id: z.string(),
  offer_id: z.string().optional(),
  status: z.string(),
  start_date: z.string(),
  end_date: z.string(),
});

export function createSubscription(payload: {
  parking_id: string;
  offer_id: string;
  start_date: string;
  end_date: string;
}) {
  return request(
    "/api/abonnements",
    {
      method: "POST",
      body: JSON.stringify(payload),
    },
    createSubscriptionResponseSchema
  );
}
