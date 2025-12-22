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
          <h2>Une erreur est survenue</h2>
          <p>Veuillez rafraîchir la page ou réessayer plus tard.</p>
          <Button onClick={() => window.location.reload()}>Recharger</Button>
        </div>
      );
    }

    return this.props.children;
  }
}
