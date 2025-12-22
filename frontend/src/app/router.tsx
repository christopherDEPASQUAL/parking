import { createBrowserRouter, Navigate } from "react-router-dom";
import { AppLayout } from "../shared/layout";
import { ProtectedRoute, RoleGuard } from "./routeGuards";
import { LoginPage } from "../pages/auth/LoginPage";
import { RegisterPage } from "../pages/auth/RegisterPage";
import { ProfilePage } from "../pages/auth/ProfilePage";
import { LogoutPage } from "../pages/auth/LogoutPage";
import { SearchPage } from "../pages/client/SearchPage";
import { ParkingDetailsPage } from "../pages/client/ParkingDetailsPage";
import { ReservationsPage } from "../pages/client/ReservationsPage";
import { ReservationDetailsPage } from "../pages/client/ReservationDetailsPage";
import { StationingsPage } from "../pages/client/StationingsPage";
import { InvoicePage } from "../pages/client/InvoicePage";
import { OwnerLayout, AdminLayout } from "../shared/layout";
import { OwnerDashboardPage } from "../pages/owner/OwnerDashboardPage";
import { OwnerParkingFormPage } from "../pages/owner/OwnerParkingFormPage";
import { OwnerPricingPage } from "../pages/owner/OwnerPricingPage";
import { OwnerOffersPage } from "../pages/owner/OwnerOffersPage";
import { OwnerReservationsPage } from "../pages/owner/OwnerReservationsPage";
import { OwnerStationingsPage } from "../pages/owner/OwnerStationingsPage";
import { OwnerRevenuePage } from "../pages/owner/OwnerRevenuePage";
import { AdminUsersPage } from "../pages/admin/AdminUsersPage";
import { AdminParkingsPage } from "../pages/admin/AdminParkingsPage";

export const router = createBrowserRouter([
  {
    element: <AppLayout />,
    children: [
      { path: "/", element: <Navigate to="/search" replace /> },
      { path: "/login", element: <LoginPage /> },
      { path: "/register", element: <RegisterPage /> },
      { path: "/logout", element: <LogoutPage /> },
      {
        element: <ProtectedRoute />,
        children: [
          { path: "/search", element: <SearchPage /> },
          { path: "/parkings/:id", element: <ParkingDetailsPage /> },
          { path: "/reservations", element: <ReservationsPage /> },
          { path: "/reservations/:id", element: <ReservationDetailsPage /> },
          { path: "/stationings", element: <StationingsPage /> },
          { path: "/invoices/reservations/:id", element: <InvoicePage /> },
          { path: "/me", element: <ProfilePage /> },
          {
            element: <RoleGuard roles={["proprietor"]} />,
            children: [
              {
                element: <OwnerLayout />,
                children: [
                  { path: "/owner/dashboard", element: <OwnerDashboardPage /> },
                  { path: "/owner/parkings/new", element: <OwnerParkingFormPage mode="create" /> },
                  { path: "/owner/parkings/:id/edit", element: <OwnerParkingFormPage mode="edit" /> },
                  { path: "/owner/parkings/:id/pricing", element: <OwnerPricingPage /> },
                  { path: "/owner/parkings/:id/offers", element: <OwnerOffersPage /> },
                  { path: "/owner/parkings/:id/reservations", element: <OwnerReservationsPage /> },
                  { path: "/owner/parkings/:id/stationings", element: <OwnerStationingsPage /> },
                  { path: "/owner/parkings/:id/revenue", element: <OwnerRevenuePage /> },
                ],
              },
            ],
          },
          {
            element: <RoleGuard roles={["admin"]} />,
            children: [
              {
                element: <AdminLayout />,
                children: [
                  { path: "/admin/users", element: <AdminUsersPage /> },
                  { path: "/admin/parkings", element: <AdminParkingsPage /> },
                ],
              },
            ],
          },
        ],
      },
    ],
  },
]);
