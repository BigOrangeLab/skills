#!/usr/bin/env bash
#
# audit-wp-71.sh — scan a WordPress codebase for WordPress 7.1 compatibility risks
# and adoption opportunities.
#
# Usage:
#   ./audit-wp-71.sh [path] [--adopt] [--max N]
#
#   path      Directory to scan. Defaults to the current directory.
#   --adopt   Also report ADOPT-level findings (new 7.1 APIs you could use).
#   --max N   Maximum matches shown per check. Default 20.
#
# Exit codes:
#   0  no HIGH or MEDIUM findings
#   1  at least one HIGH or MEDIUM finding
#   2  usage error
#
# This is a grep-based triage tool. It finds candidates, not confirmations —
# the iframe boundary, cross-origin isolation, and CSS specificity issues are
# runtime behaviors. Verify every finding against the referenced section.

set -o pipefail

ROOT="."
MAX=20
SHOW_ADOPT=0

while [ $# -gt 0 ]; do
	case "$1" in
		--adopt) SHOW_ADOPT=1; shift ;;
		--max)   MAX="${2:-20}"; shift 2 ;;
		-h|--help)
			sed -n '2,20p' "$0" | sed 's/^# \{0,1\}//'
			exit 0 ;;
		-*) printf 'Unknown option: %s\n' "$1" >&2; exit 2 ;;
		*)  ROOT="$1"; shift ;;
	esac
done

if [ ! -d "$ROOT" ]; then
	printf 'Not a directory: %s\n' "$ROOT" >&2
	exit 2
fi

if [ -t 1 ] && [ -z "${NO_COLOR:-}" ]; then
	BOLD=$'\033[1m'; RED=$'\033[31m'; YEL=$'\033[33m'; BLU=$'\033[34m'
	GRN=$'\033[32m'; DIM=$'\033[2m'; RST=$'\033[0m'
else
	BOLD=; RED=; YEL=; BLU=; GRN=; DIM=; RST=
fi

EXCL_DIRS="node_modules vendor build dist .git .svn wp-admin wp-includes coverage"
# Minified and bundled files are skipped: matches there are unreadable single-line
# blobs, and the source they were built from is what needs fixing. Third-party
# minified assets with no source in the repo need a manual review pass.
EXCL_FILES="*.min.js *.min.css *-min.js *.bundle.js *.map *.pot"
EXCL=""
for d in $EXCL_DIRS; do
	EXCL="$EXCL --exclude-dir=$d"
done
for f in $EXCL_FILES; do
	EXCL="$EXCL --exclude=$f"
done
# shellcheck disable=SC2086
set -- $EXCL
EXCL_ARGS=( "$@" )

# Longest source line echoed per match, so a single dense line cannot flood the report.
LINE_WIDTH=200

HIGH_COUNT=0
MED_COUNT=0
LOW_COUNT=0
ADOPT_COUNT=0

# scan LABEL SEVERITY EXTENSIONS PATTERN REFERENCE
scan() {
	label="$1"; sev="$2"; exts="$3"; pattern="$4"; ref="$5"

	if [ "$sev" = "ADOPT" ] && [ "$SHOW_ADOPT" -eq 0 ]; then
		return 0
	fi

	# A token containing "*" is used as a literal filename glob; a bare token is
	# treated as an extension. Lets a check target "*admin*.css" as easily as "css".
	inc=()
	for e in $exts; do
		case "$e" in
			*'*'*) inc+=( --include="$e" ) ;;
			*)     inc+=( --include="*.$e" ) ;;
		esac
	done

	out=$( grep -rInE --binary-files=without-match \
		"${inc[@]}" "${EXCL_ARGS[@]}" -e "$pattern" "$ROOT" 2>/dev/null \
		| head -n "$MAX" | cut -c "1-$LINE_WIDTH" )

	[ -z "$out" ] && return 0

	case "$sev" in
		HIGH)  color="$RED";  HIGH_COUNT=$((HIGH_COUNT + 1)) ;;
		MED)   color="$YEL";  MED_COUNT=$((MED_COUNT + 1)) ;;
		LOW)   color="$BLU";  LOW_COUNT=$((LOW_COUNT + 1)) ;;
		ADOPT) color="$GRN";  ADOPT_COUNT=$((ADOPT_COUNT + 1)) ;;
		*)     color="" ;;
	esac

	printf '%s[%s]%s %s%s%s\n' "$color" "$sev" "$RST" "$BOLD" "$label" "$RST"
	printf '%s        → %s%s\n' "$DIM" "$ref" "$RST"
	printf '%s\n' "$out" | sed 's/^/        /'
	printf '\n'
}

printf '%s=== WordPress 7.1 compatibility audit ===%s\n' "$BOLD" "$RST"
printf '%sScanning: %s%s\n\n' "$DIM" "$ROOT" "$RST"

# ---------------------------------------------------------------- HIGH

scan "Global document/window access in JS (iframed editor)" HIGH \
	"js jsx ts tsx" \
	"(^|[^.[:alnum:]_])(document|window)\.(querySelector|querySelectorAll|getElementById|getElementsByClassName|getElementsByTagName|addEventListener|getComputedStyle)" \
	"breaking-changes.md #1 — use element.ownerDocument / .defaultView, or useRefEffect"

scan "jQuery bound to the global document (iframed editor)" HIGH \
	"js jsx" \
	"(jQuery|\\\$)\( *document *[,)]" \
	"breaking-changes.md #1 — the canvas is a separate document"

scan "Removed: Navigation component family (@wordpress/components)" HIGH \
	"js jsx ts tsx" \
	"Navigation(Menu|Item|Group|BackButton)" \
	"breaking-changes.md #2 — replaced by Navigator. Ignore hits for the core Navigation *block*"

scan "Removed: __experimentalApplyValueToSides" HIGH \
	"js jsx ts tsx" \
	"__experimentalApplyValueToSides" \
	"breaking-changes.md #2 — removed, no direct replacement"

scan "Removed jQuery UI 1.14 APIs" HIGH \
	"js jsx php" \
	"\\\$\.(fn\._form|ui\.(ie|safeActiveElement|safeBlur))" \
	"breaking-changes.md #5"

# ---------------------------------------------------------------- MEDIUM

scan "Inert prop: __next40pxDefaultSize" MED \
	"js jsx ts tsx" \
	"__next40pxDefaultSize" \
	"breaking-changes.md #3 — remove it; controls are 40px unconditionally"

scan "Deprecated size prop used for 40px height" MED \
	"js jsx ts tsx" \
	"size=[\"{ ]*[\"']__unstable-large" \
	"breaking-changes.md #3 — deprecated on BorderBoxControl/BorderControl/FontSizePicker/ToggleGroupControl"

scan "List-table selectors assuming pre-7.1 row header position" MED \
	"css scss less js jsx php" \
	"(th[.# ]*check-column|th\.check-column|td\.(column-)?(title|page-title)|td\.column-primary)" \
	"breaking-changes.md #4 — th moved from checkbox column to title column"

scan "media_library_infinite_scrolling (default flipped to true)" MED \
	"php" \
	"media_library_infinite_scrolling" \
	"breaking-changes.md #6 — any hooked callback now overrides every user's opt-out"

scan "notify_post_author filter (return value is now final)" MED \
	"php" \
	"notify_post_author" \
	"breaking-changes.md #7 — __return_true now emails for spam/trashed comments"

scan "getEntityRecords (non-paginated entities now return everything)" MED \
	"js jsx ts tsx" \
	"getEntityRecords" \
	"breaking-changes.md #8 — lists previously capped at 10 may now be unbounded"

scan "Deprecated: @wordpress/nux (now a no-op)" MED \
	"js jsx ts tsx php json" \
	"(@wordpress/nux|wp[-.]nux)" \
	"breaking-changes.md #12 — migrate to the Guide component"

scan "Deprecated: @wordpress/reusable-blocks" MED \
	"js jsx ts tsx php json" \
	"(@wordpress/reusable-blocks|wp[-.]reusableBlocks|wp-reusable-blocks)" \
	"breaking-changes.md #12 — use core entity methods for Synced Patterns"

scan "Browser-side fetch of remote media (fails under cross-origin isolation)" MED \
	"js jsx ts tsx" \
	"(fetch|XMLHttpRequest)\(.*(https?:)?//.*\.(jpe?g|png|gif|webp|avif|heic)" \
	"breaking-changes.md #14 — POST the URL to the media endpoint and sideload server-side"

# ---------------------------------------------------------------- LOW

scan "Deprecated: __experimentalCloneSanitizedBlock / __experimentalSanitizeBlockAttributes" LOW \
	"js jsx ts tsx" \
	"__experimental(CloneSanitizedBlock|SanitizeBlockAttributes)" \
	"breaking-changes.md #13 — drop the __experimental prefix"

scan "pasteHandler (Markdown parser changed: showdown -> marked)" LOW \
	"js jsx ts tsx" \
	"pasteHandler" \
	"breaking-changes.md #11 — re-test representative Markdown input"

scan "Navigation item font-size CSS (propagation removed)" LOW \
	"css scss less" \
	"(navigation-(link|submenu)|wp-block-navigation)[^{]*has-[a-z0-9-]+-font-size" \
	"breaking-changes.md #9 — core no longer applies these classes to child items"

scan "!important preset-class CSS (block-level specificity dropped)" LOW \
	"css scss less" \
	"has-[a-z0-9-]+-(color|background-color|border-color|gradient-background|font-size|font-family)[^;{}]*\{[^}]*!important" \
	"breaking-changes.md #10 — block-level presets are now :where()-wrapped at 0-1-0"

scan "Admin bar node registration (toolbar now persists in the Site Editor)" LOW \
	"php" \
	"(admin_bar_menu|add_node *\()" \
	"breaking-changes.md #15 — verify behavior in the Site Editor"

scan "Block apiVersion below 3" LOW \
	"json" \
	"\"apiVersion\" *: *[12] *[,}]" \
	"breaking-changes.md #1 — lower API versions no longer avoid the iframe; test in the canvas"

scan "title attribute used as help text (a11y)" LOW \
	"php" \
	"<(button|a|span|input)[^>]*title=[\"']" \
	"new-apis.md — consider wp_get_tooltip() / wp_get_toggletip()"

# ---------------------------------------------------------------- ADOPT

scan "Inline SVG markup in PHP — could use the SVG Icon API" ADOPT \
	"php" \
	"<svg[ >]" \
	"new-apis.md — wp_register_icon() / wp_get_icon(). Note the wp_kses allowlist: svg/path/polygon only, no stroke"

scan "Manual ability filtering — could use wp_get_abilities( \$args )" ADOPT \
	"php" \
	"array_filter *\( *\n? *wp_get_abilities" \
	"new-apis.md — declarative category/namespace/meta args"

scan "Media queries in block CSS — could use @mobile/@tablet in theme.json" ADOPT \
	"css scss less" \
	"@media[^{]*(max-width|min-width) *: *(480|600|768|782|1024)" \
	"new-apis.md — responsive block styles"

scan "Hover/focus CSS for buttons or nav links — could use pseudo style states" ADOPT \
	"css scss less" \
	"(wp-block-button|wp-block-navigation)[^{]*:(hover|focus|active)" \
	"new-apis.md — :hover / :focus / :focus-visible / :active in theme.json"

# Scoped to admin-ish stylesheets: the wp-theme tokens are for wp-admin UI, so
# matching every hex colour in a front-end theme would be pure noise.
scan "Hardcoded colors in admin CSS — could use wp-theme design tokens" ADOPT \
	"*admin*.css *admin*.scss *editor*.css *editor*.scss" \
	"(background-color|color|border-color) *: *#[0-9a-fA-F]{3,8}" \
	"new-apis.md — --wpds-* tokens and ThemeProvider"

# ---------------------------------------------------------------- summary

printf '%s=== Summary ===%s\n' "$BOLD" "$RST"
printf '  %sHIGH%s   %d check(s) with findings\n' "$RED" "$RST" "$HIGH_COUNT"
printf '  %sMED%s    %d check(s) with findings\n' "$YEL" "$RST" "$MED_COUNT"
printf '  %sLOW%s    %d check(s) with findings\n' "$BLU" "$RST" "$LOW_COUNT"
if [ "$SHOW_ADOPT" -eq 1 ]; then
	printf '  %sADOPT%s  %d check(s) with findings\n' "$GRN" "$RST" "$ADOPT_COUNT"
else
	printf '  %sADOPT%s  not run (pass --adopt)\n' "$DIM" "$RST"
fi
printf '\n'

if [ "$HIGH_COUNT" -gt 0 ] || [ "$MED_COUNT" -gt 0 ]; then
	printf '%sReview HIGH and MED findings against references/breaking-changes.md before upgrading.%s\n' "$BOLD" "$RST"
	exit 1
fi

printf 'No HIGH or MED findings. Still verify the editor loads on 7.1 — the iframe,\n'
printf 'cross-origin isolation, and CSS specificity changes are runtime behaviors.\n'
exit 0
