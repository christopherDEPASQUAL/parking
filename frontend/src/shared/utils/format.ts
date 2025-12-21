import { format } from "date-fns";

export function formatCurrency(cents: number, currency: string = "EUR") {
  const value = cents / 100;
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency,
    maximumFractionDigits: 2,
  }).format(value);
}

export function formatDateTime(value?: string) {
  if (!value) {
    return "-";
  }
  return format(new Date(value), "PPP p");
}

export function formatDate(value?: string) {
  if (!value) {
    return "-";
  }
  return format(new Date(value), "PPP");
}
