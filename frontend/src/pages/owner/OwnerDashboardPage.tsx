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
      <Card title="Mes parkings" subtitle="Gérez vos parkings">
        {query.isLoading ? <Skeleton height={24} /> : null}
        {query.isError ? (
          <EmptyState title="Chargement échoué" description="Veuillez réessayer." />
        ) : null}
        {!query.isLoading && !query.data?.items?.length ? (
          <EmptyState
            title="Aucun parking"
            description="Créez votre premier parking pour commencer."
            actionLabel="Créer un parking"
            onAction={() => navigate("/owner/parkings/new")}
          />
        ) : null}
        {query.data?.items?.length ? (
          <div className={styles.list}>
            {query.data.items.map((parking) => (
              <div key={parking.id} className={styles.item}>
                <div className={styles.details}>
                  <strong>{parking.name}</strong>
                  <span>{parking.address}</span>
                </div>
                <div className={styles.actions}>
                  <Button size="sm" variant="secondary" onClick={() => navigate(`/owner/parkings/${parking.id}/edit`)}>
                    Modifier
                  </Button>
                  <Button size="sm" variant="ghost" onClick={() => navigate(`/owner/parkings/${parking.id}/pricing`)}>
                    Tarifs
                  </Button>
                  <Button size="sm" variant="ghost" onClick={() => navigate(`/owner/parkings/${parking.id}/offers`)}>
                    Offres
                  </Button>
                  <Button size="sm" variant="ghost" onClick={() => navigate(`/owner/parkings/${parking.id}/reservations`)}>
                    Réservations
                  </Button>
                  <Button size="sm" variant="ghost" onClick={() => navigate(`/owner/parkings/${parking.id}/stationings`)}>
                    Stationnements
                  </Button>
                  <Button size="sm" variant="ghost" onClick={() => navigate(`/owner/parkings/${parking.id}/revenue`)}>
                    CA mensuel
                  </Button>
                  <Button size="sm" variant="ghost" onClick={() => navigate(`/owner/parkings/${parking.id}/overstayers`)}>
                    Hors créneaux
                  </Button>
                </div>
              </div>
            ))}
          </div>
        ) : null}
      </Card>
    </div>
  );
}
