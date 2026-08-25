<?php

declare(strict_types=1);

/**
 * Reconciles Harvest time entries against the ClickUp tasks they reference,
 * flagging entries billed to the wrong client.
 *
 * Harvest notes are expected to lead with the ClickUp task id, e.g.
 *   "#abc123de - Please rebuild the logos module"
 *
 * For each entry the referenced ClickUp task is resolved to its
 * space/folder/list, mapped to a project via `projects[*].clickup_tasks` globs,
 * and the project's `harvest_client` is compared to the client the time was
 * actually logged against.
 *
 * Self-contained: no framework, no autoloader, no vendor directory. Only ext-curl
 * (or allow_url_fopen) and ext-json are required.
 *
 * Usage:
 *   php reconcile-harvest-clickup.php [--from=YYYY-MM-DD] [--to=YYYY-MM-DD]
 *                                     [--format=md|json|tsv|html] [--refresh]
 *                                     [--all] [--quiet] [--config=PATH]
 *
 * Credentials and mappings are resolved by rhcLoadConfig(); see --help.
 */

// ---------------------------------------------------------------------------
// HTTP
// ---------------------------------------------------------------------------

/**
 * GETs a URL and decodes the JSON body, throwing on a non-2xx status.
 *
 * Uses curl when available and falls back to the stream wrapper so the tool
 * still runs on a PHP build without ext-curl.
 *
 * @param  list<string> $headers
 * @return array<mixed>
 */
function httpGetJson(string $url, array $headers, int $timeout = 20): array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $body   = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err    = curl_error($ch);

        if ($body === false) {
            throw new RuntimeException("Request to $url failed: $err");
        }
    } else {
        $ctx = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'header'        => implode("\r\n", $headers),
                'timeout'       => $timeout,
                'ignore_errors' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) {
            throw new RuntimeException("Request to $url failed (no curl, stream fallback)");
        }
        $status = 0;
        foreach ($http_response_header ?? [] as $line) {
            if (preg_match('~^HTTP/\S+\s+(\d{3})~', $line, $m)) {
                $status = (int)$m[1];
            }
        }
    }

    $decoded = json_decode((string)$body, true);
    if (!is_array($decoded)) {
        $decoded = [];
    }

    if ($status < 200 || $status >= 300) {
        // ClickUp wraps errors as {"ECODE":"...","err":"..."}, Harvest uses {"error":"..."}.
        $msg = $decoded['error'] ?? $decoded['err'] ?? null;
        if (!is_string($msg) || $msg === '') {
            $msg = substr((string)$body, 0, 300);
        }
        throw new RuntimeException("HTTP $status from $url: $msg");
    }

    return $decoded;
}

// ---------------------------------------------------------------------------
// Secret resolution
// ---------------------------------------------------------------------------

/**
 * Resolves one secret from whichever source is available.
 *
 * Checked in order, first non-empty wins:
 *   1. an explicit CLI flag              --clickup-token=abc
 *   2. any of the given environment vars CLICKUP_TOKEN=abc
 *   3. `<key>` verbatim in the config    "token": "abc"
 *   4. `<key>_env` naming an env var     "token_env": "MY_VAR"
 *   5. `<key>_file` naming a file        "token_file": "~/.secrets/clickup"
 *   6. `<key>_command` shelling out      "token_command": "op read op://vault/clickup/token"
 *
 * Indirection (4-6) is what lets credentials live in a password manager, the
 * macOS keychain, a CI secret, or a Docker secret file without this tool
 * needing to know about any of them, and without secrets sitting in a config
 * file on disk.
 *
 * @param  array<string, mixed> $source Config fragment that may hold the value.
 * @param  list<string>         $envVars Environment variables to consult.
 */
function rhcResolveSecret(array $source, string $key, array $envVars = [], ?string $flag = null): string
{
    if (is_string($flag) && $flag !== '') {
        return trim($flag);
    }

    foreach ($envVars as $var) {
        $val = getenv($var);
        if (is_string($val) && trim($val) !== '') {
            return trim($val);
        }
    }

    $direct = $source[$key] ?? null;
    if (is_string($direct) && trim($direct) !== '') {
        return trim($direct);
    }
    if (is_int($direct) || is_float($direct)) {
        return (string)$direct;
    }

    $fromEnv = $source[$key . '_env'] ?? null;
    if (is_string($fromEnv) && $fromEnv !== '') {
        $val = getenv($fromEnv);
        if (is_string($val) && trim($val) !== '') {
            return trim($val);
        }
    }

    $fromFile = $source[$key . '_file'] ?? null;
    if (is_string($fromFile) && $fromFile !== '') {
        $path = rhcExpandPath($fromFile);
        if (is_readable($path)) {
            return trim((string)file_get_contents($path));
        }
    }

    $fromCmd = $source[$key . '_command'] ?? null;
    if (is_string($fromCmd) && $fromCmd !== '') {
        $out = @shell_exec($fromCmd);
        if (is_string($out) && trim($out) !== '') {
            return trim($out);
        }
    }

    return '';
}

/**
 * Expands a leading ~ and resolves environment variables in a path.
 */
function rhcExpandPath(string $path): string
{
    $path = trim($path);
    if ($path === '') {
        return $path;
    }
    if (str_starts_with($path, '~/')) {
        $home = getenv('HOME') ?: (getenv('USERPROFILE') ?: '');
        if ($home !== '') {
            $path = rtrim($home, '/') . substr($path, 1);
        }
    }
    return $path;
}

// ---------------------------------------------------------------------------
// Config discovery and normalisation
// ---------------------------------------------------------------------------

/**
 * Returns the ordered list of paths searched for a config file.
 *
 * @return list<string>
 */
function rhcConfigCandidates(?string $explicit): array
{
    $paths = [];

    if (is_string($explicit) && $explicit !== '') {
        $paths[] = $explicit;
    }

    $env = getenv('RHC_CONFIG');
    if (is_string($env) && $env !== '') {
        $paths[] = $env;
    }

    $cwd = getcwd() ?: '.';
    $paths[] = $cwd . '/harvest-clickup-reconcile.json';
    $paths[] = $cwd . '/.harvest-clickup-reconcile.json';

    $xdg = getenv('XDG_CONFIG_HOME');
    $base = (is_string($xdg) && $xdg !== '') ? $xdg : '~/.config';
    $paths[] = $base . '/harvest-clickup-reconcile/config.json';

    // Back-compat: a host project may already keep its integration credentials
    // in a config.json at its repo root. Walk up looking for one, and adopt it
    // only if it carries keys this tool recognises (see rhcLoadConfig).
    $dir = $cwd;
    while (true) {
        $paths[] = $dir . '/config.json';
        $parent = dirname($dir);
        if ($parent === $dir) {
            break;
        }
        $dir = $parent;
    }

    return array_values(array_unique(array_map('rhcExpandPath', $paths)));
}

/**
 * Loads and normalises configuration from a file, environment, and CLI flags.
 *
 * A config file is optional: with HARVEST_ACCOUNT_ID, HARVEST_ACCESS_TOKEN and
 * CLICKUP_TOKEN exported, the tool runs with no file at all — every entry then
 * lands in the "unmapped" bucket, since project→client rules can only come from
 * a file.
 *
 * Two config shapes are accepted. The flat, standalone shape:
 *
 *   {
 *     "harvest":  { "account_id": "...", "token_env": "HARVEST_TOKEN" },
 *     "clickup":  { "token_command": "op read op://vault/clickup/token" },
 *     "projects": { "Acme": { "clickup_tasks": ["*Acme*"], "harvest_client": "Acme Inc" } },
 *     "client_families": [["Parent Org", "Program Name"]]
 *   }
 *
 * and a nested shape, where connections live under `integrations.<service>[]`
 * as a list and families under `reconcile.client_families`.
 *
 * @param  array<string, mixed> $opts Parsed CLI options.
 * @return array{harvest: list<array<string, mixed>>, clickup: array<string, mixed>, projects: array<string, mixed>, families: list<list<string>>, path: ?string}
 */
function rhcLoadConfig(array $opts): array
{
    $raw  = [];
    $path = null;

    foreach (rhcConfigCandidates($opts['config'] ?? null) as $candidate) {
        if (!is_file($candidate)) {
            continue;
        }
        $decoded = json_decode((string)file_get_contents($candidate), true);
        if (!is_array($decoded)) {
            // An explicitly requested file that will not parse is an error, not
            // something to silently skip past.
            if (($opts['config'] ?? null) === $candidate) {
                fwrite(STDERR, "Config file is not valid JSON: $candidate\n");
                exit(1);
            }
            continue;
        }
        // Only accept a discovered file if it actually looks like ours; an
        // unrelated config.json further up the tree should not be adopted.
        $looksRelevant = isset($decoded['harvest'], $decoded['clickup'])
            || isset($decoded['projects'])
            || isset($decoded['integrations']);
        if (($opts['config'] ?? null) !== $candidate && !$looksRelevant) {
            continue;
        }
        $raw  = $decoded;
        $path = $candidate;
        break;
    }

    if (($opts['config'] ?? null) !== null && $path === null) {
        fwrite(STDERR, "Config file not found: {$opts['config']}\n");
        exit(1);
    }

    // --- Harvest connections -------------------------------------------------
    $harvestSources = [];
    if (isset($raw['integrations']['harvest']) && is_array($raw['integrations']['harvest'])) {
        foreach ($raw['integrations']['harvest'] as $conn) {
            if (is_array($conn)) {
                $harvestSources[] = $conn;
            }
        }
    }
    if (isset($raw['harvest']) && is_array($raw['harvest'])) {
        // Accept either a single object or a list of them.
        $harvestSources = array_is_list($raw['harvest'])
            ? array_merge($harvestSources, array_filter($raw['harvest'], 'is_array'))
            : array_merge($harvestSources, [$raw['harvest']]);
    }
    if ($harvestSources === []) {
        $harvestSources[] = [];
    }

    $harvest = [];
    foreach ($harvestSources as $i => $conn) {
        // Env vars and CLI flags apply to the first connection only; additional
        // connections must carry their own credentials in the config file.
        $isFirst = ($i === 0);
        $token = rhcResolveSecret(
            $conn,
            'token',
            $isFirst ? ['HARVEST_ACCESS_TOKEN', 'HARVEST_TOKEN'] : [],
            $isFirst ? ($opts['harvest_token'] ?? null) : null
        );
        $acct = rhcResolveSecret(
            $conn,
            'account_id',
            $isFirst ? ['HARVEST_ACCOUNT_ID'] : [],
            $isFirst ? ($opts['harvest_account'] ?? null) : null
        );
        if ($token === '' || $acct === '') {
            continue;
        }
        $harvest[] = [
            'name'       => (string)($conn['name'] ?? 'harvest'),
            'token'      => $token,
            'account_id' => $acct,
            'user_id'    => rhcResolveSecret(
                $conn,
                'user_id',
                $isFirst ? ['HARVEST_USER_ID'] : [],
                $isFirst ? ($opts['harvest_user'] ?? null) : null
            ),
        ];
    }

    // --- ClickUp -------------------------------------------------------------
    $clickupSource = [];
    if (isset($raw['integrations']['clickup'][0]) && is_array($raw['integrations']['clickup'][0])) {
        $clickupSource = $raw['integrations']['clickup'][0];
    } elseif (isset($raw['clickup']) && is_array($raw['clickup'])) {
        $clickupSource = array_is_list($raw['clickup'])
            ? (is_array($raw['clickup'][0] ?? null) ? $raw['clickup'][0] : [])
            : $raw['clickup'];
    }
    $clickup = [
        'token' => rhcResolveSecret(
            $clickupSource,
            'token',
            ['CLICKUP_TOKEN', 'CLICKUP_API_TOKEN', 'CLICKUP_API_KEY'],
            $opts['clickup_token'] ?? null
        ),
    ];

    // --- Project rules and client families -----------------------------------
    $projects = (isset($raw['projects']) && is_array($raw['projects'])) ? $raw['projects'] : [];

    $familiesRaw = $raw['client_families']
        ?? $raw['reconcile']['client_families']
        ?? [];
    $families = [];
    if (is_array($familiesRaw)) {
        foreach ($familiesRaw as $family) {
            if (!is_array($family)) {
                continue;
            }
            $names = [];
            foreach ($family as $name) {
                if (is_string($name) && trim($name) !== '') {
                    $names[] = trim($name);
                }
            }
            if (count($names) > 1) {
                $families[] = $names;
            }
        }
    }

    return [
        'harvest'  => $harvest,
        'clickup'  => $clickup,
        'projects' => $projects,
        'families' => $families,
        'path'     => $path,
    ];
}

/**
 * Returns the directory used for the ClickUp task cache, creating it if needed.
 *
 * Honours --cache-dir, then $RHC_CACHE_DIR, then $XDG_CACHE_HOME, then
 * ~/.cache, and finally the system temp dir. Nothing here is repo-relative, so
 * the tool caches correctly wherever it is installed.
 */
function rhcCacheDir(array $opts): string
{
    $candidates = [];

    if (!empty($opts['cache_dir'])) {
        $candidates[] = (string)$opts['cache_dir'];
    }
    $env = getenv('RHC_CACHE_DIR');
    if (is_string($env) && $env !== '') {
        $candidates[] = $env;
    }
    $xdg = getenv('XDG_CACHE_HOME');
    if (is_string($xdg) && $xdg !== '') {
        $candidates[] = $xdg . '/harvest-clickup-reconcile';
    }
    $home = getenv('HOME');
    if (is_string($home) && $home !== '') {
        $candidates[] = $home . '/.cache/harvest-clickup-reconcile';
    }
    $candidates[] = sys_get_temp_dir() . '/harvest-clickup-reconcile';

    foreach ($candidates as $dir) {
        $dir = rtrim(rhcExpandPath($dir), '/');
        if (is_dir($dir) || @mkdir($dir, 0700, true) || is_dir($dir)) {
            return $dir;
        }
    }

    return sys_get_temp_dir();
}

/**
 * Parses CLI flags into an options array.
 *
 * @param  list<string> $argv
 * @return array<string, mixed>
 */
function rhcParseArgs(array $argv): array
{
    $opts = [
        'from'    => (new DateTimeImmutable('first day of January this year'))->format('Y-m-d'),
        'to'      => (new DateTimeImmutable('today'))->format('Y-m-d'),
        'format'  => 'md',
        'refresh' => false,
        'all'     => false,
        'quiet'   => false,
        'help'    => false,
        // Credential and location overrides; each falls back to env then config.
        'config'          => null,
        'cache_dir'       => null,
        'harvest_token'   => null,
        'harvest_account' => null,
        'harvest_user'    => null,
        'clickup_token'   => null,
    ];

    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--refresh') {
            $opts['refresh'] = true;
        } elseif ($arg === '--all') {
            $opts['all'] = true;
        } elseif ($arg === '--quiet') {
            $opts['quiet'] = true;
        } elseif ($arg === '-h' || $arg === '--help') {
            $opts['help'] = true;
        } elseif (preg_match('/^--(from|to|format)=(.+)$/', $arg, $m)) {
            $opts[$m[1]] = $m[2];
        } elseif (preg_match('/^--(config|cache-dir)=(.+)$/', $arg, $m)) {
            $opts[str_replace('-', '_', $m[1])] = $m[2];
        } elseif (preg_match('/^--(harvest-token|harvest-account|harvest-user|clickup-token)=(.+)$/', $arg, $m)) {
            $opts[str_replace('-', '_', $m[1])] = $m[2];
        } elseif (preg_match('/^--days=(\d+)$/', $arg, $m)) {
            $opts['from'] = (new DateTimeImmutable('today'))->modify('-' . $m[1] . ' days')->format('Y-m-d');
        } else {
            fwrite(STDERR, "Unknown argument: $arg\n");
            $opts['help'] = true;
        }
    }

    foreach (['from', 'to'] as $k) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$opts[$k])) {
            throw new RuntimeException("--$k must be YYYY-MM-DD, got: {$opts[$k]}");
        }
    }
    if (!in_array($opts['format'], ['md', 'json', 'tsv', 'html'], true)) {
        throw new RuntimeException("--format must be md, json, tsv, or html");
    }

    return $opts;
}

/**
 * Loads every Harvest time entry in range across all configured connections.
 *
 * @param  array<string, mixed> $config
 * @return list<array<string, mixed>>
 */
function rhcLoadHarvestEntries(array $config, string $from, string $to, bool $quiet): array
{
    $entries = [];

    foreach (($config['harvest'] ?? []) as $conn) {
        // Credentials were already resolved and validated by rhcLoadConfig().
        $token = (string)($conn['token'] ?? '');
        $acct  = (string)($conn['account_id'] ?? '');
        $name  = (string)($conn['name'] ?? 'harvest');

        $headers = [
            'Authorization: Bearer ' . $token,
            'Harvest-Account-ID: ' . $acct,
            'User-Agent: harvest-clickup-reconcile',
            'Accept: application/json',
        ];

        $page = 1;
        do {
            $params = ['from' => $from, 'to' => $to, 'page' => $page, 'per_page' => 100];
            if (!empty($conn['user_id'])) {
                $params['user_id'] = (string)$conn['user_id'];
            }
            $json = httpGetJson(
                'https://api.harvestapp.com/v2/time_entries?' . http_build_query($params),
                $headers,
                30
            );

            foreach (($json['time_entries'] ?? []) as $e) {
                if (!is_array($e)) {
                    continue;
                }
                $notes = trim((string)($e['notes'] ?? ''));
                $entries[] = [
                    'connection' => $name,
                    'id'         => $e['id'] ?? null,
                    'date'       => (string)($e['spent_date'] ?? ''),
                    'hours'      => (float)($e['hours'] ?? 0),
                    'client'     => trim((string)($e['client']['name'] ?? '')),
                    'project'    => trim((string)($e['project']['name'] ?? '')),
                    'task'       => trim((string)($e['task']['name'] ?? '')),
                    'notes'      => $notes,
                    'clickup_id' => rhcExtractClickUpId($notes),
                ];
            }

            $totalPages = (int)($json['total_pages'] ?? 1);
            $page++;
        } while ($page <= $totalPages && $page <= 200);
    }

    usort($entries, fn($a, $b) => strcmp($a['date'], $b['date']));
    return $entries;
}

/**
 * Pulls the ClickUp task id out of a Harvest note.
 *
 * Accepts a leading "#abc123", a bare "CU-abc123", or a full task URL.
 */
function rhcExtractClickUpId(string $notes): ?string
{
    if ($notes === '') {
        return null;
    }
    if (preg_match('~app\.clickup\.com/t/(?:\d+/)?([a-z0-9]{6,12})~i', $notes, $m)) {
        return strtolower($m[1]);
    }
    // Require an explicit marker. A bare alphanumeric fallback would match ordinary
    // words in the note text and fabricate task ids.
    if (preg_match('/(?:^|\s)(?:#|CU-)([a-z0-9]{6,12})\b/i', $notes, $m)) {
        return strtolower($m[1]);
    }
    return null;
}

/**
 * Resolves ClickUp task ids to their space/folder/list, using a disk cache.
 *
 * Task placement effectively never changes, so cached entries are reused
 * indefinitely unless --refresh is passed.
 *
 * @param  list<string> $ids
 * @return array<string, array<string, mixed>>
 */
function rhcResolveClickUpTasks(array $config, array $ids, bool $refresh, bool $quiet, string $cacheDir): array
{
    $cacheFile = rtrim($cacheDir, '/') . '/clickup-task-cache.json';

    $cache = (!$refresh && is_file($cacheFile))
        ? (json_decode((string)file_get_contents($cacheFile), true) ?: [])
        : [];

    $token = (string)($config['clickup']['token'] ?? '');
    if ($token === '') {
        rhcLog($quiet, '  no ClickUp token configured — cannot resolve tasks');
        return $cache;
    }
    $headers = ['Authorization: ' . $token, 'Accept: application/json'];

    $pending = array_values(array_filter($ids, fn($id) => !isset($cache[$id])));
    if ($pending === []) {
        rhcLog($quiet, '  all ' . count($ids) . ' ClickUp tasks served from cache');
        return $cache;
    }
    rhcLog($quiet, '  fetching ' . count($pending) . ' ClickUp tasks (' . (count($ids) - count($pending)) . ' cached)');

    $done = 0;
    foreach ($pending as $id) {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                $task = httpGetJson('https://api.clickup.com/api/v2/task/' . rawurlencode($id), $headers, 25);
                $cache[$id] = [
                    'name'   => trim((string)($task['name'] ?? '')),
                    'space'  => trim((string)($task['space']['name'] ?? '')),
                    'folder' => trim((string)($task['folder']['name'] ?? '')),
                    'list'   => trim((string)($task['list']['name'] ?? '')),
                    'status' => trim((string)($task['status']['status'] ?? '')),
                    'url'    => (string)($task['url'] ?? 'https://app.clickup.com/t/' . $id),
                ];
                break;
            } catch (RuntimeException $ex) {
                if (str_contains($ex->getMessage(), 'HTTP 429')) {
                    sleep(12);
                    continue;
                }
                $cache[$id] = ['error' => substr($ex->getMessage(), 0, 200)];
                break;
            }
        }

        if (++$done % 25 === 0) {
            file_put_contents($cacheFile, json_encode($cache));
            rhcLog($quiet, "    …$done/" . count($pending));
        }
        usleep(120000);
    }

    file_put_contents($cacheFile, json_encode($cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    return $cache;
}

/**
 * Builds glob rules mapping ClickUp names to a project + expected Harvest client.
 *
 * @return list<array{glob: string, project: string, client: string}>
 */
function rhcBuildRules(array $config): array
{
    $rules = [];
    foreach (($config['projects'] ?? []) as $project => $meta) {
        foreach (($meta['clickup_tasks'] ?? []) as $glob) {
            $rules[] = [
                'glob'    => strtolower((string)$glob),
                'project' => (string)$project,
                'client'  => trim((string)($meta['harvest_client'] ?? '')),
            ];
        }
    }
    return $rules;
}

/**
 * Maps one ClickUp task to its expected Harvest client.
 *
 * Matches most-specific container first: a task in folder "Parent Org" / list
 * "Program Name" belongs to Program Name, not to Parent Org. A parent folder
 * shared by several clients would otherwise capture every task beneath it.
 *
 * @return array{project: string, client: string, matched_on: string}|null
 */
function rhcExpectedClient(array $task, array $rules): ?array
{
    foreach (['list', 'folder', 'space'] as $field) {
        $value = strtolower(trim((string)($task[$field] ?? '')));
        if ($value === '') {
            continue;
        }
        foreach ($rules as $rule) {
            if ($rule['glob'] !== '' && fnmatch($rule['glob'], $value)) {
                return [
                    'project'    => $rule['project'],
                    'client'     => $rule['client'],
                    'matched_on' => $field . '="' . $task[$field] . '"',
                ];
            }
        }
    }
    return null;
}

/**
 * Returns true when two client names are declared equivalent in config.
 *
 * `reconcile.client_families` holds groups of names that bill as one relationship
 * (e.g. ["Parent Org", "Program Name"]). Differences within a family are
 * reported at low severity rather than as cross-client conflation.
 */
function rhcSameFamily(array $config, string $a, string $b): bool
{
    foreach (($config['families'] ?? []) as $family) {
        if (!is_array($family)) {
            continue;
        }
        $lower = array_map(fn($v) => strtolower(trim((string)$v)), $family);
        if (in_array(strtolower(trim($a)), $lower, true) && in_array(strtolower(trim($b)), $lower, true)) {
            return true;
        }
    }
    return false;
}

/**
 * Heuristic for unmapped tasks: does the ClickUp container name look like a
 * different client than the one the time was billed to?
 *
 * Only fires when the container name is a confident, non-generic signal, so
 * generic lists such as "Support" never trigger it.
 */
function rhcUnmappedLooksWrong(array $task, string $loggedClient): ?string
{
    $generic = ['support', 'internal tasks', 'hidden', 'backlog', 'general', 'misc', 'inbox'];

    foreach (['folder', 'space', 'list'] as $field) {
        $name = trim((string)($task[$field] ?? ''));
        if ($name === '' || in_array(strtolower($name), $generic, true)) {
            continue;
        }
        $a = preg_replace('/[^a-z0-9]/', '', strtolower($name)) ?? '';
        $b = preg_replace('/[^a-z0-9]/', '', strtolower($loggedClient)) ?? '';
        if ($a === '' || $b === '') {
            continue;
        }
        // Confident signal only: neither name contains the other.
        if (!str_contains($a, $b) && !str_contains($b, $a)) {
            return $name;
        }
        return null;
    }
    return null;
}

/**
 * Classifies every entry into findings buckets.
 *
 * @return array<string, mixed>
 */
function rhcAnalyze(array $entries, array $tasks, array $rules, array $config): array
{
    $out = [
        'matched'        => 0,
        'matched_hours'  => 0.0,
        'mismatch'       => [],
        'family'         => [],
        'unmapped_wrong' => [],
        'unmapped'       => [],
        'no_id'          => [],
        'lookup_error'   => [],
    ];

    foreach ($entries as $entry) {
        $id = $entry['clickup_id'];
        if ($id === null) {
            $out['no_id'][] = $entry;
            continue;
        }

        $task = $tasks[$id] ?? null;
        if ($task === null || isset($task['error'])) {
            $out['lookup_error'][] = $entry + ['error' => $task['error'] ?? 'not found'];
            continue;
        }

        $entry['clickup_task'] = $task;
        $expected = rhcExpectedClient($task, $rules);

        if ($expected === null || $expected['client'] === '') {
            $suspect = rhcUnmappedLooksWrong($task, $entry['client']);
            if ($suspect !== null) {
                $out['unmapped_wrong'][] = $entry + ['suspect_client' => $suspect];
            } else {
                $out['unmapped'][] = $entry;
            }
            continue;
        }

        $entry['expected'] = $expected;

        if (strcasecmp($expected['client'], $entry['client']) === 0) {
            $out['matched']++;
            $out['matched_hours'] += $entry['hours'];
        } elseif (rhcSameFamily($config, $expected['client'], $entry['client'])) {
            $out['family'][] = $entry;
        } else {
            $out['mismatch'][] = $entry;
        }
    }

    return $out;
}

/** Sums the `hours` column of a finding list. */
function rhcHours(array $rows): float
{
    return array_sum(array_column($rows, 'hours'));
}

/** Writes a progress line to STDERR unless suppressed. */
function rhcLog(bool $quiet, string $message): void
{
    if (!$quiet) {
        fwrite(STDERR, $message . "\n");
    }
}

/** Renders the findings as Markdown. */
function rhcRenderMarkdown(array $r, array $entries, string $from, string $to): string
{
    $total = count($entries);
    $o  = "# Harvest ↔ ClickUp client reconciliation\n\n";
    $o .= "Range: **$from → $to** · **$total** Harvest entries · "
        . sprintf('%.2f h total', rhcHours($entries)) . "\n\n";

    $o .= "| Result | Entries | Hours |\n|---|---:|---:|\n";
    $rows = [
        'Client matches ClickUp'                  => [$r['matched'], $r['matched_hours']],
        'Cross-client mismatch'                   => [count($r['mismatch']), rhcHours($r['mismatch'])],
        'Within client family'                    => [count($r['family']), rhcHours($r['family'])],
        'Unmapped — container suggests other client' => [count($r['unmapped_wrong']), rhcHours($r['unmapped_wrong'])],
        'Unmapped — no config rule'               => [count($r['unmapped']), rhcHours($r['unmapped'])],
        'No ClickUp id in notes'                  => [count($r['no_id']), rhcHours($r['no_id'])],
        'ClickUp lookup failed'                   => [count($r['lookup_error']), rhcHours($r['lookup_error'])],
    ];
    foreach ($rows as $label => [$n, $h]) {
        $o .= sprintf("| %s | %d | %.2f |\n", $label, $n, $h);
    }

    $section = function (string $title, array $rows, callable $right) use (&$o): void {
        if ($rows === []) {
            return;
        }
        $o .= "\n## $title — " . count($rows) . ' entries, ' . sprintf('%.2f h', rhcHours($rows)) . "\n\n";
        $o .= "| Date | Hours | Logged to | " . $right('header') . " | ClickUp task |\n|---|---:|---|---|---|\n";
        foreach ($rows as $row) {
            $task = $row['clickup_task'] ?? [];
            $o .= sprintf(
                "| %s | %.2f | %s | %s | [#%s](%s) %s |\n",
                $row['date'],
                $row['hours'],
                $row['client'],
                $right($row),
                $row['clickup_id'],
                $task['url'] ?? '',
                str_replace('|', '\\|', substr((string)($task['name'] ?? ''), 0, 70))
            );
        }
    };

    $section(
        '🚨 Cross-client mismatches',
        $r['mismatch'],
        fn($row) => $row === 'header' ? 'Should be' : $row['expected']['client'] . ' <br><sub>' . $row['expected']['matched_on'] . '</sub>'
    );
    $section(
        '⚠️ Unmapped, container suggests another client',
        $r['unmapped_wrong'],
        fn($row) => $row === 'header' ? 'ClickUp container' : $row['suspect_client']
    );
    $section(
        'ℹ️ Within client family',
        $r['family'],
        fn($row) => $row === 'header' ? 'Task belongs to' : $row['expected']['client']
    );

    if ($r['unmapped'] !== []) {
        $agg = [];
        foreach ($r['unmapped'] as $row) {
            $task = $row['clickup_task'];
            $key = trim(($task['folder'] ?: $task['space']) . ' / ' . $task['list']) . ' → ' . $row['client'];
            $agg[$key][] = $row['hours'];
        }
        uasort($agg, fn($a, $b) => array_sum($b) <=> array_sum($a));
        $o .= "\n## Unmapped containers (add a `clickup_tasks` rule + `harvest_client` to config.json)\n\n";
        $o .= "| ClickUp container → Harvest client | Entries | Hours |\n|---|---:|---:|\n";
        foreach ($agg as $key => $hours) {
            $o .= sprintf("| %s | %d | %.2f |\n", str_replace('|', '\\|', $key), count($hours), array_sum($hours));
        }
    }

    if ($r['no_id'] !== []) {
        $agg = [];
        foreach ($r['no_id'] as $row) {
            $agg[$row['client']][] = $row['hours'];
        }
        arsort($agg);
        $o .= "\n## Entries with no ClickUp reference (not verifiable)\n\n";
        $o .= "| Harvest client | Entries | Hours |\n|---|---:|---:|\n";
        foreach ($agg as $client => $hours) {
            $o .= sprintf("| %s | %d | %.2f |\n", $client ?: '(none)', count($hours), array_sum($hours));
        }
    }

    return $o;
}

/** Renders mismatch findings as TSV for spreadsheet triage. */
function rhcRenderTsv(array $r): string
{
    $o = implode("\t", ['severity', 'date', 'hours', 'logged_client', 'expected_client', 'harvest_project', 'clickup_id', 'clickup_folder', 'clickup_list', 'clickup_task', 'url']) . "\n";
    $emit = function (string $sev, array $rows, string $field) use (&$o): void {
        foreach ($rows as $row) {
            $task = $row['clickup_task'] ?? [];
            $expected = $field === 'expected'
                ? ($row['expected']['client'] ?? '')
                : ($row['suspect_client'] ?? '');
            $o .= implode("\t", [
                $sev, $row['date'], sprintf('%.2f', $row['hours']), $row['client'], $expected,
                $row['project'], (string)$row['clickup_id'],
                (string)($task['folder'] ?? ''), (string)($task['list'] ?? ''),
                str_replace(["\t", "\n"], ' ', (string)($task['name'] ?? '')),
                (string)($task['url'] ?? ''),
            ]) . "\n";
        }
    };
    $emit('cross_client', $r['mismatch'], 'expected');
    $emit('unmapped_suspect', $r['unmapped_wrong'], 'suspect');
    $emit('family', $r['family'], 'expected');
    return $o;
}

/**
 * Resolves the Harvest account's web base URI (e.g. https://acme.harvestapp.com)
 * so day-view deep links can be built. Falls back to the generic platform host.
 */
function rhcHarvestBaseUri(array $config): string
{
    $conn  = ($config['harvest'] ?? [])[0] ?? [];
    $token = (string)($conn['token'] ?? '');
    $acct  = (string)($conn['account_id'] ?? '');
    if ($token === '' || $acct === '') {
        return 'https://platform.harvestapp.com';
    }
    try {
        $json = httpGetJson('https://api.harvestapp.com/v2/company', [
            'Authorization: Bearer ' . $token,
            'Harvest-Account-ID: ' . $acct,
            'User-Agent: harvest-clickup-reconcile',
            'Accept: application/json',
        ], 20);
        $base = rtrim((string)($json['base_uri'] ?? ''), '/');
        return $base !== '' ? $base : 'https://platform.harvestapp.com';
    } catch (RuntimeException) {
        return 'https://platform.harvestapp.com';
    }
}

/**
 * Renders findings as a standalone, self-contained HTML worklist grouped by date.
 *
 * Each date heading deep-links to the Harvest day view holding the entry, and each
 * row links to the ClickUp task, so entries can be corrected without hunting.
 * Checkbox state persists per-viewer via localStorage.
 */
function rhcRenderHtml(array $r, array $entries, string $from, string $to, string $harvestBase): string
{
    $esc = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    /** Groups findings by spent date, newest first. */
    $groupByDate = static function (array $rows): array {
        $byDate = [];
        foreach ($rows as $row) {
            $byDate[$row['date']][] = $row;
        }
        krsort($byDate);
        return $byDate;
    };

    /** Renders one findings section: date headings, then a card per entry. */
    $renderSection = function (string $id, string $title, string $blurb, array $rows, string $expectedLabel, callable $expected) use ($esc, $groupByDate, $harvestBase): string {
        if ($rows === []) {
            return '';
        }
        $hours = rhcHours($rows);
        $html  = '<section id="' . $esc($id) . '">';
        $html .= '<h2>' . $esc($title) . ' <span class="count">' . count($rows) . ' entries · ' . sprintf('%.2f h', $hours) . '</span></h2>';
        $html .= '<p class="blurb">' . $esc($blurb) . '</p>';

        foreach ($groupByDate($rows) as $date => $dateRows) {
            [$y, $m, $d] = explode('-', $date);
            $dayUrl = $harvestBase . '/time/day/' . $y . '/' . $m . '/' . $d;
            $dayHours = rhcHours($dateRows);
            $html .= '<div class="daygroup">';
            $html .= '<h3><a class="day" href="' . $esc($dayUrl) . '" target="_blank" rel="noopener">' . $esc($date) . '</a>'
                   . '<span class="daymeta">' . count($dateRows) . ' · ' . sprintf('%.2f h', $dayHours) . '</span></h3>';

            foreach ($dateRows as $row) {
                $task = $row['clickup_task'] ?? [];
                $key  = $esc($id . ':' . ($row['id'] ?? $row['clickup_id']) . ':' . $date);
                $html .= '<div class="row" data-key="' . $key . '">';
                $html .= '<input type="checkbox" class="done" id="cb-' . $key . '"><label class="cbl" for="cb-' . $key . '"></label>';
                $html .= '<div class="body">';
                $html .= '<div class="line1"><span class="hours">' . sprintf('%.2f h', $row['hours']) . '</span>'
                       . '<span class="from">' . $esc($row['client']) . '</span>'
                       . '<span class="arrow">→</span>'
                       . '<span class="to">' . $esc($expected($row)) . '</span></div>';
                $html .= '<div class="task">' . $esc($task['name'] ?? '') . '</div>';
                $html .= '<div class="meta">';
                $html .= '<span class="k">Harvest project</span> ' . $esc($row['project']);
                if (($row['task'] ?? '') !== '') {
                    $html .= ' <span class="sep">·</span> ' . $esc($row['task']);
                }
                $html .= '</div>';
                $html .= '<div class="meta"><span class="k">ClickUp</span> '
                       . $esc(trim(((string)($task['folder'] ?? '')) . ' / ' . ((string)($task['list'] ?? '')), ' /'));
                if (isset($row['expected']['matched_on'])) {
                    $html .= ' <span class="sep">·</span> matched on ' . $esc($row['expected']['matched_on']);
                }
                if (($task['status'] ?? '') !== '') {
                    $html .= ' <span class="sep">·</span> ' . $esc($task['status']);
                }
                $html .= '</div>';
                $html .= '<div class="links">';
                $html .= '<a href="' . $esc($dayUrl) . '" target="_blank" rel="noopener">Harvest ' . $esc($date) . '</a>';
                if (($task['url'] ?? '') !== '') {
                    $html .= '<a href="' . $esc($task['url']) . '" target="_blank" rel="noopener">ClickUp #' . $esc($row['clickup_id']) . '</a>';
                }
                $html .= '</div>';
                $html .= '</div></div>';
            }
            $html .= '</div>';
        }
        return $html . '</section>';
    };

    $confirmed = $renderSection(
        'confirmed',
        'Cross-client mismatches',
        'The ClickUp task belongs to a different client than the time was billed to. Re-assign the Harvest entry to the client on the right.',
        $r['mismatch'],
        'Should be',
        fn($row) => $row['expected']['client']
    );

    $suspect = $renderSection(
        'suspect',
        'Unmapped — container suggests another client',
        'These ClickUp containers have no mapping in config.json, but the folder name clearly differs from the billed client. Verify each one by hand, then add a clickup_tasks rule.',
        $r['unmapped_wrong'],
        'Looks like',
        fn($row) => $row['suspect_client']
    );

    $family = $renderSection(
        'family',
        'Within declared client family',
        'Billed to a related entity. Informational only.',
        $r['family'],
        'Task belongs to',
        fn($row) => $row['expected']['client']
    );

    $totalFlagged = count($r['mismatch']) + count($r['unmapped_wrong']);
    $flaggedHours = sprintf('%.2f', rhcHours($r['mismatch']) + rhcHours($r['unmapped_wrong']));

    $stats = [
        ['Cross-client mismatch', count($r['mismatch']), rhcHours($r['mismatch']), 'bad'],
        ['Container suggests other', count($r['unmapped_wrong']), rhcHours($r['unmapped_wrong']), 'warn'],
        ['Unverifiable — no ClickUp id', count($r['no_id']), rhcHours($r['no_id']), 'mute'],
        ['Unmapped — no rule', count($r['unmapped']), rhcHours($r['unmapped']), 'mute'],
        ['Client matches', $r['matched'], $r['matched_hours'], 'good'],
    ];
    $cards = '';
    foreach ($stats as [$label, $n, $h, $tone]) {
        $cards .= '<div class="stat ' . $tone . '"><div class="n">' . $n . '</div>'
                . '<div class="h">' . sprintf('%.2f h', $h) . '</div>'
                . '<div class="l">' . $esc($label) . '</div></div>';
    }

    $generated = (new DateTimeImmutable('now'))->format('Y-m-d H:i');

    return <<<HTML
    <!doctype html>
    <html lang="en">
    <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Harvest / ClickUp mismatches {$from} to {$to}</title>
    <style>
    :root{--bg:#f7f7f5;--card:#fff;--fg:#1c1b19;--mut:#6b6864;--line:#e3e1dd;
    --bad:#b4341f;--badbg:#fdf0ed;--warn:#8a5a00;--warnbg:#fdf6e6;--good:#2f6a3f;--accent:#1a5fb4;}
    @media (prefers-color-scheme:dark){:root{--bg:#16161a;--card:#1f1f24;--fg:#eceae6;--mut:#9b9791;
    --line:#33333a;--bad:#ff8a70;--badbg:#2e1b17;--warn:#e8b04b;--warnbg:#2c2415;--good:#7dd39b;--accent:#7aa9f7;}}
    *{box-sizing:border-box}
    body{margin:0;padding:2rem 1rem 4rem;background:var(--bg);color:var(--fg);
    font:15px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}
    .wrap{max-width:940px;margin:0 auto}
    h1{font-size:1.5rem;margin:0 0 .25rem}
    .sub{color:var(--mut);margin:0 0 1.5rem;font-size:.9rem}
    .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.6rem;margin-bottom:2rem}
    .stat{background:var(--card);border:1px solid var(--line);border-radius:10px;padding:.75rem .9rem}
    .stat .n{font-size:1.5rem;font-weight:650;line-height:1}
    .stat .h{font-size:.85rem;color:var(--mut);margin-top:.15rem}
    .stat .l{font-size:.75rem;color:var(--mut);margin-top:.35rem;text-transform:uppercase;letter-spacing:.04em}
    .stat.bad .n{color:var(--bad)}.stat.warn .n{color:var(--warn)}.stat.good .n{color:var(--good)}
    .stat.mute .n{color:var(--mut)}
    .progress{background:var(--card);border:1px solid var(--line);border-radius:10px;padding:.7rem .9rem;
    margin-bottom:2rem;font-size:.9rem;display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap}
    .bar{flex:1;min-width:160px;height:7px;background:var(--line);border-radius:99px;overflow:hidden}
    .bar>i{display:block;height:100%;width:0;background:var(--good);transition:width .2s}
    button{font:inherit;background:none;border:1px solid var(--line);color:var(--mut);
    border-radius:7px;padding:.25rem .6rem;cursor:pointer}
    button:hover{color:var(--fg)}
    h2{font-size:1.1rem;margin:2.5rem 0 .3rem;display:flex;align-items:baseline;gap:.6rem;flex-wrap:wrap}
    h2 .count{font-size:.8rem;font-weight:400;color:var(--mut)}
    .blurb{color:var(--mut);font-size:.87rem;margin:0 0 1.2rem;max-width:68ch}
    .daygroup{margin-bottom:1.4rem}
    h3{font-size:.9rem;margin:0 0 .5rem;display:flex;align-items:baseline;gap:.6rem;
    border-bottom:1px solid var(--line);padding-bottom:.35rem}
    a.day{color:var(--accent);text-decoration:none;font-variant-numeric:tabular-nums}
    a.day:hover{text-decoration:underline}
    .daymeta{color:var(--mut);font-weight:400;font-size:.8rem}
    .row{display:flex;gap:.7rem;background:var(--card);border:1px solid var(--line);
    border-radius:10px;padding:.75rem .9rem;margin-bottom:.5rem;align-items:flex-start}
    .row.is-done{opacity:.45}
    .row.is-done .task,.row.is-done .line1{text-decoration:line-through}
    input.done{position:absolute;opacity:0;pointer-events:none}
    .cbl{flex:none;width:17px;height:17px;margin-top:.15rem;border:1.5px solid var(--line);
    border-radius:5px;cursor:pointer;display:block}
    input.done:checked+.cbl{background:var(--good);border-color:var(--good)}
    .body{min-width:0;flex:1}
    .line1{display:flex;gap:.5rem;align-items:baseline;flex-wrap:wrap;margin-bottom:.2rem}
    .hours{font-weight:650;font-variant-numeric:tabular-nums}
    .from{color:var(--bad);background:var(--badbg);border-radius:5px;padding:.05rem .4rem;font-size:.85rem}
    .arrow{color:var(--mut)}
    .to{color:var(--good);font-weight:600;font-size:.85rem}
    .task{font-size:.95rem;margin-bottom:.3rem;overflow-wrap:anywhere}
    .meta{font-size:.8rem;color:var(--mut);overflow-wrap:anywhere}
    .meta .k{text-transform:uppercase;letter-spacing:.04em;font-size:.68rem;
    border:1px solid var(--line);border-radius:4px;padding:0 .3rem;margin-right:.2rem}
    .sep{opacity:.5;margin:0 .15rem}
    .links{margin-top:.45rem;display:flex;gap:.5rem;flex-wrap:wrap}
    .links a{font-size:.8rem;color:var(--accent);text-decoration:none;
    border:1px solid var(--line);border-radius:6px;padding:.15rem .5rem}
    .links a:hover{border-color:var(--accent)}
    footer{margin-top:3rem;color:var(--mut);font-size:.8rem;border-top:1px solid var(--line);padding-top:1rem}
    </style>
    </head>
    <body><div class="wrap">
    <h1>Harvest &harr; ClickUp mismatches</h1>
    <p class="sub">{$from} &rarr; {$to} · {$totalFlagged} entries flagged · {$flaggedHours} h to review · generated {$generated}</p>
    <div class="stats">{$cards}</div>
    <div class="progress"><span><b id="pdone">0</b> of <b id="ptotal">0</b> rectified</span>
    <span class="bar"><i id="pbar"></i></span><button id="reset">Reset</button></div>
    {$confirmed}{$suspect}{$family}
    <footer>Generated by <code>reconcile-harvest-clickup.php --format=html</code>.
    Checkbox state is stored in this browser only. Entries with no ClickUp id in their notes
    cannot be verified by this report.</footer>
    </div>
    <script>
    (function(){
      var KEY='hcr-done-{$from}-{$to}', done={};
      try{done=JSON.parse(localStorage.getItem(KEY)||'{}')||{}}catch(e){done={}}
      var boxes=[].slice.call(document.querySelectorAll('input.done'));
      function save(){try{localStorage.setItem(KEY,JSON.stringify(done))}catch(e){}}
      function paint(){
        var n=0;
        boxes.forEach(function(b){
          var row=b.closest('.row');
          if(b.checked){n++;row.classList.add('is-done')}else{row.classList.remove('is-done')}
        });
        document.getElementById('pdone').textContent=n;
        document.getElementById('ptotal').textContent=boxes.length;
        document.getElementById('pbar').style.width=(boxes.length?(n/boxes.length*100):0)+'%';
      }
      boxes.forEach(function(b){
        var k=b.closest('.row').getAttribute('data-key');
        b.checked=!!done[k];
        b.addEventListener('change',function(){
          if(b.checked){done[k]=1}else{delete done[k]}
          save();paint();
        });
      });
      document.getElementById('reset').addEventListener('click',function(){
        done={};save();boxes.forEach(function(b){b.checked=false});paint();
      });
      paint();
    })();
    </script>
    </body></html>
    HTML;
}

// ---------------------------------------------------------------- main

$opts = rhcParseArgs($argv);

if ($opts['help']) {
    fwrite(STDOUT, <<<'TXT'
    Reconcile Harvest time entries against their referenced ClickUp tasks.

      --from=YYYY-MM-DD   Start date (default: Jan 1 of current year)
      --to=YYYY-MM-DD     End date (default: today)
      --days=N            Shorthand for --from=N days ago
      --format=md|json|tsv|html  Output format (default: md)
      --refresh           Ignore the ClickUp task cache and refetch
      --all               Include full detail in JSON output
      --quiet             Suppress progress output on STDERR

      --config=PATH       Config file to use
      --cache-dir=PATH    Where to keep the ClickUp task cache

      --harvest-token=X   Harvest personal access token
      --harvest-account=X Harvest account id
      --harvest-user=X    Restrict to one Harvest user id
      --clickup-token=X   ClickUp API token

    CREDENTIALS
      Resolved in order, first non-empty wins: the CLI flags above, then
      environment variables, then the config file.

        HARVEST_ACCESS_TOKEN   (or HARVEST_TOKEN)
        HARVEST_ACCOUNT_ID
        HARVEST_USER_ID        (optional)
        CLICKUP_TOKEN          (or CLICKUP_API_TOKEN / CLICKUP_API_KEY)

      In the config file a secret may be given literally, or indirectly so that
      it never sits on disk. For any key K, the tool also accepts K_env
      (read this environment variable), K_file (read this file) and K_command
      (run this and take stdout) -- enough to source secrets from a password
      manager, keychain, CI secret, or mounted secret file:

        "clickup": { "token_command": "op read op://vault/clickup/token" }
        "harvest": { "token_file": "/run/secrets/harvest" }

    CONFIG FILE
      Searched in order: --config, $RHC_CONFIG,
      ./harvest-clickup-reconcile.json, ./.harvest-clickup-reconcile.json,
      $XDG_CONFIG_HOME/harvest-clickup-reconcile/config.json, then any
      config.json found walking up from the working directory.

        {
          "harvest":  { "account_id": "123456", "token_env": "HARVEST_TOKEN" },
          "clickup":  { "token_env": "CLICKUP_TOKEN" },
          "projects": {
            "Acme": { "clickup_tasks": ["*Acme*"], "harvest_client": "Acme Inc" }
          },
          "client_families": [["Parent Org", "Program Name"]]
        }

      Credentials alone are enough to run; project rules are what let the tool
      say which client a task belongs to, so without them every entry reports
      as unmapped.

    CACHE
      ClickUp task placement is cached in --cache-dir, else $RHC_CACHE_DIR,
      else $XDG_CACHE_HOME or ~/.cache, under harvest-clickup-reconcile/.

    EXIT CODES
      0  clean
      2  cross-client mismatches or suspect unmapped entries found
      1  error

    TXT);
    exit(0);
}

$config   = rhcLoadConfig($opts);
$cacheDir = rhcCacheDir($opts);

if ($config['harvest'] === []) {
    fwrite(
        STDERR,
        "No usable Harvest credentials.\n"
        . "Set HARVEST_ACCESS_TOKEN and HARVEST_ACCOUNT_ID, pass --harvest-token and\n"
        . "--harvest-account, or put them in a config file. See --help.\n"
    );
    exit(1);
}
if (!$opts['quiet'] && $config['projects'] === []) {
    fwrite(
        STDERR,
        "Note: no project rules loaded"
        . ($config['path'] === null ? ' (no config file found)' : " from {$config['path']}")
        . " — every entry will report as unmapped.\n"
    );
}

try {
    rhcLog($opts['quiet'], "Loading Harvest entries {$opts['from']} → {$opts['to']}…");
    $entries = rhcLoadHarvestEntries($config, $opts['from'], $opts['to'], $opts['quiet']);
    rhcLog($opts['quiet'], '  ' . count($entries) . ' entries');

    $ids = array_values(array_unique(array_filter(array_column($entries, 'clickup_id'))));
    $tasks = rhcResolveClickUpTasks($config, $ids, $opts['refresh'], $opts['quiet'], $cacheDir);

    $result = rhcAnalyze($entries, $tasks, rhcBuildRules($config), $config);
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}

echo match ($opts['format']) {
    'json' => json_encode(
        $opts['all'] ? $result + ['entries' => $entries] : $result,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    ) . "\n",
    'tsv'  => rhcRenderTsv($result),
    'html' => rhcRenderHtml($result, $entries, $opts['from'], $opts['to'], rhcHarvestBaseUri($config)),
    default => rhcRenderMarkdown($result, $entries, $opts['from'], $opts['to']),
};

// Non-zero exit when genuine cross-client conflation is present, so this can gate CI/cron.
exit($result['mismatch'] === [] && $result['unmapped_wrong'] === [] ? 0 : 2);
