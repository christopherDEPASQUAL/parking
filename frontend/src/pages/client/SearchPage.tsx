import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { useQuery } from "@tanstack/react-query";
import { searchParkings } from "../../api/parkings";
import { Button, Card, DateTimeInput, Input, EmptyState, Skeleton } from "../../shared/ui";
import { formatDateTime } from "../../shared/utils/format";
import styles from "./SearchPage.module.css";

const schema = z
  .object({
    lat: z.number(),
    lng: z.number(),
    radius: z.number().min(1),
    starts_at: z.string().min(1),
    ends_at: z.string().min(1),
  })
  .refine((data) => new Date(data.starts_at) < new Date(data.ends_at), {
    message: "End time must be after start time",
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
      lat: 48.8566,
      lng: 2.3522,
      radius: 5,
      starts_at: new Date().toISOString().slice(0, 16),
      ends_at: new Date(Date.now() + 60 * 60 * 1000).toISOString().slice(0, 16),
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
        <Card title="Search parkings" subtitle="Find a spot near you">
          <form onSubmit={handleSubmit(onSubmit)} className={styles.form}>
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
              label="Radius (km)"
              type="number"
              step="1"
              error={errors.radius?.message}
              {...register("radius", { valueAsNumber: true })}
            />
            <DateTimeInput label="Start" error={errors.starts_at?.message} {...register("starts_at")} />
            <DateTimeInput label="End" error={errors.ends_at?.message} {...register("ends_at")} />
            <Button type="submit">Search</Button>
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
              title="Search failed"
              description="We could not load results. Try again."
              actionLabel="Retry"
              onAction={() => query.refetch()}
            />
          ) : null}
          {!query.isLoading && query.data?.items?.length === 0 ? (
            <EmptyState
              title="No results"
              description="Adjust your radius or time range to find more parkings."
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
                      View
                    </Button>
                  </div>
                  <div className={styles.meta}>
                    <span>Next availability: {formatDateTime(params?.starts_at)}</span>
                    <span>Capacity: {parking.capacity ?? "-"}</span>
                  </div>
                </Card>
              ))}
            </div>
          ) : null}
        </div>
      </div>
    </div>
  );
}
