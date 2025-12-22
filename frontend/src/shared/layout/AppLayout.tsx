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
      <a className={styles.skipLink} href="#main-content">Aller au contenu</a>
      <header className={styles.header}>
        <NavLink to="/" className={styles.brand} aria-label="Accueil">
          <img className={styles.logo} src="/ParkingLogo.png" alt="Parking" />
        </NavLink>
        <nav className={styles.nav} aria-label="Navigation principale">
          {user ? (
            <>
              <NavLink to="/search">Recherche</NavLink>
              <NavLink to="/reservations">Réservations</NavLink>
              <NavLink to="/stationings">Stationnements</NavLink>
              {user.role === "proprietor" ? <NavLink to="/owner/dashboard">Propriétaire</NavLink> : null}
              {user.role === "admin" ? <NavLink to="/admin/users">Admin</NavLink> : null}
            </>
          ) : null}
        </nav>
        <div className={styles.actions}>
          {user ? (
            <>
              <span className={styles.userTag}>{user.email}</span>
              <Button variant="ghost" onClick={logout}>
                Déconnexion
              </Button>
            </>
          ) : (
            <>
              <NavLink to="/login">Connexion</NavLink>
              <NavLink to="/register">Inscription</NavLink>
            </>
          )}
        </div>
      </header>
      <main id="main-content" className={styles.main}>{children ?? <Outlet />}</main>
    </div>
  );
}
