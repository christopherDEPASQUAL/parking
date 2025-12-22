import { useState } from "react";
import { useParams } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import { getParkingRevenue } from "../../api/parkings";
import { Button, Card, Input, Skeleton } from "../../shared/ui";
import { formatCurrency } from "../../shared/utils/format";
import styles from "./OwnerRevenuePage.module.css";

export function OwnerRevenuePage() {
  const { id } = useParams();
  const [month, setMonth] = useState(new Date().toISOString().slice(0, 7));

  const query = useQuery({
    queryKey: ["owner", "revenue", id, month],
    queryFn: () => (id ? getParkingRevenue(id, month) : Promise.reject()),
    enabled: !!id,
  });

  return (
    <Card title="Monthly revenue" subtitle="Includes payments and penalties">
      <div className={styles.controls}>
        <Input label="Month" type="month" value={month} onChange={(e) => setMonth(e.target.value)} />
        <Button onClick={() => query.refetch()}>Refresh</Button>
      </div>
      {query.isLoading ? <Skeleton height={24} /> : null}
      {query.data ? (
        <div className={styles.value}>
          <strong>{formatCurrency(query.data.total_cents ?? 0)}</strong>
          <span>Month: {month}</span>
        </div>
      ) : null}
    </Card>
  );
}
