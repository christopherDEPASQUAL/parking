import { z } from "zod";
import { request } from "./http";
import { reservationSchema } from "../entities/reservation";

const reservationListSchema = z.object({
  items: z.array(reservationSchema),
});

export function createReservation(payload: Record<string, unknown>) {
  return request("/reservations", {
    method: "POST",
    body: JSON.stringify(payload),
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
