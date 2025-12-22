import { format } from "date-fns";
import { fr } from "date-fns/locale";

export function formatCurrency(cents: number, currency: string = "EUR") {
  const value = cents / 100;
  return new Intl.NumberFormat("fr-FR", {
    style: "currency",
    currency,
    maximumFractionDigits: 2,
  }).format(value);
}

export function formatDateTime(value?: string) {
  if (!value) {
    return "-";
  }
  return format(new Date(value), "PPP p", { locale: fr });
}

export function formatDate(value?: string) {
  if (!value) {
    return "-";
  }
  return format(new Date(value), "PPP", { locale: fr });
}

export function toLocalDateTimeInputValue(date: Date) {
  return format(date, "yyyy-MM-dd'T'HH:mm");
}

export function toLocalMonthInputValue(date: Date) {
  return format(date, "yyyy-MM");
}

export function toApiDateTime(value: string) {
  return new Date(value).toISOString();
}

export function toLocalDateInputValue(date: Date) {
  return format(date, "yyyy-MM-dd");
}
