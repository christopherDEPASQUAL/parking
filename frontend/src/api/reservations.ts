import { z } from "zod";
import { request } from "./http";
import { toApiDateTime } from "../shared/utils/format";
import { reservationSchema } from "../entities/reservation";

const reservationListSchema = z.object({
  items: z.array(reservationSchema),
});

export function createReservation(payload: Record<string, unknown>) {
  const startsAt = typeof payload.starts_at === "string" ? toApiDateTime(payload.starts_at) : payload.starts_at;
  const endsAt = typeof payload.ends_at === "string" ? toApiDateTime(payload.ends_at) : payload.ends_at;
  const body = {
    ...payload,
    starts_at: startsAt,
    ends_at: endsAt,
  };

  return request("/reservations", {
    method: "POST",
    body: JSON.stringify(body),
  });
}

export function getMyReservations() {
  return request("/reservations/me", { method: "GET" }, reservationListSchema);
}

export function getReservation(id: string) {
  return request(`/reservations/${id}`, { method: "GET" }, reservationSchema);
}

export function cancelReservation(id: string) {
  return request(`/reservations/${id}/cancel`, { method: "POST" });
}

export function getReservationInvoice(id: string) {
  return request(`/invoices/reservations/${id}`, { method: "GET" });
}
