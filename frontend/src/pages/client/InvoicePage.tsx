import { useParams } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import { getReservationInvoice } from "../../api/reservations";
import { Card, EmptyState, Skeleton, Button } from "../../shared/ui";
import styles from "./InvoicePage.module.css";

export function InvoicePage() {
  const { id } = useParams();
  const query = useQuery({
    queryKey: ["invoice", id],
    queryFn: () => (id ? getReservationInvoice(id) : Promise.reject()),
    enabled: !!id,
  });

  if (query.isLoading) {
    return (
      <div className="container">
        <Skeleton height={32} />
      </div>
    );
  }

  if (query.isError) {
    return (
      <div className="container">
        <EmptyState title="Invoice unavailable" description="We could not load this invoice." />
      </div>
    );
  }

  return (
    <div className="container">
      <Card title="Invoice">
        <div className={styles.actions}>
          <Button onClick={() => window.print()}>Print</Button>
        </div>
        <div className={styles.preview}>
          {typeof query.data === "string" ? (
            <iframe title="Invoice" className={styles.iframe} srcDoc={query.data} />
          ) : (
            <pre>{JSON.stringify(query.data, null, 2)}</pre>
          )}
        </div>
      </Card>
    </div>
  );
}
