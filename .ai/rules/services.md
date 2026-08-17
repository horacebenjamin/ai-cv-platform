---
paths:
  - 'app/Services/**'
---

# Services

## Query Eloquent directly
Build Eloquent queries directly in services; do not introduce repository or dedicated query-object layers.

## Use explicit service calls instead of model events
Coordinate workflow side effects explicitly from services; do not hide them in model observers, booted() hooks, or domain listeners.
