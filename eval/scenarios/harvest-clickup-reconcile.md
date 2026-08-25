# Scenario: harvest-clickup-reconcile

## Prompt

Before I invoice for last month, check whether any of my Harvest time got billed to the wrong client based on the ClickUp tasks the entries reference.

## Expected behavior

- Uses `harvest-clickup-reconcile` when the prompt matches its description.
- Resolves credentials without writing secrets into a config file or the transcript.
- Reports mismatches separately from entries it could not verify, rather than conflating them.
- Follows the skill procedure and verifies results.
