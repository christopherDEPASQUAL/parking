export const dayLabels = ["Dim", "Lun", "Mar", "Mer", "Jeu", "Ven", "Sam"] as const;

export function formatDay(day: number) {
  return dayLabels[day] ?? String(day);
}
