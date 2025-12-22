import { z } from "zod";
import { request } from "./http";
import { subscriptionOfferSchema } from "../entities/offer";

const offerListSchema = z.object({
  items: z.array(subscriptionOfferSchema),
});

export function getParkingOffers(parkingId: string) {
  return request(`/parkings/${parkingId}/subscription-offers`, { method: "GET" }, offerListSchema);
}

export function createOffer(parkingId: string, payload: Record<string, unknown>) {
  return request(`/owner/parkings/${parkingId}/subscription-offers`, {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function updateOffer(offerId: string, payload: Record<string, unknown>) {
  return request(`/owner/subscription-offers/${offerId}`, {
    method: "PATCH",
    body: JSON.stringify(payload),
  });
}

export function deleteOffer(offerId: string) {
  return request(`/owner/subscription-offers/${offerId}`, { method: "DELETE" });
}

export function addOfferSlot(offerId: string, payload: Record<string, unknown>) {
  return request(`/owner/subscription-offers/${offerId}/slots`, {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function deleteOfferSlot(offerId: string, slotId: string) {
  return request(`/owner/subscription-offers/${offerId}/slots/${slotId}`, { method: "DELETE" });
}
