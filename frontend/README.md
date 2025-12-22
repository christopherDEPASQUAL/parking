# Parking Frontend

React + TypeScript frontend for the Parking app.

## Requirements
- Node.js 18+

## Setup
```bash
npm install
```

## Run (dev)
```bash
npm run dev
```

## Build
```bash
npm run build
```

## Environment variables
Create a `.env` file in this folder:
```
VITE_API_BASE_URL=http://localhost:8000
```

## Project structure
- `src/app` Router, providers, guards
- `src/shared` UI kit, layouts, utilities
- `src/entities` Types and Zod schemas
- `src/api` API client and endpoints
- `src/pages` Route pages
- `src/styles` Tokens and base styles

## API contract
See `../docs/api-contract.md` for assumptions and endpoints.
