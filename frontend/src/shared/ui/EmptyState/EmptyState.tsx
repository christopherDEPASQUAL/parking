import styles from "./EmptyState.module.css";
import { Button } from "../Button";

interface EmptyStateProps {
  title: string;
  description: string;
  actionLabel?: string;
  onAction?: () => void;
}

export function EmptyState({ title, description, actionLabel, onAction }: EmptyStateProps) {
  return (
    <div className={styles.empty}>
      <div className={styles.icon} aria-hidden="true" />
      <h3>{title}</h3>
      <p>{description}</p>
      {actionLabel && onAction ? (
        <Button variant="secondary" onClick={onAction}>
          {actionLabel}
        </Button>
      ) : null}
    </div>
  );
}
