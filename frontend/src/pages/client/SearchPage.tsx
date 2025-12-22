import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { useQuery } from "@tanstack/react-query";
import { searchParkings } from "../../api/parkings";
import { Button, Card, DateTimeInput, Input, EmptyState, Skeleton } from "../../shared/ui";
import { formatDateTime, toLocalDateTimeInputValue } from "../../shared/utils/format";
import styles from "./SearchPage.module.css";

const schema = z
  .object({
    name: z.string().optional(),
    lat: z.number(),
    lng: z.number(),
    radius: z.number().min(1),
    starts_at: z.string().min(1),
    ends_at: z.string().min(1),
  })
  .refine((data) => new Date(data.starts_at) < new Date(data.ends_at), {
    message: "L’heure de fin doit être après l’heure de début",
    path: ["ends_at"],
  });

type FormValues = z.infer<typeof schema>;

export function SearchPage() {
  const [params, setParams] = useState<FormValues | null>(null);
  const navigate = useNavigate();
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      name: "",
      lat: 48.8566,
      lng: 2.3522,
      radius: 5,
      starts_at: toLocalDateTimeInputValue(new Date()),
      ends_at: toLocalDateTimeInputValue(new Date(Date.now() + 60 * 60 * 1000)),
    },
  });

  const query = useQuery({
    queryKey: ["parkings", params],
    queryFn: () => (params ? searchParkings(params) : Promise.resolve({ items: [] })),
    enabled: !!params,
  });

  const onSubmit = (data: FormValues) => {
    setParams(data);
  };

  return (
    <div className="container">
      <div className={styles.layout}>
        <Card title="Rechercher un parking" subtitle="Trouvez une place près de vous">
          <form onSubmit={handleSubmit(onSubmit)} className={styles.form}>
            <Input
              label="Nom du parking"
              error={errors.name?.message}
              placeholder="ex: Centre-ville"
              {...register("name")}
            />
            <Input
              label="Latitude"
              type="number"
              step="any"
              error={errors.lat?.message}
              {...register("lat", { valueAsNumber: true })}
            />
            <Input
              label="Longitude"
              type="number"
              step="any"
              error={errors.lng?.message}
              {...register("lng", { valueAsNumber: true })}
            />
            <Input
              label="Rayon (km)"
              type="number"
              step="1"
              error={errors.radius?.message}
              {...register("radius", { valueAsNumber: true })}
            />
            <DateTimeInput label="Début" error={errors.starts_at?.message} {...register("starts_at")} />
            <DateTimeInput label="Fin" error={errors.ends_at?.message} {...register("ends_at")} />
            <Button type="submit">Rechercher</Button>
          </form>
        </Card>

        <div className={styles.results}>
          {query.isLoading ? (
            <div className={styles.cards}>
              {[...Array(3)].map((_, idx) => (
                <Card key={idx}>
                  <Skeleton height={20} />
                  <Skeleton height={16} width="70%" />
                  <Skeleton height={16} width="40%" />
                </Card>
              ))}
            </div>
          ) : null}
          {query.isError ? (
            <EmptyState
              title="Recherche échouée"
              description="Impossible de charger les résultats. Réessayez."
              actionLabel="Réessayer"
              onAction={() => query.refetch()}
            />
          ) : null}
          {!query.isLoading && query.data?.items?.length === 0 ? (
            <EmptyState
              title="Aucun résultat"
              description="Ajustez le rayon ou l’intervalle pour trouver plus de parkings."
            />
          ) : null}
          {query.data?.items?.length ? (
            <div className={styles.cards}>
              {query.data.items.map((parking) => (
                <Card key={parking.id} className={styles.card}>
                  <div className={styles.cardHeader}>
                    <div>
                      <h3>{parking.name}</h3>
                      <p>{parking.address}</p>
                    </div>
                    <Button variant="secondary" onClick={() => navigate(`/parkings/${parking.id}`)}>
                      Voir
                    </Button>
                  </div>
                  <div className={styles.meta}>
                    <span>Prochaine disponibilité: {formatDateTime(params?.starts_at)}</span>
                    <span>Capacité: {parking.capacity ?? "-"}</span>
                  </div>
                </Card>
              ))}
            </div>
          ) : null}
        <div className={styles.mapCard} aria-hidden="true">
          <img
            className={styles.mapImage}
            src="/parkingLocalisation.png"
            alt=""
            loading="lazy"
          />
        </div>
        </div>
      </div>
    </div>
  );
}
