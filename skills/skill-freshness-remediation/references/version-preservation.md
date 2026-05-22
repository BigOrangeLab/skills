# Version Preservation Patterns

When refreshing an existing skill, prefer preserving valid older-version guidance over replacing it wholesale.

## Keep both when both matter

Use short labels when behavior differs by version:

- `For Terminus 3.x, use ...`
- `For Terminus 4.x, use ...`
- `On WordPress 6.9, this screen still uses ...`
- `On WordPress 7.0+, prefer ...`

## Broaden compatibility instead of flattening history

If a skill still works across a range, say so directly in `compatibility` and the body text.

Good:

- `compatibility: "WordPress 6.9 to 7.0. Some UI details differ by version."`

Bad:

- Replacing all 6.9 guidance with 7.0 guidance while the repo still needs both

## Update metadata honestly

- Update `written_against` for the versions you actually reviewed.
- Do not imply that an older branch of guidance vanished just because the reviewed version moved forward.
- If only one tool in a skill was revalidated, update only that tool's entry and explain the partial review in the PR summary if needed.

## Remove older guidance only with intent

Dropping legacy instructions is appropriate only when at least one of these is true:

- The support policy no longer includes the older version
- The older workflow is provably obsolete or broken
- The user explicitly asked to stop documenting the legacy path

If none of those are true, preserve the older guidance and label it.
