import React from "react";
import styles from "./Table.module.css";
import { cn } from "../../utils/cn";

interface TableProps {
  columns: string[];
  children: React.ReactNode;
  className?: string;
}

export function Table({ columns, children, className }: TableProps) {
  return (
    <div className={cn(styles.wrapper, className)}>
      <table className={styles.table}>
        <thead>
          <tr>
            {columns.map((col) => (
              <th key={col}>{col}</th>
            ))}
          </tr>
        </thead>
        <tbody>{children}</tbody>
      </table>
    </div>
  );
}
