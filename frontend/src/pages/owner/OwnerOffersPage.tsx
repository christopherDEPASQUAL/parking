import { useParams } from "react-router-dom";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation, useQuery } from "@tanstack/react-query";
import { getParkingOffers, createOffer } from "../../api/offers";
import { Button, Card, EmptyState, Input, Select, Badge, useToast } from "../../shared/ui";
import styles from "./OwnerOffersPage.module.css";

const schema = z.object({
  label: z.string().min(1),
  type: z.enum(["full", "weekend", "evening", "custom"]),
  price_cents: z.number().int().nonnegative(),
  status: z.enum(["active", "inactive"]),
  start_day: z.number().int().min(0).max(6),
  end_day: z.number().int().min(0).max(6),
  start_time: z.string().min(1),
  end_time: z.string().min(1),
});

type FormValues = z.infer<typeof schema>;

const offerTypeLabel: Record<string, string> = {
  full: "Complet",
  weekend: "Week-end",
  evening: "Soir",
  custom: "Spécifique",
};
const offerStatusLabel: Record<string, string> = {
  active: "actif",
  inactive: "inactif",
};

export function OwnerOffersPage() {
  const { id } = useParams();
  const { notify } = useToast();
  const query = useQuery({
    queryKey: ["offers", id],
    queryFn: () => (id ? getParkingOffers(id) : Promise.resolve({ items: [] })),
    enabled: !!id,
  });

  const form = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { status: "active", type: "custom", start_day: 0, end_day: 0 },
  });

  const mutation = useMutation({
    mutationFn: (payload: FormValues) =>
      id
        ? createOffer(id, {
            label: payload.label,
            type: payload.type,
            price_cents: payload.price_cents,
            status: payload.status,
            weekly_time_slots: [
              {
                start_day: payload.start_day,
                end_day: payload.end_day,
                start_time: payload.start_time,
                end_time: payload.end_time,
              },
            ],
          })
        : Promise.reject(),
    onSuccess: () => {
      notify({ title: "Offre créée", description: "L’offre d’abonnement est disponible.", variant: "success" });
      query.refetch();
    },
    onError: (error: any) => {
      notify({
        title: "Création échouée",
        description: error?.message || "Veuillez vérifier le formulaire et réessayer.",
        variant: "error",
      });
    },
  });

  return (
    <div className={styles.layout}>
      <Card title="Offres d’abonnement">
        {query.data?.items?.length ? (
          <div className={styles.list}>
            {query.data.items.map((offer) => (
              <div key={offer.offer_id} className={styles.item}>
                <div>
                  <strong>{offer.label}</strong>
                  <span>{offerTypeLabel[offer.type] ?? offer.type}</span>
                  <span>{offer.weekly_time_slots.length} créneaux</span>
                </div>
                <Badge
                  label={offerStatusLabel[offer.status] ?? offer.status}
                  variant={offer.status === "active" ? "success" : "neutral"}
                />
              </div>
            ))}
          </div>
        ) : (
          <EmptyState title="Aucune offre" description="Créez la première offre d’abonnement." />
        )}
      </Card>
      <Card title="Créer une offre">
        <form className={styles.form} onSubmit={form.handleSubmit((data) => mutation.mutate(data))}>
          <Input label="Libellé" error={form.formState.errors.label?.message} {...form.register("label")} />
          <Select label="Type" error={form.formState.errors.type?.message} {...form.register("type")}>
            <option value="custom">Spécifique</option>
            <option value="full">Complet</option>
            <option value="evening">Soir</option>
            <option value="weekend">Week-end</option>
          </Select>
          <Input
            label="Prix (centimes)"
            type="number"
            error={form.formState.errors.price_cents?.message}
            {...form.register("price_cents", { valueAsNumber: true })}
          />
          <Select label="Statut" error={form.formState.errors.status?.message} {...form.register("status")}>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </Select>
          <div className={styles.slotGrid}>
            <Select
              label="Jour début"
              error={form.formState.errors.start_day?.message}
              {...form.register("start_day", { valueAsNumber: true })}
            >
              <option value={0}>Dim</option>
              <option value={1}>Lun</option>
              <option value={2}>Mar</option>
              <option value={3}>Mer</option>
              <option value={4}>Jeu</option>
              <option value={5}>Ven</option>
              <option value={6}>Sam</option>
            </Select>
            <Select
              label="Jour fin"
              error={form.formState.errors.end_day?.message}
              {...form.register("end_day", { valueAsNumber: true })}
            >
              <option value={0}>Dim</option>
              <option value={1}>Lun</option>
              <option value={2}>Mar</option>
              <option value={3}>Mer</option>
              <option value={4}>Jeu</option>
              <option value={5}>Ven</option>
              <option value={6}>Sam</option>
            </Select>
            <Input label="Heure début" error={form.formState.errors.start_time?.message} {...form.register("start_time")} placeholder="18:00" />
            <Input label="Heure fin" error={form.formState.errors.end_time?.message} {...form.register("end_time")} placeholder="10:00" />
          </div>
          <Button type="submit" loading={mutation.isPending}>Créer l’offre</Button>
        </form>
      </Card>
    </div>
  );
}
