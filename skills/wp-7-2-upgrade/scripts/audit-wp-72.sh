#!/usr/bin/env bash
#
# audit-wp-72.sh — scan a WordPress codebase for WordPress 7.2 compatibility risks
# and adoption opportunities.
#
# Usage:
#   ./audit-wp-72.sh [path] [--adopt] [--max N]
#
#   path      Directory to scan. Defaults to the current directory.
#   --adopt   Also report ADOPT-level findings (new 7.2 APIs you could use).
#   --max N   Maximum matches shown per check. Default 20.
#
# Exit codes:
#   0  no HIGH or MEDIUM findings
#   1  at least one HIGH or MEDIUM finding
#   2  usage error
#
# This is a grep-based triage tool. It finds candidates, not confirmations —
# wp_kses() output, admin icon rendering, and Interactivity hydration are
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

printf '%s=== WordPress 7.2 compatibility audit ===%s\n' "$BOLD" "$RST"
printf '%sScanning: %s%s\n\n' "$DIM" "$ROOT" "$RST"

BC="references/breaking-changes.md"
NA="references/new-apis.md"
CODE="php js jsx ts tsx mjs cjs html"
JS="js jsx ts tsx mjs cjs"
CONF="php js json yml yaml toml conf *htaccess nginx*"

# ---------------------------------------------------------------- HIGH

scan "load-scripts.php / load-styles.php now return 410 Gone" HIGH \
	"$CONF" \
	'load-(scripts|styles)\\?\.php' \
	"$BC §1"

scan "print_* filters fire as deprecations; return values are ignored" HIGH \
	"php" \
	"add_filter\([[:space:]]*['\"]print_(head_scripts|footer_scripts|admin_styles|late_styles)['\"]" \
	"$BC §1"

scan "Removed directive: data-wp-ignore" HIGH \
	"$CODE" \
	'data-wp-ignore' \
	"$BC §3"

scan "Two-hyphen unique ID (now skipped or misread as an event name)" HIGH \
	"$CODE" \
	'data-wp-(watch|init|run)--[A-Za-z0-9]|data-wp-on(-window|-document)?--[A-Za-z0-9_.]*[A-Za-z0-9]--[A-Za-z0-9]' \
	"$BC §3"

scan "Removed prop: InspectorControls __experimentalGroup" HIGH \
	"$JS" \
	'__experimentalGroup' \
	"$BC §3"

# ---------------------------------------------------------------- MEDIUM

scan "Concatenation/compression constants (no longer read)" MED \
	"php" \
	'CONCATENATE_SCRIPTS|COMPRESS_(SCRIPTS|CSS)|ENFORCE_GZIP' \
	"$BC §1"

scan "Deprecated in 7.2: script-loader internals" MED \
	"php" \
	'_print_(scripts|styles)\(|script_concat_settings\(|in_default_dir\(|->get_etag\(|compression_test\(|->(concat|print_code|print_html)\b' \
	"$BC §1"

scan "wp_kses allowlist naming script/style/svg/math (contents/markup now stricter)" MED \
	"php" \
	"['\"](script|style|svg|math)['\"][[:space:]]*=>[[:space:]]*(array|\[|true)" \
	"$BC §2"

scan "Tests asserting exact wp_kses output (normalized by the HTML API parser)" MED \
	"php" \
	'assert(Same|Equals)\(.*wp_(kses|filter_kses|filter_post_kses)' \
	"$BC §2"

scan "\"Howdy\" handling (moved from my-account to user-info)" MED \
	"php" \
	'Howdy' \
	"$BC §4"

scan "CSS on core admin menu/toolbar icon glyphs (now inline SVG)" MED \
	"css scss sass less" \
	'(wp-menu-image|dashicons-before|ab-icon)[^{]*::?before' \
	"$BC §5"

scan "Dashboard Activity widget (Recent Comments moved to its own widget)" MED \
	"php" \
	"dashboard_activity|dashboard_recent_comments" \
	"$BC §7"

scan "Rewrite rules or query arg named 'random' (new core /random/ URLs and query var)" MED \
	"php" \
	"add_rewrite_rule\([[:space:]]*['\"]\^?random|get_query_var\([[:space:]]*['\"]random['\"]|\\\$_(GET|REQUEST)\[[[:space:]]*['\"]random['\"]" \
	"$BC §8"

scan "Sitemap stylesheet filters/classes (deprecated, results ignored)" MED \
	"php" \
	'wp_sitemaps_stylesheet|WP_Sitemaps_Stylesheet|get_sitemap(_index)?_stylesheet_url' \
	"$BC §9"

scan "Deprecated @wordpress/components (removal planned for 7.4)" MED \
	"$JS" \
	'<(__experimental)?(Divider|ResponsiveWrapper|Scrollable|ZStack|Elevation|Surface|Animate)\b|unstableFeaturedImageFlow' \
	"$BC §9"

scan "Plugin*MenuItem 'as' prop (now ignored)" MED \
	"$JS" \
	'<Plugin(More|SidebarMore|Preview)MenuItem[^>]*[[:space:]]as=' \
	"$BC §3"

scan "NO_BACKSLASH_ESCAPES / incompatible_sql_modes (mode now stripped)" MED \
	"php" \
	'NO_BACKSLASH_ESCAPES|incompatible_sql_modes' \
	"$BC §9"

scan "Deprecated cross-origin isolation helpers" MED \
	"php" \
	'wp_add_crossorigin_attributes|wp_start_cross_origin_isolation_output_buffer' \
	"$BC §9"

# ---------------------------------------------------------------- LOW

scan "Local environment type (WP_DEBUG now defaults to true there)" LOW \
	"php yml yaml json env" \
	"WP_ENVIRONMENT_TYPE['\"]?[[:space:]]*(,|=|:)[[:space:]]*['\"]?local" \
	"$BC §6"

scan "Programmatic application passwords (creation now emails the user)" LOW \
	"php sh yml yaml" \
	'create_new_application_password|application-password[[:space:]]+create' \
	"$BC §9"

scan "Icon recolored via fill (stroke-based @wordpress/icons ignore fill)" LOW \
	"$JS css scss" \
	'<Icon[^>]*[[:space:]]fill=|\.components-[a-z-]+[^{]*svg[^{]*\{[^}]*fill:' \
	"$BC §9"

scan "Deprecated helpers: _truncate_post_slug, wp_kses_html_error" LOW \
	"php" \
	'_truncate_post_slug\(|wp_kses_html_error\(' \
	"$BC §9"

scan "Imgur / someecards embeds (oEmbed providers removed)" LOW \
	"php html" \
	'imgur\.com|someecards\.com' \
	"$BC §9"

# ---------------------------------------------------------------- ADOPT

scan "Credential stored in a plaintext option — could use the Secrets API" ADOPT \
	"php" \
	"(get|update|add)_option\([[:space:]]*['\"][a-z0-9_-]*(api_?key|secret|token|password|client_secret)[a-z0-9_-]*['\"]" \
	"$NA — Secrets API"

scan "innerHTML in Interactivity code — could use data-wp-html" ADOPT \
	"$JS" \
	'\.innerHTML[[:space:]]*=' \
	"$NA — Interactivity API: data-wp-html"

scan "dashicons-* admin menu icon — could use a registered SVG icon name" ADOPT \
	"php" \
	"add_menu_page\(.*['\"]dashicons-" \
	"$NA — Icons"

scan "Hand-written text-shadow or gradient-text CSS — could use block supports" ADOPT \
	"css scss" \
	'text-shadow:|background-clip:[[:space:]]*text' \
	"$NA — Block supports"

scan "wp_count_posts on hot paths — pre_wp_count_posts can short-circuit it" ADOPT \
	"php" \
	'wp_count_(posts|attachments)\(' \
	"$NA — Smaller additions"

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

printf 'No HIGH or MED findings. Still verify on a 7.2 beta/RC — wp_kses() output,\n'
printf 'admin icons, and Interactivity hydration are runtime behaviors.\n'
exit 0
