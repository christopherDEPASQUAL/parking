import { useEffect } from "react";
import { useFieldArray, useForm } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation, useQuery } from "@tanstack/react-query";
import { createParking, getParking, updateParking } from "../../api/parkings";
import { Button, Card, Input, Select, Textarea } from "../../shared/ui";
import styles from "./OwnerParkingFormPage.module.css";
import { useParams } from "react-router-dom";

const schema = z.object({
  name: z.string().min(1),
  address: z.string().min(1),
  description: z.string().optional(),
  latitude: z.number(),
  longitude: z.number(),
  capacity: z.number().int().positive(),
  opening_hours: z.array(
    z.object({
      start_day: z.number().int().min(0).max(6),
      end_day: z.number().int().min(0).max(6),
      start_time: z.string().min(1),
      end_time: z.string().min(1),
    })
  ),
});

type FormValues = z.infer<typeof schema>;

export function OwnerParkingFormPage({ mode }: { mode: "create" | "edit" }) {
  const { id } = useParams();
  const form = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { opening_hours: [{ start_day: 1, end_day: 1, start_time: "08:00", end_time: "18:00" }] },
  });
  const { fields, append, remove } = useFieldArray({ control: form.control, name: "opening_hours" });

  const parkingQuery = useQuery({
    queryKey: ["parking", id],
    queryFn: () => (id ? getParking(id) : Promise.reject()),
    enabled: mode === "edit" && !!id,
  });

  useEffect(() => {
    if (parkingQuery.data) {
      form.reset({
        name: parkingQuery.data.name,
        address: parkingQuery.data.address,
        description: parkingQuery.data.description ?? "",
        latitude: parkingQuery.data.latitude ?? 0,
        longitude: parkingQuery.data.longitude ?? 0,
        capacity: parkingQuery.data.capacity ?? 0,
        opening_hours: parkingQuery.data.opening_schedule ?? [],
      });
    }
  }, [parkingQuery.data, form]);

  const createMutation = useMutation({
    mutationFn: (payload: FormValues) => createParking(payload),
  });
  const updateMutation = useMutation({
    mutationFn: (payload: FormValues) => (id ? updateParking(id, payload) : Promise.reject()),
  });

  const onSubmit = (data: FormValues) => {
    if (mode === "create") {
      createMutation.mutate(data);
    } else {
      updateMutation.mutate(data);
    }
  };

  return (
    <Card title={mode === "create" ? "Create parking" : "Edit parking"}>
      <form className={styles.form} onSubmit={form.handleSubmit(onSubmit)}>
        <Input label="Name" error={form.formState.errors.name?.message} {...form.register("name")} />
        <Input label="Address" error={form.formState.errors.address?.message} {...form.register("address")} />
        <Textarea label="Description" {...form.register("description")} />
        <Input
          label="Latitude"
          type="number"
          step="any"
          error={form.formState.errors.latitude?.message}
          {...form.register("latitude", { valueAsNumber: true })}
        />
        <Input
          label="Longitude"
          type="number"
          step="any"
          error={form.formState.errors.longitude?.message}
          {...form.register("longitude", { valueAsNumber: true })}
        />
        <Input
          label="Capacity"
          type="number"
          step="1"
          error={form.formState.errors.capacity?.message}
          {...form.register("capacity", { valueAsNumber: true })}
        />
        <div className={styles.slots}>
          <div className={styles.slotsHeader}>
            <h4>Opening hours</h4>
            <Button
              type="button"
              variant="secondary"
              onClick={() => append({ start_day: 1, end_day: 1, start_time: "08:00", end_time: "18:00" })}
            >
              Add slot
            </Button>
          </div>
          {fields.map((field, index) => (
            <div key={field.id} className={styles.slotRow}>
              <Select label="Start day" {...form.register(`opening_hours.${index}.start_day`, { valueAsNumber: true })}>
                <option value={0}>Sun</option>
                <option value={1}>Mon</option>
                <option value={2}>Tue</option>
                <option value={3}>Wed</option>
                <option value={4}>Thu</option>
                <option value={5}>Fri</option>
                <option value={6}>Sat</option>
              </Select>
              <Select label="End day" {...form.register(`opening_hours.${index}.end_day`, { valueAsNumber: true })}>
                <option value={0}>Sun</option>
                <option value={1}>Mon</option>
                <option value={2}>Tue</option>
                <option value={3}>Wed</option>
                <option value={4}>Thu</option>
                <option value={5}>Fri</option>
                <option value={6}>Sat</option>
              </Select>
              <Input label="Start time" {...form.register(`opening_hours.${index}.start_time`)} placeholder="08:00" />
              <Input label="End time" {...form.register(`opening_hours.${index}.end_time`)} placeholder="18:00" />
              <Button type="button" variant="ghost" onClick={() => remove(index)}>
                Remove
              </Button>
            </div>
          ))}
        </div>
        <Button type="submit" loading={createMutation.isPending || updateMutation.isPending}>
          {mode === "create" ? "Create parking" : "Save changes"}
        </Button>
      </form>
    </Card>
  );
}
