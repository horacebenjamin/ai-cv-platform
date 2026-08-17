---
paths:
  - 'app/Models/**'
---

# Models

## Use explicit service calls instead of model events
Coordinate workflow side effects explicitly from services; do not hide them in model observers, booted() hooks, or domain listeners.

## Use fillable allow-lists
Declare mass-assignable attributes with $fillable; do not switch models to $guarded.
