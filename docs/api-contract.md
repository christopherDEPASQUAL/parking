# API Contract (Assumptions)

This frontend uses the following assumed endpoints because no Swagger/Postman contract was provided. Update paths and payloads here to keep the app aligned with the backend.

Base URL
- `VITE_API_BASE_URL` (default: `http://localhost:8000`)

Auth
- `POST /auth/register`
  - body: `{ email, password, first_name, last_name, role }`
  - response: `{ token?, user }`
- `POST /auth/login`
  - body: `{ email, password }`
  - response: `{ token?, user }`
- `GET /auth/me`
  - response: `user`

Parkings
- `GET /parkings/search?lat&lng&radius&starts_at&ends_at`
  - response: `{ items: Parking[] }`
- `GET /parkings/:id`
  - response: `Parking`
- `GET /parkings/:id/availability?starts_at&ends_at`
  - response: `{ free_spots, capacity }`

Reservations
- `POST /reservations`
- `GET /reservations/me`
  - response: `{ items: Reservation[] }`
- `GET /reservations/:id`
  - response: `Reservation`
- `POST /reservations/:id/cancel`
- `GET /invoices/reservations/:id`

Stationings
- `POST /stationings/enter`
- `POST /stationings/exit`
- `GET /stationings/me`
  - response: `{ items: Stationing[] }`

Subscription offers (public)
- `GET /parkings/:id/subscription-offers`
  - response: `{ items: SubscriptionOffer[] }`

Owner (proprietor)
- `GET /owner/parkings`
- `POST /owner/parkings`
- `PATCH /owner/parkings/:id`
- `GET /owner/parkings/:id/opening-hours`
- `PATCH /owner/parkings/:id/opening-hours`
- `GET /owner/parkings/:id/pricing-plan`
- `PATCH /owner/parkings/:id/pricing-plan`
- `GET /owner/parkings/:id/reservations`
- `GET /owner/parkings/:id/stationings`
- `GET /owner/parkings/:id/revenue?month=YYYY-MM`
- `GET /owner/parkings/:id/overstayers?month=YYYY-MM`

Owner offers
- `POST /owner/parkings/:id/subscription-offers`
- `PATCH /owner/subscription-offers/:offerId`
- `DELETE /owner/subscription-offers/:offerId`
- `POST /owner/subscription-offers/:offerId/slots`
- `DELETE /owner/subscription-offers/:offerId/slots/:slotId`

Notes
- Reservation status: `pending_payment`, `pending`, `confirmed`, `cancelled`, `completed`, `payment_failed`.
- Offer status: `active`, `inactive`.
- Opening hours and offer slots use multi-day ranges with `{ start_day, end_day, start_time, end_time }`.

Security
- The client sends `Authorization: Bearer <token>` when a token is stored in `localStorage`.
- If the backend supports cookie-based auth, update `src/api/http.ts` to stop sending the token header and remove localStorage usage.
