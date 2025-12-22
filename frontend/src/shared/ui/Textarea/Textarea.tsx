import React from "react";
import styles from "./Textarea.module.css";
import { cn } from "../../utils/cn";

interface TextareaProps extends React.TextareaHTMLAttributes<HTMLTextAreaElement> {
  label?: string;
  helperText?: string;
  error?: string;
}

export const Textarea = React.forwardRef<HTMLTextAreaElement, TextareaProps>(
  ({ label, helperText, error, className, id, ...props }, ref) => {
    const inputId = id ?? `textarea-${Math.random().toString(36).slice(2)}`;
    return (
      <div className={styles.field}>
        {label ? (
          <label htmlFor={inputId} className={styles.label}>
            {label}
          </label>
        ) : null}
        <textarea
          ref={ref}
          id={inputId}
          className={cn(styles.textarea, error && styles.error, className)}
          aria-invalid={!!error}
          {...props}
        />
        {helperText && !error ? <div className={styles.helper}>{helperText}</div> : null}
        {error ? <div className={styles.errorText}>{error}</div> : null}
      </div>
    );
  }
);
Textarea.displayName = "Textarea";
