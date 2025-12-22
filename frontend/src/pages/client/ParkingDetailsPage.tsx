import { useMemo, useState } from "react";
import { useParams } from "react-router-dom";
import { useMutation, useQuery } from "@tanstack/react-query";
import { getParking, getParkingAvailability } from "../../api/parkings";
import { getParkingOffers } from "../../api/offers";
import { createReservation } from "../../api/reservations";
import { createSubscription } from "../../api/subscriptions";
import { Button, Card, DateTimeInput, EmptyState, Skeleton, Badge, Input } from "../../shared/ui";
import {
  formatCurrency,
  formatDateTime,
  toLocalDateInputValue,
  toLocalDateTimeInputValue,
} from "../../shared/utils/format";
import { formatDay } from "../../shared/utils/days";
import styles from "./ParkingDetailsPage.module.css";
import { useToast } from "../../shared/ui";

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

export function ParkingDetailsPage() {
  const { id } = useParams();
  const { notify } = useToast();
  const [range, setRange] = useState({
    starts_at: toLocalDateTimeInputValue(new Date()),
    ends_at: toLocalDateTimeInputValue(new Date(Date.now() + 60 * 60 * 1000)),
  });
  const [reservationRange, setReservationRange] = useState({
    starts_at: toLocalDateTimeInputValue(new Date()),
    ends_at: toLocalDateTimeInputValue(new Date(Date.now() + 60 * 60 * 1000)),
  });
  const [selectedOfferId, setSelectedOfferId] = useState<string | null>(null);
  const [subscriptionRange, setSubscriptionRange] = useState({
    start_date: toLocalDateInputValue(new Date()),
    end_date: toLocalDateInputValue(new Date(new Date().setMonth(new Date().getMonth() + 1))),
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
      notify({
        title: "Réservation demandée",
        description: "Consultez votre liste de réservations.",
        variant: "success",
      });
    },
    onError: (error: any) => {
      notify({
        title: "Réservation échouée",
        description: error?.message || "Veuillez réessayer",
        variant: "error",
      });
    },
  });

  const subscriptionMutation = useMutation({
    mutationFn: () =>
      id && selectedOfferId
        ? createSubscription({
            parking_id: id,
            offer_id: selectedOfferId,
            start_date: subscriptionRange.start_date,
            end_date: subscriptionRange.end_date,
          })
        : Promise.reject(),
    onSuccess: (data) => {
      notify({
        title: "Abonnement activé",
        description: `Identifiant d’abonnement : ${data.abonnement_id}`,
        variant: "success",
      });
    },
    onError: (error: any) => {
      notify({
        title: "Abonnement échoué",
        description: error?.message || "Veuillez vérifier les dates et réessayer.",
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
        <EmptyState title="Parking introuvable" description="Impossible de charger ce parking." />
      </div>
    );
  }

  const parking = parkingQuery.data;

  return (
    <div className="container">
      <div className={styles.layout}>
        <Card title={parking.name} subtitle={parking.address}>
          <p>{parking.description ?? "Aucune description pour le moment."}</p>
          <div className={styles.info}>
            <div>
              <span>Capacité</span>
              <strong>{parking.capacity ?? "-"}</strong>
            </div>
            <div>
              <span>Tarification</span>
              <strong>
                {parking.pricing_plan?.defaultPricePerStepCents
                  ? formatCurrency(parking.pricing_plan.defaultPricePerStepCents, "EUR")
                  : "-"}
              </strong>
            </div>
          </div>
          <div className={styles.section}>
            <h4>Horaires d’ouverture</h4>
            <ul>
              {openingHours.length ? (
                openingHours.map((slot) => <li key={slot}>{slot}</li>)
              ) : (
                <li>Toujours ouvert</li>
              )}
            </ul>
          </div>
        </Card>

        <Card title="Vérifier la disponibilité">
          <div className={styles.form}>
            <DateTimeInput
              label="Début"
              value={range.starts_at}
              onChange={(event) => setRange((prev) => ({ ...prev, starts_at: event.target.value }))}
            />
            <DateTimeInput
              label="Fin"
              value={range.ends_at}
              onChange={(event) => setRange((prev) => ({ ...prev, ends_at: event.target.value }))}
            />
            <Button onClick={() => availabilityQuery.refetch()}>Vérifier</Button>
          </div>
          {availabilityQuery.data ? (
            <div className={styles.availability}>
              <Badge label={`Places libres : ${availabilityQuery.data.free_spots}`} variant="success" />
              <span>Mise à jour pour {formatDateTime(range.starts_at)}</span>
            </div>
          ) : null}
        </Card>

        <Card title="Réserver une place" subtitle="Confirmez la date et l’heure">
          <div className={styles.form}>
            <DateTimeInput
              label="Début"
              value={reservationRange.starts_at}
              onChange={(event) =>
                setReservationRange((prev) => ({ ...prev, starts_at: event.target.value }))
              }
            />
            <DateTimeInput
              label="Fin"
              value={reservationRange.ends_at}
              onChange={(event) => setReservationRange((prev) => ({ ...prev, ends_at: event.target.value }))}
            />
            <Button
              onClick={() => {
                if (new Date(reservationRange.starts_at) >= new Date(reservationRange.ends_at)) {
                  notify({
                    title: "Plage horaire invalide",
                    description: "L’heure de fin doit être après l’heure de début.",
                    variant: "error",
                  });
                  return;
                }
                reservationMutation.mutate();
              }}
              loading={reservationMutation.isPending}
            >
              Réserver
            </Button>
          </div>
        </Card>
      </div>

      <div className={styles.section}>
        <h3>Offres d’abonnement</h3>
        {offersQuery.isLoading ? (
          <Skeleton height={24} />
        ) : offersQuery.data?.items?.length ? (
          <div className={styles.offers}>
            {offersQuery.data.items.map((offer) => (
              <Card key={offer.offer_id}>
                <div className={styles.offerHeader}>
                  <div>
                    <h4>{offer.label}</h4>
                    <p>{offerTypeLabel[offer.type] ?? offer.type}</p>
                  </div>
                  <Badge
                    label={offerStatusLabel[offer.status] ?? offer.status}
                    variant={offer.status === "active" ? "success" : "neutral"}
                  />
                </div>
                <strong>{formatCurrency(offer.price_cents)}</strong>
                <ul>
                  {offer.weekly_time_slots.map((slot, index) => (
                    <li key={`${offer.offer_id}-${index}`}>
                      {formatDay(slot.start_day)} {slot.start_time} - {formatDay(slot.end_day)} {slot.end_time}
                    </li>
                  ))}
                </ul>
                <div className={styles.offerActions}>
                  <Button
                    variant={selectedOfferId === offer.offer_id ? "secondary" : "primary"}
                    onClick={() => setSelectedOfferId(offer.offer_id)}
                  >
                    {selectedOfferId === offer.offer_id ? "Sélectionnée" : "Choisir l’offre"}
                  </Button>
                </div>
                {selectedOfferId === offer.offer_id ? (
                  <div className={styles.subscriptionForm}>
                    <Input
                      label="Date de début"
                      type="date"
                      value={subscriptionRange.start_date}
                      onChange={(event) =>
                        setSubscriptionRange((prev) => ({ ...prev, start_date: event.target.value }))
                      }
                    />
                    <Input
                      label="Date de fin"
                      type="date"
                      value={subscriptionRange.end_date}
                      onChange={(event) =>
                        setSubscriptionRange((prev) => ({ ...prev, end_date: event.target.value }))
                      }
                    />
                    <Button
                      onClick={() => {
                        if (new Date(subscriptionRange.start_date) >= new Date(subscriptionRange.end_date)) {
                          notify({
                            title: "Période invalide",
                            description: "La date de fin doit être après la date de début.",
                            variant: "error",
                          });
                          return;
                        }
                        subscriptionMutation.mutate();
                      }}
                      loading={subscriptionMutation.isPending}
                    >
                      Souscrire
                    </Button>
                  </div>
                ) : null}
              </Card>
            ))}
          </div>
        ) : (
          <EmptyState title="Aucune offre" description="Ce parking n’a pas encore d’offres d’abonnement." />
        )}
      </div>
    </div>
  );
}
