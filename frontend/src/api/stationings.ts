import { z } from "zod";
import { request } from "./http";
import { stationingSchema } from "../entities/stationing";

const stationingListSchema = z.object({
  items: z.array(stationingSchema),
});

export function enterStationing(payload: Record<string, unknown>) {
  return request("/stationings/enter", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function exitStationing(payload: Record<string, unknown>) {
  return request("/stationings/exit", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function getMyStationings() {
  return request("/stationings/me", { method: "GET" }, stationingListSchema);
}
