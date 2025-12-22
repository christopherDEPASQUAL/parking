import { useAuth } from "../../shared/providers/AuthProvider";
import { Card } from "../../shared/ui";
import styles from "./ProfilePage.module.css";

export function ProfilePage() {
  const { user } = useAuth();

  if (!user) {
    return null;
  }

  return (
    <div className={styles.wrapper}>
      <Card title="My profile">
        <div className={styles.grid}>
          <div>
            <span className={styles.label}>Email</span>
            <strong>{user.email}</strong>
          </div>
          <div>
            <span className={styles.label}>Role</span>
            <strong>{user.role}</strong>
          </div>
        </div>
      </Card>
    </div>
  );
}
