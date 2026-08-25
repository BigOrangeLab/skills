---
name: harvest-clickup-reconcile
description: "Use when auditing Harvest time entries against the ClickUp tasks their notes reference, to catch time billed to the wrong client. Triggers on reconciling or sanity-checking tracked time, finding conflated or misbilled clients, closing out a month before invoicing, or investigating why a client's hours look wrong."
compatibility: "PHP 8.1+ with ext-json. ext-curl preferred (falls back to the stream wrapper). Requires a Harvest personal access token with account id, and a ClickUp API token. Harvest notes must reference ClickUp task ids for an entry to be verifiable."
license: MIT
metadata:
    author: georgestephanis
    version: "1.0"
    written: "2026-08-25"
    written_against:
        harvest_api: "v2"
        clickup_api: "v2"
        php: "8.1+"
---

# Harvest ↔ ClickUp client reconciliation

Catches time billed to the wrong client by comparing each Harvest entry's client
against the client that owns the ClickUp task the entry references.

## When to use

- Before invoicing, or when closing out a month.
- When a client's hours look wrong and you need to know whether work was logged
  against the wrong client.
- As a scheduled check — the tool exits non-zero when it finds mismatches, so it
  can gate a cron job or CI run.

Do not reach for this to check whether the _hours_ are right, or whether the work
itself was correct. It answers one question only: was this time billed to the
client that owns the referenced task?

## Inputs required

**Credentials.** A Harvest personal access token plus account id, and a ClickUp
API token. Resolved in this order, first non-empty wins:

1. CLI flags — `--harvest-token=`, `--harvest-account=`, `--clickup-token=`
2. Environment — `HARVEST_ACCESS_TOKEN` (or `HARVEST_TOKEN`),
   `HARVEST_ACCOUNT_ID`, `HARVEST_USER_ID` (optional),
   `CLICKUP_TOKEN` (or `CLICKUP_API_TOKEN` / `CLICKUP_API_KEY`)
3. A config file

**Project rules**, mapping ClickUp containers to Harvest clients. These can only
come from a config file. Credentials alone are enough to run, but with no rules
every entry lands in the unmapped bucket and the audit tells you nothing.

### Config file

Searched in order: `--config`, `$RHC_CONFIG`,
`./harvest-clickup-reconcile.json`, `./.harvest-clickup-reconcile.json`,
`$XDG_CONFIG_HOME/harvest-clickup-reconcile/config.json`, then any `config.json`
found walking up from the working directory.

```json
{
	"harvest": { "account_id": "123456", "token_env": "HARVEST_TOKEN" },
	"clickup": { "token_env": "CLICKUP_TOKEN" },
	"projects": {
		"Acme": {
			"clickup_tasks": ["*Acme*"],
			"harvest_client": "Acme Inc"
		}
	},
	"client_families": [["Parent Org", "Program Name"]]
}
```

`clickup_tasks` holds globs matched against the task's list, folder, and space
names; `harvest_client` is the Harvest client those tasks are expected to be
billed to. The file is read with a strict JSON parser — no comments, no trailing
commas.

Keep secrets out of the file where you can. For any key `K`, the tool also
accepts `K_env` (read that environment variable), `K_file` (read that file), and
`K_command` (run it, take stdout) — enough to source credentials from a password
manager, keychain, CI secret, or mounted secret file:

```json
{
	"harvest": { "account_id": "123456", "token_file": "/run/secrets/harvest" },
	"clickup": { "token_command": "op read op://vault/clickup/token" }
}
```

## Procedure

Harvest notes must lead with the ClickUp task id:

```text
#abc123de - Please rebuild the logos module
```

The tool extracts that id, fetches the task, reads its container hierarchy, and
maps container → client through the `projects` globs.

```bash
php tools/reconcile-harvest-clickup.php                    # year to date, Markdown
php tools/reconcile-harvest-clickup.php --days=30          # last 30 days
php tools/reconcile-harvest-clickup.php --from=2026-01-01 --to=2026-03-31
php tools/reconcile-harvest-clickup.php --format=tsv > audit.tsv    # spreadsheet
php tools/reconcile-harvest-clickup.php --format=html > worklist.html
php tools/reconcile-harvest-clickup.php --format=json --all         # full detail
php tools/reconcile-harvest-clickup.php --refresh          # bypass the task cache
```

### Match the most-specific container first

Matching runs **list → folder → space**, stopping at the first hit. This ordering
is load-bearing, not cosmetic. Where a client is a program or sub-brand of a
larger organisation, its tasks commonly live in the parent's folder under their
own list. Matching folder-first attributes every one of those tasks to the
parent and produces a false-positive run as large as the client's task count.

Note also that `space` is empty in many ClickUp workspaces, leaving
`folder → list` as the real hierarchy. Do not assume a space is present.

### Client families

Related entities that bill as one relationship can be declared equivalent, so
differences between them report as informational rather than as conflation:

```jsonc
"client_families": [["Parent Org", "Program Name"]]
```

Leave this unset if the two are separate Harvest clients with separate budgets —
there, a swap between them _is_ a real billing error and should stay loud. This
is a billing judgement call; confirm with the user before adding it.

## Verification

Exit codes: `0` clean · `2` cross-client mismatches or suspect unmapped entries
found · `1` error.

Read the output by bucket:

| Bucket                                         | Meaning                                                                        | Action                                   |
| ---------------------------------------------- | ------------------------------------------------------------------------------ | ---------------------------------------- |
| **Cross-client mismatch**                      | Task's client ≠ billed client. Genuine conflation.                             | Fix the Harvest entry.                   |
| **Unmapped — container suggests other client** | No config rule, but the container name clearly differs from the billed client. | Triage individually, then add a rule.    |
| **Unmapped — no config rule**                  | Container has no `clickup_tasks` glob, or no `harvest_client`. Not verifiable. | Add the mapping to the config file.      |
| **No ClickUp id in notes**                     | Note has no `#id`. Not verifiable by this tool at all.                         | Only fixable by changing logging habits. |
| **Within client family**                       | Differs, but both names are declared equivalent.                               | Informational.                           |

**Always report the unverifiable buckets alongside the mismatches.** A clean
mismatch list means nothing if a third of the hours carry no ClickUp reference.
Say what the audit could and could not see.

`--format=html` writes a standalone remediation worklist: findings grouped by
date, each date heading deep-linking to the Harvest day view
(`<base_uri>/time/day/YYYY/MM/DD`, resolved live from `GET /v2/company`) and each
row linking to its ClickUp task. Rows carry a checkbox whose state persists in
`localStorage`, so a long list can be worked through across sittings. Write it
somewhere gitignored — it contains client billing detail.

## Failure modes

- **Everything reports unmapped.** No project rules loaded. The tool warns about
  this on STDERR unless `--quiet`; check that a config file was actually found.
- **A whole client's tasks attributed to its parent.** Folder matched before
  list. See "match the most-specific container first" above.
- **`HTTP 429` from ClickUp.** Rate limited. The tool backs off and retries
  three times per task; task placement is cached, so a re-run resumes cheaply.
- **Stale placement after reorganising ClickUp.** Run with `--refresh`.
- **A wrong-but-plausible `#id`** — a typo landing on a real task — is invisible.
  So is any entry whose notes lack an id.

Task placement is cached under `--cache-dir`, else `$RHC_CACHE_DIR`, else
`$XDG_CACHE_HOME` or `~/.cache`, in `harvest-clickup-reconcile/`.

## Extending

- `rhcExtractClickUpId()` — accepts `#id`, `CU-id`, or a task URL. It deliberately
  refuses bare alphanumeric words; a loose pattern here matches ordinary note
  prose and fabricates task ids that then fail lookup.
- `rhcUnmappedLooksWrong()` — heuristic for unmapped containers. Skips generic
  names (`Support`, `Internal Tasks`, `hidden`, `backlog`, `general`) and only
  fires when neither name contains the other, so a client and its own sub-brand
  are not flagged against each other.
- `rhcSameFamily()` — reads `client_families`.
- `rhcResolveSecret()` — the credential resolution chain. Add new sources here.

## Escalation

Escalate to the user, rather than guessing, when:

- A mismatch could plausibly be correct — sub-contracted work, or a task
  deliberately filed under one client while billed to another.
- You are considering adding a `client_families` entry. Declaring two clients
  equivalent silences a real class of billing error; that is the user's call.
- A large share of entries carry no ClickUp id. The fix is a logging-habit
  change, not a config change.
