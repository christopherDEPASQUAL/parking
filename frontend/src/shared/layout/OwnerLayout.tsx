import { NavLink, Outlet } from "react-router-dom";
import styles from "./OwnerLayout.module.css";

export function OwnerLayout() {
  return (
    <div className={styles.wrapper}>
      <aside className={styles.sidebar}>
        <h3>Propriétaire</h3>
        <nav>
          <NavLink to="/owner/dashboard">Tableau de bord</NavLink>
          <NavLink to="/owner/parkings/new">Nouveau parking</NavLink>
        </nav>
      </aside>
      <section className={styles.content}>
        <Outlet />
      </section>
    </div>
  );
}
