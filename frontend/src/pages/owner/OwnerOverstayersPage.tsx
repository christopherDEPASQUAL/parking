import { useState } from "react";
import { useParams } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import { getParkingOverstayers } from "../../api/parkings";
import { Card, EmptyState, Input, Skeleton, Table, Button } from "../../shared/ui";
import { formatDateTime, toLocalMonthInputValue } from "../../shared/utils/format";
import styles from "./OwnerOverstayersPage.module.css";

export function OwnerOverstayersPage() {
  const { id } = useParams();
  const [month, setMonth] = useState(toLocalMonthInputValue(new Date()));

  const query = useQuery({
    queryKey: ["owner", "overstayers", id, month],
    queryFn: () => (id ? getParkingOverstayers(id, month) : Promise.reject()),
    enabled: !!id,
  });

  const items = query.data?.items ?? [];

  return (
    <Card title="Hors créneaux" subtitle="Conducteurs garés hors réservation ou abonnement">
      <div className={styles.controls}>
        <Input label="Mois" type="month" value={month} onChange={(e) => setMonth(e.target.value)} />
        <Button onClick={() => query.refetch()}>Rafraîchir</Button>
      </div>
      {query.isLoading ? <Skeleton height={24} /> : null}
      {query.isError ? (
        <EmptyState title="Chargement échoué" description="Veuillez réessayer." />
      ) : null}
      {items.length ? (
        <Table columns={["Session", "Utilisateur", "Réservation", "Abonnement", "Début"]}>
          {items.map((item) => (
            <tr key={item.session_id}>
              <td>{item.session_id.slice(0, 8)}</td>
              <td>{item.user_id.slice(0, 8)}</td>
              <td>{item.reservation_id ? item.reservation_id.slice(0, 8) : "-"}</td>
              <td>{item.abonnement_id ? item.abonnement_id.slice(0, 8) : "-"}</td>
              <td>{formatDateTime(item.started_at)}</td>
            </tr>
          ))}
        </Table>
      ) : (
        <EmptyState title="Aucun hors créneau" description="Aucun conducteur hors créneau pour cette période." />
      )}
    </Card>
  );
}
