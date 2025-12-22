import { useMemo, useState } from "react";
import { useParams } from "react-router-dom";
import { useMutation, useQuery } from "@tanstack/react-query";
import { getParking, getParkingAvailability } from "../../api/parkings";
import { getParkingOffers } from "../../api/offers";
import { createReservation } from "../../api/reservations";
import { Button, Card, DateTimeInput, EmptyState, Skeleton, Badge } from "../../shared/ui";
import { formatCurrency, formatDateTime } from "../../shared/utils/format";
import { formatDay } from "../../shared/utils/days";
import styles from "./ParkingDetailsPage.module.css";
import { useToast } from "../../shared/ui";

export function ParkingDetailsPage() {
  const { id } = useParams();
  const { notify } = useToast();
  const [range, setRange] = useState({
    starts_at: new Date().toISOString().slice(0, 16),
    ends_at: new Date(Date.now() + 60 * 60 * 1000).toISOString().slice(0, 16),
  });
  const [reservationRange, setReservationRange] = useState({
    starts_at: new Date().toISOString().slice(0, 16),
    ends_at: new Date(Date.now() + 60 * 60 * 1000).toISOString().slice(0, 16),
  });

  const parkingQuery = useQuery({
    queryKey: ["parking", id],
    queryFn: () => (id ? getParking(id) : Promise.reject()),
    enabled: !!id,
  });

  const offersQuery = useQuery({
    queryKey: ["parking", id, "offers"],
    queryFn: () => (id ? getParkingOffers(id) : Promise.resolve({ items: [] })),
    enabled: !!id,
  });

  const availabilityQuery = useQuery({
    queryKey: ["parking", id, "availability", range],
    queryFn: () => (id ? getParkingAvailability(id, range) : Promise.reject()),
    enabled: false,
  });

  const reservationMutation = useMutation({
    mutationFn: () =>
      id
        ? createReservation({
            parking_id: id,
            starts_at: reservationRange.starts_at,
            ends_at: reservationRange.ends_at,
          })
        : Promise.reject(),
    onSuccess: () => {
      notify({ title: "Reservation requested", description: "Check your reservations list.", variant: "success" });
    },
    onError: (error: any) => {
      notify({
        title: "Reservation failed",
        description: error?.message || "Please try again",
        variant: "error",
      });
    },
  });

  const openingHours = useMemo(() => {
    const schedule = parkingQuery.data?.opening_schedule ?? [];
    return schedule.map(
      (slot) =>
        `${formatDay(slot.start_day)} ${slot.start_time} - ${formatDay(slot.end_day)} ${slot.end_time}`
    );
  }, [parkingQuery.data]);

  if (parkingQuery.isLoading) {
    return (
      <div className="container">
        <Card>
          <Skeleton height={28} />
          <Skeleton height={16} width="60%" />
        </Card>
      </div>
    );
  }

  if (parkingQuery.isError || !parkingQuery.data) {
    return (
      <div className="container">
        <EmptyState title="Parking not found" description="We could not load this parking." />
      </div>
    );
  }

  const parking = parkingQuery.data;

  return (
    <div className="container">
      <div className={styles.layout}>
        <Card title={parking.name} subtitle={parking.address}>
          <p>{parking.description ?? "No description yet."}</p>
          <div className={styles.info}>
            <div>
              <span>Capacity</span>
              <strong>{parking.capacity ?? "-"}</strong>
            </div>
            <div>
              <span>Pricing</span>
              <strong>
                {parking.pricing_plan?.defaultPricePerStepCents
                  ? formatCurrency(parking.pricing_plan.defaultPricePerStepCents, "EUR")
                  : "-"}
              </strong>
            </div>
          </div>
          <div className={styles.section}>
            <h4>Opening hours</h4>
            <ul>
              {openingHours.length ? (
                openingHours.map((slot) => <li key={slot}>{slot}</li>)
              ) : (
                <li>Always open</li>
              )}
            </ul>
          </div>
        </Card>

        <Card title="Check availability">
          <div className={styles.form}>
            <DateTimeInput
              label="Start"
              value={range.starts_at}
              onChange={(event) => setRange((prev) => ({ ...prev, starts_at: event.target.value }))}
            />
            <DateTimeInput
              label="End"
              value={range.ends_at}
              onChange={(event) => setRange((prev) => ({ ...prev, ends_at: event.target.value }))}
            />
            <Button onClick={() => availabilityQuery.refetch()}>Check</Button>
          </div>
          {availabilityQuery.data ? (
            <div className={styles.availability}>
              <Badge label={`Free spots: ${availabilityQuery.data.free_spots}`} variant="success" />
              <span>Updated for {formatDateTime(range.starts_at)}</span>
            </div>
          ) : null}
        </Card>

        <Card title="Reserve a spot" subtitle="Confirm your date and time">
          <div className={styles.form}>
            <DateTimeInput
              label="Start"
              value={reservationRange.starts_at}
              onChange={(event) =>
                setReservationRange((prev) => ({ ...prev, starts_at: event.target.value }))
              }
            />
            <DateTimeInput
              label="End"
              value={reservationRange.ends_at}
              onChange={(event) => setReservationRange((prev) => ({ ...prev, ends_at: event.target.value }))}
            />
            <Button
              onClick={() => {
                if (new Date(reservationRange.starts_at) >= new Date(reservationRange.ends_at)) {
                  notify({
                    title: "Invalid time range",
                    description: "End time must be after start time.",
                    variant: "error",
                  });
                  return;
                }
                reservationMutation.mutate();
              }}
              loading={reservationMutation.isPending}
            >
              Reserve
            </Button>
          </div>
        </Card>
      </div>

      <div className={styles.section}>
        <h3>Subscription offers</h3>
        {offersQuery.isLoading ? (
          <Skeleton height={24} />
        ) : offersQuery.data?.items?.length ? (
          <div className={styles.offers}>
            {offersQuery.data.items.map((offer) => (
              <Card key={offer.offer_id}>
                <div className={styles.offerHeader}>
                  <div>
                    <h4>{offer.label}</h4>
                    <p>{offer.type}</p>
                  </div>
                  <Badge label={offer.status} variant={offer.status === "active" ? "success" : "neutral"} />
                </div>
                <strong>{formatCurrency(offer.price_cents)}</strong>
                <ul>
                  {offer.weekly_time_slots.map((slot, index) => (
                    <li key={`${offer.offer_id}-${index}`}>
                      {formatDay(slot.start_day)} {slot.start_time} - {formatDay(slot.end_day)} {slot.end_time}
                    </li>
                  ))}
                </ul>
              </Card>
            ))}
          </div>
        ) : (
          <EmptyState title="No offers" description="This parking has no subscription offers yet." />
        )}
      </div>
    </div>
  );
}
