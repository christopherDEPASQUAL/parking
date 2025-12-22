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

  return (
    <Card title="Reservations" subtitle="All reservations for this parking">
      {query.isLoading ? <Skeleton height={24} /> : null}
      {query.isError ? (
        <EmptyState title="Failed to load" description="Please retry." />
      ) : null}
      {items.length ? (
        <Table columns={["Reservation", "User", "Start", "End", "Status"]}>
          {items.map((reservation) => (
            <tr key={reservation.id}>
              <td>{reservation.id.slice(0, 8)}</td>
              <td>{reservation.user_id ?? "-"}</td>
              <td>{formatDateTime(reservation.starts_at)}</td>
              <td>{formatDateTime(reservation.ends_at)}</td>
              <td>{reservation.status}</td>
            </tr>
          ))}
        </Table>
      ) : (
        <EmptyState title="No reservations" description="No reservations yet." />
      )}
    </Card>
  );
}
