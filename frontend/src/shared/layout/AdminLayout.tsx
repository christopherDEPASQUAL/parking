import { NavLink, Outlet } from "react-router-dom";
import styles from "./AdminLayout.module.css";

export function AdminLayout() {
  return (
    <div className={styles.wrapper}>
      <aside className={styles.sidebar}>
        <h3>Admin</h3>
        <nav>
          <NavLink to="/admin/users">Utilisateurs</NavLink>
          <NavLink to="/admin/parkings">Parkings</NavLink>
        </nav>
      </aside>
      <section className={styles.content}>
        <Outlet />
      </section>
    </div>
  );
}
