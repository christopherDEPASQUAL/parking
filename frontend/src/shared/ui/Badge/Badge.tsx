import styles from "./Badge.module.css";
import { cn } from "../../utils/cn";

export type BadgeVariant = "neutral" | "success" | "warning" | "error" | "info";

interface BadgeProps {
  label: string;
  variant?: BadgeVariant;
}

export function Badge({ label, variant = "neutral" }: BadgeProps) {
  return <span className={cn(styles.badge, styles[variant])}>{label}</span>;
}
