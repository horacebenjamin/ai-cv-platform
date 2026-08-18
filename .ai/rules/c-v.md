---
paths:
  - 'app/{Ai/Agents,Jobs,Services/AI,Services/CV}/**'
---

# C V

## Keep one queue boundary for SDK agents
ProcessAIRequest is the sole queue boundary. Route cv_generation through CVGenerationService/GenerateCvAgent and only the five approved generic features through CareerContentAgent. Agents own instructions and SDK prompting; application services own state, validation, persistence, accounting, credits, and transactions.
