import { Navigate, Outlet } from "react-router-dom";
import { useAuth } from "../shared/providers/AuthProvider";

export function ProtectedRoute() {
  const { user, isReady } = useAuth();
  if (!isReady) {
    return <div className="container">Loading...</div>;
  }
  if (!user) {
    return <Navigate to="/login" replace />;
  }
  return <Outlet />;
}

export function RoleGuard({ roles }: { roles: Array<"admin" | "client" | "proprietor"> }) {
  const { user, isReady } = useAuth();
  if (!isReady) {
    return <div className="container">Loading...</div>;
  }
  if (!user) {
    return <Navigate to="/login" replace />;
  }
  if (!roles.includes(user.role)) {
    return <Navigate to="/search" replace />;
  }
  return <Outlet />;
}
