import { RouterProvider } from "react-router-dom";
import { router } from "./router";
import { AuthProvider } from "../shared/providers/AuthProvider";
import { QueryProvider } from "../shared/providers/QueryProvider";
import { ToastProvider } from "../shared/ui";
import { ErrorBoundary } from "./ErrorBoundary";

export function App() {
  return (
    <ErrorBoundary>
      <QueryProvider>
        <AuthProvider>
          <ToastProvider>
            <RouterProvider router={router} />
          </ToastProvider>
        </AuthProvider>
      </QueryProvider>
    </ErrorBoundary>
  );
}
