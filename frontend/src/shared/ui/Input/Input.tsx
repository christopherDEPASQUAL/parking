import React from "react";
import styles from "./Input.module.css";
import { cn } from "../../utils/cn";

interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label?: string;
  helperText?: string;
  error?: string;
}

export const Input = React.forwardRef<HTMLInputElement, InputProps>(
  ({ label, helperText, error, className, id, ...props }, ref) => {
    const inputId = id ?? `input-${Math.random().toString(36).slice(2)}`;
    return (
      <div className={styles.field}>
        {label ? (
          <label htmlFor={inputId} className={styles.label}>
            {label}
          </label>
        ) : null}
        <input
          ref={ref}
          id={inputId}
          className={cn(styles.input, error && styles.error, className)}
          aria-invalid={!!error}
          {...props}
        />
        {helperText && !error ? <div className={styles.helper}>{helperText}</div> : null}
        {error ? <div className={styles.errorText}>{error}</div> : null}
      </div>
    );
  }
);
Input.displayName = "Input";
