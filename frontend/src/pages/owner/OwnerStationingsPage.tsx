import { useParams } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import { getParkingStationings } from "../../api/parkings";
import { Card, EmptyState, Skeleton, Table } from "../../shared/ui";
import { formatDateTime } from "../../shared/utils/format";

export function OwnerStationingsPage() {
  const { id } = useParams();
  const query = useQuery({
    queryKey: ["owner", "stationings", id],
    queryFn: () => (id ? getParkingStationings(id) : Promise.reject()),
    enabled: !!id,
  });
  const items = query.data?.items ?? [];
  const statusLabel = (value?: string | null) => {
    if (!value) {
      return "-";
    }
    const normalized = value.toLowerCase();
    const labels: Record<string, string> = {
      active: "en cours",
      completed: "terminé",
      pending: "en attente",
      cancelled: "annulé",
    };
    return labels[normalized] ?? value;
  };

  return (
    <Card title="Stationnements" subtitle="Stationnements actifs et historiques">
      {query.isLoading ? <Skeleton height={24} /> : null}
      {query.isError ? (
        <EmptyState title="Chargement échoué" description="Veuillez réessayer." />
      ) : null}
      {items.length ? (
        <Table columns={["Session", "Utilisateur", "Entrée", "Sortie", "Statut"]}>
          {items.map((session) => (
            <tr key={session.id}>
              <td>{session.id.slice(0, 8)}</td>
              <td>{session.user_id ?? "-"}</td>
              <td>{formatDateTime(session.entered_at)}</td>
              <td>{session.exited_at ? formatDateTime(session.exited_at) : "En cours"}</td>
              <td>{statusLabel(session.status)}</td>
            </tr>
          ))}
        </Table>
      ) : (
        <EmptyState title="Aucun stationnement" description="Aucun stationnement pour le moment." />
      )}
    </Card>
  );
}
