import { useEffect, useMemo, useState } from "react";
import { useParams } from "react-router-dom";
import { useMutation, useQuery } from "@tanstack/react-query";
import { getPricingPlan, updatePricingPlan } from "../../api/parkings";
import { Button, Card, Textarea } from "../../shared/ui";
import { pricingPlanSchema } from "../../entities/parking";
import styles from "./OwnerPricingPage.module.css";

export function OwnerPricingPage() {
  const { id } = useParams();
  const [raw, setRaw] = useState("");
  const [error, setError] = useState<string | null>(null);

  const query = useQuery({
    queryKey: ["pricing", id],
    queryFn: () => (id ? getPricingPlan(id) : Promise.reject()),
    enabled: !!id,
  });

  useEffect(() => {
    if (query.data) {
      setRaw(JSON.stringify(query.data, null, 2));
    }
  }, [query.data]);

  const parsed = useMemo(() => {
    try {
      const value = JSON.parse(raw || "{}");
      pricingPlanSchema.parse(value);
      return { value, error: null };
    } catch (err) {
      return { value: null, error: err instanceof Error ? err.message : "Invalid JSON" };
    }
  }, [raw]);

  useEffect(() => {
    setError(parsed.error);
  }, [parsed.error]);

  const mutation = useMutation({
    mutationFn: () => (id && parsed.value ? updatePricingPlan(id, parsed.value) : Promise.reject()),
  });

  return (
    <Card title="Pricing plan" subtitle="Edit plan_json as source of truth">
      <div className={styles.layout}>
        <Textarea
          label="Pricing plan JSON"
          value={raw}
          onChange={(event) => setRaw(event.target.value)}
          helperText="Use a JSON structure matching the pricing plan schema."
          error={error ?? undefined}
        />
        <div className={styles.preview}>
          <h4>Preview</h4>
          <pre>{parsed.value ? JSON.stringify(parsed.value, null, 2) : "Invalid JSON"}</pre>
        </div>
      </div>
      <Button
        onClick={() => mutation.mutate()}
        loading={mutation.isPending}
        disabled={!parsed.value}
      >
        Save pricing plan
      </Button>
    </Card>
  );
}
