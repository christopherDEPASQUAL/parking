import styles from "./Skeleton.module.css";
import { cn } from "../../utils/cn";

interface SkeletonProps {
  height?: number;
  width?: string | number;
  className?: string;
}

export function Skeleton({ height = 16, width = "100%", className }: SkeletonProps) {
  return <div className={cn(styles.skeleton, className)} style={{ height, width }} />;
}
