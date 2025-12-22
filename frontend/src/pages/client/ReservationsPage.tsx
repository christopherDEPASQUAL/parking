import { useQuery } from "@tanstack/react-query";
import { getMyReservations } from "../../api/reservations";
import { Badge, Card, EmptyState, Skeleton, Table } from "../../shared/ui";
import { formatDateTime, formatCurrency } from "../../shared/utils/format";
import type { ReservationStatus } from "../../entities/reservation";
import styles from "./ReservationsPage.module.css";
import { useNavigate } from "react-router-dom";

const statusVariant: Record<ReservationStatus, "neutral" | "success" | "warning" | "error" | "info"> = {
  pending_payment: "warning",
  pending: "info",
  confirmed: "success",
  cancelled: "error",
  completed: "neutral",
  payment_failed: "error",
};

const statusLabel: Record<ReservationStatus, string> = {
  pending_payment: "paiement en attente",
  pending: "en attente",
  confirmed: "confirmée",
  cancelled: "annulée",
  completed: "terminée",
  payment_failed: "paiement échoué",
};

export function ReservationsPage() {
  const navigate = useNavigate();
  const query = useQuery({
    queryKey: ["reservations", "me"],
    queryFn: getMyReservations,
  });

  if (query.isLoading) {
    return (
      <div className="container">
        <Skeleton height={32} />
      </div>
    );
  }

  if (query.isError) {
    return (
      <div className="container">
        <EmptyState title="Impossible de charger les réservations" description="Veuillez réessayer." />
      </div>
    );
  }

  const items = query.data?.items ?? [];

  if (!items.length) {
    return (
      <div className="container">
        <EmptyState title="Aucune réservation" description="Commencez par rechercher un parking." />
      </div>
    );
  }

  return (
    <div className="container">
      <div className={styles.mobileCards}>
        {items.map((reservation) => (
          <Card key={reservation.id}>
            <div className={styles.cardHeader}>
              <h4>Réservation {reservation.id.slice(0, 8)}</h4>
              <Badge label={statusLabel[reservation.status]} variant={statusVariant[reservation.status]} />
            </div>
            <p>{formatDateTime(reservation.starts_at)} - {formatDateTime(reservation.ends_at)}</p>
            <strong>{formatCurrency(reservation.price_cents ?? 0)}</strong>
            <button
              type="button"
              className={styles.linkButton}
              onClick={() => navigate(`/reservations/${reservation.id}`)}
            >
              Voir les détails
            </button>
          </Card>
        ))}
      </div>
      <div className={styles.tableWrapper}>
        <Table columns={["Réservation", "Statut", "Début", "Fin", "Prix"]}>
          {items.map((reservation) => (
            <tr
              key={reservation.id}
              onClick={() => navigate(`/reservations/${reservation.id}`)}
              onKeyDown={(event) => {
                if (event.key === "Enter") {
                  navigate(`/reservations/${reservation.id}`);
                }
              }}
              tabIndex={0}
            >
              <td>{reservation.id.slice(0, 8)}</td>
              <td><Badge label={statusLabel[reservation.status]} variant={statusVariant[reservation.status]} /></td>
              <td>{formatDateTime(reservation.starts_at)}</td>
              <td>{formatDateTime(reservation.ends_at)}</td>
              <td>{formatCurrency(reservation.price_cents ?? 0)}</td>
            </tr>
          ))}
        </Table>
      </div>
    </div>
  );
}
