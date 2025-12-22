import { useQuery } from "@tanstack/react-query";
import { getOwnerParkings } from "../../api/parkings";
import { Card, EmptyState, Skeleton, Button } from "../../shared/ui";
import { useNavigate } from "react-router-dom";
import styles from "./OwnerDashboardPage.module.css";

export function OwnerDashboardPage() {
  const navigate = useNavigate();
  const query = useQuery({
    queryKey: ["owner", "parkings"],
    queryFn: getOwnerParkings,
  });

  return (
    <div className={styles.wrapper}>
      <Card title="My parkings" subtitle="Manage your assets">
        {query.isLoading ? <Skeleton height={24} /> : null}
        {query.isError ? (
          <EmptyState title="Failed to load" description="Please retry." />
        ) : null}
        {!query.isLoading && !query.data?.items?.length ? (
          <EmptyState
            title="No parkings"
            description="Create your first parking to get started."
            actionLabel="Create parking"
            onAction={() => navigate("/owner/parkings/new")}
          />
        ) : null}
        {query.data?.items?.length ? (
          <div className={styles.list}>
            {query.data.items.map((parking) => (
              <div key={parking.id} className={styles.item}>
                <div>
                  <strong>{parking.name}</strong>
                  <span>{parking.address}</span>
                </div>
                <Button variant="secondary" onClick={() => navigate(`/owner/parkings/${parking.id}/edit`)}>
                  Manage
                </Button>
              </div>
            ))}
          </div>
        ) : null}
      </Card>
    </div>
  );
}
