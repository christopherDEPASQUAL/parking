import React from "react";
import { Button } from "../shared/ui";
import styles from "./ErrorBoundary.module.css";

interface State {
  hasError: boolean;
}

export class ErrorBoundary extends React.Component<{ children: React.ReactNode }, State> {
  state: State = { hasError: false };

  static getDerivedStateFromError() {
    return { hasError: true };
  }

  render() {
    if (this.state.hasError) {
      return (
        <div className={styles.wrapper}>
          <h2>Something went wrong</h2>
          <p>Please refresh the page or try again later.</p>
          <Button onClick={() => window.location.reload()}>Reload</Button>
        </div>
      );
    }

    return this.props.children;
  }
}
