import { useParams } from "react-router-dom";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation, useQuery } from "@tanstack/react-query";
import { getParkingOffers, createOffer } from "../../api/offers";
import { Button, Card, EmptyState, Input, Select, Badge } from "../../shared/ui";
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

export function OwnerOffersPage() {
  const { id } = useParams();
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
    onSuccess: () => query.refetch(),
  });

  return (
    <div className={styles.layout}>
      <Card title="Subscription offers">
        {query.data?.items?.length ? (
          <div className={styles.list}>
            {query.data.items.map((offer) => (
              <div key={offer.offer_id} className={styles.item}>
                <div>
                  <strong>{offer.label}</strong>
                  <span>{offer.type}</span>
                  <span>{offer.weekly_time_slots.length} slots</span>
                </div>
                <Badge label={offer.status} variant={offer.status === "active" ? "success" : "neutral"} />
              </div>
            ))}
          </div>
        ) : (
          <EmptyState title="No offers" description="Create the first subscription offer." />
        )}
      </Card>
      <Card title="Create offer">
        <form className={styles.form} onSubmit={form.handleSubmit((data) => mutation.mutate(data))}>
          <Input label="Label" error={form.formState.errors.label?.message} {...form.register("label")} />
          <Select label="Type" error={form.formState.errors.type?.message} {...form.register("type")}>
            <option value="custom">Custom</option>
            <option value="full">Full</option>
            <option value="evening">Evening</option>
            <option value="weekend">Weekend</option>
          </Select>
          <Input
            label="Price (cents)"
            type="number"
            error={form.formState.errors.price_cents?.message}
            {...form.register("price_cents", { valueAsNumber: true })}
          />
          <Select label="Status" error={form.formState.errors.status?.message} {...form.register("status")}>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </Select>
          <div className={styles.slotGrid}>
            <Select
              label="Start day"
              error={form.formState.errors.start_day?.message}
              {...form.register("start_day", { valueAsNumber: true })}
            >
              <option value={0}>Sun</option>
              <option value={1}>Mon</option>
              <option value={2}>Tue</option>
              <option value={3}>Wed</option>
              <option value={4}>Thu</option>
              <option value={5}>Fri</option>
              <option value={6}>Sat</option>
            </Select>
            <Select
              label="End day"
              error={form.formState.errors.end_day?.message}
              {...form.register("end_day", { valueAsNumber: true })}
            >
              <option value={0}>Sun</option>
              <option value={1}>Mon</option>
              <option value={2}>Tue</option>
              <option value={3}>Wed</option>
              <option value={4}>Thu</option>
              <option value={5}>Fri</option>
              <option value={6}>Sat</option>
            </Select>
            <Input label="Start time" error={form.formState.errors.start_time?.message} {...form.register("start_time")} placeholder="18:00" />
            <Input label="End time" error={form.formState.errors.end_time?.message} {...form.register("end_time")} placeholder="10:00" />
          </div>
          <Button type="submit" loading={mutation.isPending}>Create offer</Button>
        </form>
      </Card>
    </div>
  );
}
