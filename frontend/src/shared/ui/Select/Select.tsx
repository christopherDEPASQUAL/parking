import React from "react";
import styles from "./Select.module.css";
import { cn } from "../../utils/cn";

interface SelectProps extends React.SelectHTMLAttributes<HTMLSelectElement> {
  label?: string;
  helperText?: string;
  error?: string;
}

export const Select = React.forwardRef<HTMLSelectElement, SelectProps>(
  ({ label, helperText, error, className, id, children, ...props }, ref) => {
    const inputId = id ?? `select-${Math.random().toString(36).slice(2)}`;
    return (
      <div className={styles.field}>
        {label ? (
          <label htmlFor={inputId} className={styles.label}>
            {label}
          </label>
        ) : null}
        <select
          ref={ref}
          id={inputId}
          className={cn(styles.select, error && styles.error, className)}
          aria-invalid={!!error}
          {...props}
        >
          {children}
        </select>
        {helperText && !error ? <div className={styles.helper}>{helperText}</div> : null}
        {error ? <div className={styles.errorText}>{error}</div> : null}
      </div>
    );
  }
);
Select.displayName = "Select";
