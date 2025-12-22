import { useState } from "react";
import { useParams } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import { getParkingRevenue } from "../../api/parkings";
import { Button, Card, Input, Skeleton } from "../../shared/ui";
import { formatCurrency, toLocalMonthInputValue } from "../../shared/utils/format";
import styles from "./OwnerRevenuePage.module.css";

export function OwnerRevenuePage() {
  const { id } = useParams();
  const [month, setMonth] = useState(toLocalMonthInputValue(new Date()));

  const query = useQuery({
    queryKey: ["owner", "revenue", id, month],
    queryFn: () => (id ? getParkingRevenue(id, month) : Promise.reject()),
    enabled: !!id,
  });

  const totalCents = query.data?.total_cents ?? query.data?.amount_cents ?? 0;

  return (
    <Card title="Chiffre d'affaires mensuel" subtitle="Inclut paiements et penalites">
      <div className={styles.controls}>
        <Input label="Mois" type="month" value={month} onChange={(e) => setMonth(e.target.value)} />
        <Button onClick={() => query.refetch()}>Rafraichir</Button>
      </div>
      {query.isLoading ? <Skeleton height={24} /> : null}
      {query.data ? (
        <div className={styles.value}>
          <strong>{formatCurrency(totalCents)}</strong>
          <span>Mois : {month}</span>
        </div>
      ) : null}
    </Card>
  );
}
