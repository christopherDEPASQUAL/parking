import React from "react";
import { NavLink, Outlet } from "react-router-dom";
import styles from "./AppLayout.module.css";
import { Button } from "../ui/Button";
import { useAuth } from "../providers/AuthProvider";

interface AppLayoutProps {
  children?: React.ReactNode;
}

export function AppLayout({ children }: AppLayoutProps) {
  const { user, logout } = useAuth();

  return (
    <div className={styles.wrapper}>
      <a className={styles.skipLink} href="#main-content">Skip to content</a>
      <header className={styles.header}>
        <div className={styles.brand}>Parking</div>
        <nav className={styles.nav} aria-label="Primary">
          <NavLink to="/search">Search</NavLink>
          <NavLink to="/reservations">Reservations</NavLink>
          <NavLink to="/stationings">Stationings</NavLink>
          {user?.role === "proprietor" ? <NavLink to="/owner/dashboard">Owner</NavLink> : null}
          {user?.role === "admin" ? <NavLink to="/admin/users">Admin</NavLink> : null}
        </nav>
        <div className={styles.actions}>
          {user ? (
            <>
              <span className={styles.userTag}>{user.email}</span>
              <Button variant="ghost" onClick={logout}>
                Logout
              </Button>
            </>
          ) : (
            <NavLink to="/login">Login</NavLink>
          )}
        </div>
      </header>
      <main id="main-content" className={styles.main}>{children ?? <Outlet />}</main>
    </div>
  );
}
