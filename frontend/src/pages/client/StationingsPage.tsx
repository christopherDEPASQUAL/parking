import { useState } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { enterStationing, exitStationing, getMyStationings } from "../../api/stationings";
import { Button, Card, EmptyState, Input, Skeleton } from "../../shared/ui";
import { formatDateTime, formatCurrency } from "../../shared/utils/format";
import styles from "./StationingsPage.module.css";

export function StationingsPage() {
  const [parkingId, setParkingId] = useState("");
  const [reservationId, setReservationId] = useState("");
  const [subscriptionId, setSubscriptionId] = useState("");

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
    onSuccess: () => query.refetch(),
  });

  const exitMutation = useMutation({
    mutationFn: () => exitStationing({ parking_id: parkingId }),
    onSuccess: () => query.refetch(),
  });

  return (
    <div className="container">
      <div className={styles.layout}>
        <Card title="Entry / Exit">
          <div className={styles.form}>
            <Input label="Parking id" value={parkingId} onChange={(e) => setParkingId(e.target.value)} />
            <Input label="Reservation id (optional)" value={reservationId} onChange={(e) => setReservationId(e.target.value)} />
            <Input label="Subscription id (optional)" value={subscriptionId} onChange={(e) => setSubscriptionId(e.target.value)} />
            <Button onClick={() => enterMutation.mutate()} loading={enterMutation.isPending}>
              Enter parking
            </Button>
            <Button variant="secondary" onClick={() => exitMutation.mutate()} loading={exitMutation.isPending}>
              Exit parking
            </Button>
          </div>
        </Card>
        <Card title="History">
          {query.isLoading ? <Skeleton height={24} /> : null}
          {query.isError ? (
            <EmptyState title="Failed to load stationings" description="Please retry." />
          ) : null}
          {!query.isLoading && !query.data?.items?.length ? (
            <EmptyState title="No stationings" description="Your stationing history will appear here." />
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
                    <span>{item.exited_at ? formatDateTime(item.exited_at) : "Active"}</span>
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
