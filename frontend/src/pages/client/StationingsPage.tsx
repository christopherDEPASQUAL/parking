import { useState } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { enterStationing, exitStationing, getMyStationings } from "../../api/stationings";
import { Button, Card, EmptyState, Input, Skeleton, useToast } from "../../shared/ui";
import { formatDateTime, formatCurrency } from "../../shared/utils/format";
import styles from "./StationingsPage.module.css";

export function StationingsPage() {
  const [parkingId, setParkingId] = useState("");
  const [reservationId, setReservationId] = useState("");
  const [subscriptionId, setSubscriptionId] = useState("");
  const { notify } = useToast();

  const query = useQuery({
    queryKey: ["stationings", "me"],
    queryFn: getMyStationings,
  });

  const enterMutation = useMutation({
    mutationFn: () =>
      enterStationing({
        parking_id: parkingId,
        reservation_id: reservationId || undefined,
        subscription_id: subscriptionId || undefined,
      }),
    onSuccess: () => {
      notify({ title: "Entrée enregistrée", description: "Stationnement démarré.", variant: "success" });
      query.refetch();
    },
    onError: (error: any) => {
      notify({
        title: "Entrée échouée",
        description: error?.message || "Vérifiez votre réservation ou votre abonnement.",
        variant: "error",
      });
    },
  });

  const exitMutation = useMutation({
    mutationFn: () => exitStationing({ parking_id: parkingId }),
    onSuccess: () => {
      notify({ title: "Sortie effectuée", description: "Montant calculé.", variant: "success" });
      query.refetch();
    },
    onError: (error: any) => {
      notify({
        title: "Sortie échouée",
        description: error?.message || "Vérifiez l’identifiant du parking.",
        variant: "error",
      });
    },
  });

  return (
    <div className="container">
      <div className={styles.layout}>
        <Card title="Entrée / Sortie">
          <div className={styles.form}>
            <Input label="Identifiant du parking" value={parkingId} onChange={(e) => setParkingId(e.target.value)} />
            <Input label="Identifiant de réservation (optionnel)" value={reservationId} onChange={(e) => setReservationId(e.target.value)} />
            <Input label="Identifiant d’abonnement (optionnel)" value={subscriptionId} onChange={(e) => setSubscriptionId(e.target.value)} />
            <Button onClick={() => enterMutation.mutate()} loading={enterMutation.isPending}>
              Entrer
            </Button>
            <Button variant="secondary" onClick={() => exitMutation.mutate()} loading={exitMutation.isPending}>
              Sortir
            </Button>
          </div>
        </Card>
        <Card title="Historique">
          {query.isLoading ? <Skeleton height={24} /> : null}
          {query.isError ? (
            <EmptyState title="Impossible de charger les stationnements" description="Veuillez réessayer." />
          ) : null}
          {!query.isLoading && !query.data?.items?.length ? (
            <EmptyState title="Aucun stationnement" description="Votre historique apparaîtra ici." />
          ) : null}
          {query.data?.items?.length ? (
            <div className={styles.list}>
              {query.data.items.map((item) => (
                <div key={item.id} className={styles.item}>
                  <div>
                    <strong>{item.parking_id}</strong>
                    <span>{formatDateTime(item.entered_at)}</span>
                  </div>
                  <div>
                    <span>{item.exited_at ? formatDateTime(item.exited_at) : "En cours"}</span>
                    <strong>{formatCurrency(item.amount_cents ?? 0)}</strong>
                  </div>
                </div>
              ))}
            </div>
          ) : null}
        </Card>
      </div>
    </div>
  );
}
