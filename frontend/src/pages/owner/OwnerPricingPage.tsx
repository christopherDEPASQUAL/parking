import { useEffect, useMemo, useState } from "react";
import { useParams } from "react-router-dom";
import { useMutation, useQuery } from "@tanstack/react-query";
import { getPricingPlan, updatePricingPlan } from "../../api/parkings";
import { Button, Card, EmptyState, Input, useToast } from "../../shared/ui";
import { pricingPlanSchema } from "../../entities/parking";
import styles from "./OwnerPricingPage.module.css";

const subscriptionTypes = ["full", "weekend", "evening", "custom"] as const;
const subscriptionTypeLabel: Record<string, string> = {
  full: "Complet",
  weekend: "Week-end",
  evening: "Soir",
  custom: "Spécifique",
};

export function OwnerPricingPage() {
  const { id } = useParams();
  const { notify } = useToast();
  const [error, setError] = useState<string | null>(null);
  const [plan, setPlan] = useState({
    stepMinutes: 15,
    tiers: [] as Array<{ upToMinutes: number; pricePerStepCents: number }>,
    defaultPricePerStepCents: 0,
    overstayPenaltyCents: 2000,
    subscriptionPrices: {} as Record<string, number>,
  });

  const query = useQuery({
    queryKey: ["pricing", id],
    queryFn: () => (id ? getPricingPlan(id) : Promise.reject()),
    enabled: !!id,
  });

  useEffect(() => {
    if (query.data) {
      setPlan({
        stepMinutes: query.data.stepMinutes ?? 15,
        tiers: Array.isArray(query.data.tiers) ? query.data.tiers : [],
        defaultPricePerStepCents: query.data.defaultPricePerStepCents ?? 0,
        overstayPenaltyCents: query.data.overstayPenaltyCents ?? 2000,
        subscriptionPrices:
          query.data.subscriptionPrices && typeof query.data.subscriptionPrices === "object"
            ? query.data.subscriptionPrices
            : {},
      });
    }
  }, [query.data]);

  const parsed = useMemo(() => {
    const result = pricingPlanSchema.safeParse(plan);
    return {
      value: result.success ? result.data : null,
      error: result.success ? null : result.error.issues[0]?.message ?? "Plan tarifaire invalide",
    };
  }, [plan]);

  useEffect(() => {
    setError(parsed.error);
  }, [parsed.error]);

  const mutation = useMutation({
    mutationFn: () => (id && parsed.value ? updatePricingPlan(id, parsed.value) : Promise.reject()),
    onSuccess: () => {
      notify({ title: "Tarifs mis à jour", description: "Plan tarifaire enregistré.", variant: "success" });
    },
    onError: (err: any) => {
      notify({
        title: "Mise à jour échouée",
        description: err?.message || "Veuillez vérifier le formulaire et réessayer.",
        variant: "error",
      });
    },
  });

  const updateTier = (index: number, key: "upToMinutes" | "pricePerStepCents", value: string) => {
    const numeric = Number(value);
    setPlan((prev) => {
      const next = [...prev.tiers];
      next[index] = {
        ...next[index],
        [key]: Number.isFinite(numeric) ? Math.max(0, Math.floor(numeric)) : 0,
      };
      return { ...prev, tiers: next };
    });
  };

  const addTier = () => {
    setPlan((prev) => ({
      ...prev,
      tiers: [...prev.tiers, { upToMinutes: 60, pricePerStepCents: prev.defaultPricePerStepCents }],
    }));
  };

  const removeTier = (index: number) => {
    setPlan((prev) => ({ ...prev, tiers: prev.tiers.filter((_, idx) => idx !== index) }));
  };

  const updateSubscriptionPrice = (type: string, value: string) => {
    setPlan((prev) => {
      const next = { ...prev.subscriptionPrices };
      if (value === "") {
        delete next[type];
      } else {
        const numeric = Math.max(0, Math.floor(Number(value)));
        next[type] = Number.isFinite(numeric) ? numeric : 0;
      }
      return { ...prev, subscriptionPrices: next };
    });
  };

  return (
    <Card title="Plan tarifaire" subtitle="Source de vérité : plan_json">
      <div className={styles.layout}>
        <div className={styles.column}>
          <Input label="Pas (minutes)" value={String(plan.stepMinutes)} disabled />
          <Input
            label="Prix par défaut par pas (centimes)"
            type="number"
            min="0"
            value={String(plan.defaultPricePerStepCents)}
            onChange={(event) =>
              setPlan((prev) => ({
                ...prev,
                defaultPricePerStepCents: Math.max(0, Math.floor(Number(event.target.value || 0))),
              }))
            }
          />
          <Input
            label="Pénalité de dépassement (centimes)"
            type="number"
            min="0"
            value={String(plan.overstayPenaltyCents)}
            onChange={(event) =>
              setPlan((prev) => ({
                ...prev,
                overstayPenaltyCents: Math.max(0, Math.floor(Number(event.target.value || 0))),
              }))
            }
          />
          <div className={styles.section}>
            <h4>Prix des abonnements (optionnel)</h4>
            <div className={styles.grid}>
              {subscriptionTypes.map((type) => (
                <Input
                  key={type}
                  label={`Prix ${subscriptionTypeLabel[type] ?? type} (centimes)`}
                  type="number"
                  min="0"
                  value={plan.subscriptionPrices[type] !== undefined ? String(plan.subscriptionPrices[type]) : ""}
                  onChange={(event) => updateSubscriptionPrice(type, event.target.value)}
                />
              ))}
            </div>
          </div>
        </div>
        <div className={styles.column}>
          <div className={styles.tiersHeader}>
            <h4>Paliers tarifaires</h4>
            <Button variant="secondary" size="sm" onClick={addTier}>
              Ajouter un palier
            </Button>
          </div>
          {plan.tiers.length ? (
            <div className={styles.tiersList}>
              {plan.tiers.map((tier, index) => (
                <div key={index} className={styles.tierRow}>
                  <Input
                    label="Jusqu’à (minutes)"
                    type="number"
                    min="1"
                    value={String(tier.upToMinutes)}
                    onChange={(event) => updateTier(index, "upToMinutes", event.target.value)}
                  />
                  <Input
                    label="Prix par pas (centimes)"
                    type="number"
                    min="0"
                    value={String(tier.pricePerStepCents)}
                    onChange={(event) => updateTier(index, "pricePerStepCents", event.target.value)}
                  />
                  <Button variant="ghost" size="sm" onClick={() => removeTier(index)}>
                    Supprimer
                  </Button>
                </div>
              ))}
            </div>
          ) : (
            <EmptyState
              title="Aucun palier"
              description="Ajoutez des paliers pour une tarification progressive."
              actionLabel="Ajouter un palier"
              onAction={addTier}
            />
          )}
        </div>
      </div>
      {error ? <div className={styles.error}>{error}</div> : null}
      <Button
        onClick={() => mutation.mutate()}
        loading={mutation.isPending}
        disabled={!parsed.value}
      >
        Enregistrer le plan tarifaire
      </Button>
    </Card>
  );
}
