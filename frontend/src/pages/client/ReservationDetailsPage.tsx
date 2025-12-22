import { useState } from "react";
import { useParams, useNavigate } from "react-router-dom";
import { useMutation, useQuery } from "@tanstack/react-query";
import { cancelReservation, getReservation } from "../../api/reservations";
import { Badge, Button, Card, ConfirmDialog, EmptyState, Skeleton } from "../../shared/ui";
import { formatDateTime, formatCurrency } from "../../shared/utils/format";
import type { ReservationStatus } from "../../entities/reservation";
import styles from "./ReservationDetailsPage.module.css";

const statusVariant: Record<ReservationStatus, "neutral" | "success" | "warning" | "error" | "info"> = {
  pending_payment: "warning",
  pending: "info",
  confirmed: "success",
  cancelled: "error",
  completed: "neutral",
  payment_failed: "error",
};

export function ReservationDetailsPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [confirmOpen, setConfirmOpen] = useState(false);

  const query = useQuery({
    queryKey: ["reservation", id],
    queryFn: () => (id ? getReservation(id) : Promise.reject()),
    enabled: !!id,
  });

  const cancelMutation = useMutation({
    mutationFn: () => (id ? cancelReservation(id) : Promise.reject()),
    onSuccess: () => query.refetch(),
  });

  if (query.isLoading) {
    return (
      <div className="container">
        <Skeleton height={24} />
      </div>
    );
  }

  if (query.isError || !query.data) {
    return (
      <div className="container">
        <EmptyState title="Reservation not found" description="We could not load this reservation." />
      </div>
    );
  }

  const reservation = query.data;

  return (
    <div className="container">
      <Card title={`Reservation ${reservation.id.slice(0, 8)}`}>
        <div className={styles.row}>
          <Badge label={reservation.status} variant={statusVariant[reservation.status]} />
          <strong>{formatCurrency(reservation.price_cents ?? 0)}</strong>
        </div>
        <div className={styles.grid}>
          <div>
            <span>Start</span>
            <strong>{formatDateTime(reservation.starts_at)}</strong>
          </div>
          <div>
            <span>End</span>
            <strong>{formatDateTime(reservation.ends_at)}</strong>
          </div>
        </div>
        <div className={styles.actions}>
          <Button variant="ghost" onClick={() => navigate(`/invoices/reservations/${reservation.id}`)}>
            View invoice
          </Button>
          {reservation.status !== "cancelled" ? (
            <Button variant="destructive" onClick={() => setConfirmOpen(true)}>
              Cancel reservation
            </Button>
          ) : null}
        </div>
      </Card>
      <ConfirmDialog
        isOpen={confirmOpen}
        title="Cancel reservation"
        description="This action cannot be undone. Do you want to continue?"
        onConfirm={() => {
          setConfirmOpen(false);
          cancelMutation.mutate();
        }}
        onCancel={() => setConfirmOpen(false)}
      />
    </div>
  );
}
