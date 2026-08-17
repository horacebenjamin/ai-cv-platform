---
paths:
  - 'tests/Feature/**'
---

# Feature

## Feature tests inherit database refresh
Feature tests receive RefreshDatabase globally from tests/Pest.php; do not add the trait to individual feature-test files.
