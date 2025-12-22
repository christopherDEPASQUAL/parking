import { z } from "zod";
import { request } from "./http";
import { toApiDateTime } from "../shared/utils/format";
import { parkingSchema, pricingPlanSchema } from "../entities/parking";
import { reservationSchema } from "../entities/reservation";
import { stationingSchema } from "../entities/stationing";

export const parkingListSchema = z.object({
  items: z.array(parkingSchema),
});

export const availabilitySchema = z.object({
  free_spots: z.number().int(),
  capacity: z.number().int(),
});

const ownerReservationListSchema = z.object({
  items: z.array(reservationSchema),
});

const ownerStationingListSchema = z.object({
  items: z.array(stationingSchema),
});

const revenueSchema = z.object({
  total_cents: z.coerce.number().int().optional(),
  amount_cents: z.coerce.number().int().optional(),
});

const overstayerSchema = z.object({
  session_id: z.string(),
  user_id: z.string(),
  parking_id: z.string(),
  reservation_id: z.string().nullable().optional(),
  abonnement_id: z.string().nullable().optional(),
  started_at: z.string(),
});

const overstayerListSchema = z.object({
  items: z.array(overstayerSchema),
});

export function searchParkings(params: {
  lat: number;
  lng: number;
  radius: number;
  starts_at: string;
  ends_at: string;
  name?: string;
}) {
  const query = new URLSearchParams({
    lat: String(params.lat),
    lng: String(params.lng),
    radius: String(params.radius),
    starts_at: toApiDateTime(params.starts_at),
    ends_at: toApiDateTime(params.ends_at),
  });
  if (params.name) {
    query.set("name", params.name);
  }
  return request(`/parkings/search?${query.toString()}`, { method: "GET" }, parkingListSchema);
}

export function getParking(id: string) {
  return request(`/parkings/${id}`, { method: "GET" }, parkingSchema);
}

export function getParkingAvailability(id: string, params: { starts_at: string; ends_at: string }) {
  const query = new URLSearchParams({
    starts_at: toApiDateTime(params.starts_at),
    ends_at: toApiDateTime(params.ends_at),
  });
  return request(`/parkings/${id}/availability?${query.toString()}`, { method: "GET" }, availabilitySchema);
}

export function createParking(payload: Record<string, unknown>) {
  return request("/owner/parkings", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function updateParking(id: string, payload: Record<string, unknown>) {
  return request(`/owner/parkings/${id}`, {
    method: "PATCH",
    body: JSON.stringify(payload),
  });
}

export function getOwnerParkings() {
  return request("/owner/parkings", { method: "GET" }, parkingListSchema);
}

export function getParkingRevenue(id: string, month: string) {
  return request(
    `/owner/parkings/${id}/revenue?month=${encodeURIComponent(month)}`,
    { method: "GET" },
    revenueSchema
  ).then((data) => ({
    ...data,
    total_cents: data.total_cents ?? data.amount_cents ?? 0,
  }));
}

export function getParkingOverstayers(id: string, month?: string) {
  const query = month ? `?month=${encodeURIComponent(month)}` : "";
  return request(`/owner/parkings/${id}/overstayers${query}`, { method: "GET" }, overstayerListSchema);
}

export function getParkingReservations(id: string) {
  return request(`/owner/parkings/${id}/reservations`, { method: "GET" }, ownerReservationListSchema);
}

export function getParkingStationings(id: string) {
  return request(`/owner/parkings/${id}/stationings`, { method: "GET" }, ownerStationingListSchema);
}

export function getParkingOpeningHours(id: string) {
  return request(`/owner/parkings/${id}/opening-hours`, { method: "GET" });
}

export function updateParkingOpeningHours(id: string, payload: Record<string, unknown>) {
  return request(`/owner/parkings/${id}/opening-hours`, {
    method: "PATCH",
    body: JSON.stringify(payload),
  });
}

export function getPricingPlan(id: string) {
  return request(`/owner/parkings/${id}/pricing-plan`, { method: "GET" }, pricingPlanSchema);
}

export function updatePricingPlan(id: string, payload: Record<string, unknown>) {
  return request(`/owner/parkings/${id}/pricing-plan`, {
    method: "PATCH",
    body: JSON.stringify(payload),
  });
}
