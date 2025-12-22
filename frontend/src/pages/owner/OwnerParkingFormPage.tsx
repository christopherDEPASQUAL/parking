import { useEffect } from "react";
import { useFieldArray, useForm } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation, useQuery } from "@tanstack/react-query";
import { createParking, getParking, updateParking } from "../../api/parkings";
import { Button, Card, Input, Select, Textarea, useToast } from "../../shared/ui";
import styles from "./OwnerParkingFormPage.module.css";
import { useNavigate, useParams } from "react-router-dom";

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
  const navigate = useNavigate();
  const { notify } = useToast();
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
    onSuccess: (data: any) => {
      notify({
        title: "Parking créé",
        description: "Vous pouvez maintenant gérer les tarifs et les offres.",
        variant: "success",
      });
      const parkingId = data?.parking_id ?? data?.id;
      if (parkingId) {
        navigate(`/owner/parkings/${parkingId}/edit`);
      }
    },
    onError: (error: any) => {
      notify({
        title: "Création échouée",
        description: error?.message || "Veuillez vérifier le formulaire et réessayer.",
        variant: "error",
      });
    },
  });
  const updateMutation = useMutation({
    mutationFn: (payload: FormValues) => (id ? updateParking(id, payload) : Promise.reject()),
    onSuccess: () => {
      notify({ title: "Parking mis à jour", description: "Modifications enregistrées.", variant: "success" });
    },
    onError: (error: any) => {
      notify({
        title: "Mise à jour échouée",
        description: error?.message || "Veuillez vérifier le formulaire et réessayer.",
        variant: "error",
      });
    },
  });

  const onSubmit = (data: FormValues) => {
    if (mode === "create") {
      createMutation.mutate(data);
    } else {
      updateMutation.mutate(data);
    }
  };

  return (
    <Card title={mode === "create" ? "Créer un parking" : "Modifier le parking"}>
      <form className={styles.form} onSubmit={form.handleSubmit(onSubmit)}>
        <Input label="Nom" error={form.formState.errors.name?.message} {...form.register("name")} />
        <Input label="Adresse" error={form.formState.errors.address?.message} {...form.register("address")} />
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
          label="Capacité"
          type="number"
          step="1"
          error={form.formState.errors.capacity?.message}
          {...form.register("capacity", { valueAsNumber: true })}
        />
        <div className={styles.slots}>
          <div className={styles.slotsHeader}>
            <h4>Horaires d’ouverture</h4>
            <Button
              type="button"
              variant="secondary"
              onClick={() => append({ start_day: 1, end_day: 1, start_time: "08:00", end_time: "18:00" })}
            >
              Ajouter un créneau
            </Button>
          </div>
          {fields.map((field, index) => (
            <div key={field.id} className={styles.slotRow}>
              <Select label="Jour début" {...form.register(`opening_hours.${index}.start_day`, { valueAsNumber: true })}>
                <option value={0}>Dim</option>
                <option value={1}>Lun</option>
                <option value={2}>Mar</option>
                <option value={3}>Mer</option>
                <option value={4}>Jeu</option>
                <option value={5}>Ven</option>
                <option value={6}>Sam</option>
              </Select>
              <Select label="Jour fin" {...form.register(`opening_hours.${index}.end_day`, { valueAsNumber: true })}>
                <option value={0}>Dim</option>
                <option value={1}>Lun</option>
                <option value={2}>Mar</option>
                <option value={3}>Mer</option>
                <option value={4}>Jeu</option>
                <option value={5}>Ven</option>
                <option value={6}>Sam</option>
              </Select>
              <Input label="Heure début" {...form.register(`opening_hours.${index}.start_time`)} placeholder="08:00" />
              <Input label="Heure fin" {...form.register(`opening_hours.${index}.end_time`)} placeholder="18:00" />
              <Button type="button" variant="ghost" onClick={() => remove(index)}>
                Supprimer
              </Button>
            </div>
          ))}
        </div>
        <Button type="submit" loading={createMutation.isPending || updateMutation.isPending}>
          {mode === "create" ? "Créer le parking" : "Enregistrer"}
        </Button>
      </form>
    </Card>
  );
}
