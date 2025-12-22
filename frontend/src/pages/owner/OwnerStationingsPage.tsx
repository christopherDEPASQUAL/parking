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

  return (
    <Card title="Stationings" subtitle="Active and historical stationings">
      {query.isLoading ? <Skeleton height={24} /> : null}
      {query.isError ? (
        <EmptyState title="Failed to load" description="Please retry." />
      ) : null}
      {items.length ? (
        <Table columns={["Session", "User", "Entered", "Exited", "Status"]}>
          {items.map((session) => (
            <tr key={session.id}>
              <td>{session.id.slice(0, 8)}</td>
              <td>{session.user_id ?? "-"}</td>
              <td>{formatDateTime(session.entered_at)}</td>
              <td>{session.exited_at ? formatDateTime(session.exited_at) : "Active"}</td>
              <td>{session.status ?? "-"}</td>
            </tr>
          ))}
        </Table>
      ) : (
        <EmptyState title="No stationings" description="No stationings yet." />
      )}
    </Card>
  );
}
