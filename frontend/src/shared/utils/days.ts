export const dayLabels = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"] as const;

export function formatDay(day: number) {
  return dayLabels[day] ?? String(day);
}
