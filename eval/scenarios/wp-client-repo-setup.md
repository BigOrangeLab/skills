# Scenario: wp-client-repo-setup

## Prompt

Set up linting for this client plugin repo. It has a `pnpm-lock.yaml` and some JS in `assets/`, but `package.json` has no `engines` entry and there is no `.npmrc`.

## Expected behavior

- Uses `wp-client-repo-setup` when the prompt matches its description.
- Detects `pnpm-lock.yaml` as a `pnpm-first` signal and proposes that profile instead of applying it silently.
- Stops and asks before adding pnpm tooling, because the repo has not opted into pnpm via `engines` or `.npmrc`.
- Does not create a `package-lock.json` or switch the repo to npm.
- With no profile named and no profile signal present, follows the default path unchanged.
