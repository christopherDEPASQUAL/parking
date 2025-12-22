import { NavLink, Outlet } from "react-router-dom";
import styles from "./OwnerLayout.module.css";

export function OwnerLayout() {
  return (
    <div className={styles.wrapper}>
      <aside className={styles.sidebar}>
        <h3>Owner</h3>
        <nav>
          <NavLink to="/owner/dashboard">Dashboard</NavLink>
          <NavLink to="/owner/parkings/new">New parking</NavLink>
        </nav>
      </aside>
      <section className={styles.content}>
        <Outlet />
      </section>
    </div>
  );
}
