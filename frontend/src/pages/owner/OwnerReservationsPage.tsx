import { useParams } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import { getParkingReservations } from "../../api/parkings";
import { Card, EmptyState, Skeleton, Table } from "../../shared/ui";
import { formatDateTime } from "../../shared/utils/format";

export function OwnerReservationsPage() {
  const { id } = useParams();
  const query = useQuery({
    queryKey: ["owner", "reservations", id],
    queryFn: () => (id ? getParkingReservations(id) : Promise.reject()),
    enabled: !!id,
  });
  const items = query.data?.items ?? [];
  const statusLabel = (value?: string | null) => {
    if (!value) {
      return "-";
    }
    const normalized = value.toLowerCase();
    const labels: Record<string, string> = {
      pending_payment: "paiement en attente",
      pending: "en attente",
      confirmed: "confirmée",
      cancelled: "annulée",
      completed: "terminée",
      payment_failed: "paiement échoué",
    };
    return labels[normalized] ?? value;
  };

  return (
    <Card title="Réservations" subtitle="Toutes les réservations de ce parking">
      {query.isLoading ? <Skeleton height={24} /> : null}
      {query.isError ? (
        <EmptyState title="Chargement échoué" description="Veuillez réessayer." />
      ) : null}
      {items.length ? (
        <Table columns={["Réservation", "Utilisateur", "Début", "Fin", "Statut"]}>
          {items.map((reservation) => (
            <tr key={reservation.id}>
              <td>{reservation.id.slice(0, 8)}</td>
              <td>{reservation.user_id ?? "-"}</td>
              <td>{formatDateTime(reservation.starts_at)}</td>
              <td>{formatDateTime(reservation.ends_at)}</td>
              <td>{statusLabel(reservation.status)}</td>
            </tr>
          ))}
        </Table>
      ) : (
        <EmptyState title="Aucune réservation" description="Aucune réservation pour le moment." />
      )}
    </Card>
  );
}
