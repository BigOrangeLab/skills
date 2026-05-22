#!/usr/bin/env node
/**
 * Reads every skill under skills/, parses the written_against frontmatter block,
 * fetches the current upstream version for each tool, and writes a JSON report
 * to stdout for the version-check workflow to consume.
 */

import { readFileSync, readdirSync, statSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const SKILLS_DIR = join(__dirname, '../../skills');
const GH_TOKEN = process.env.GITHUB_TOKEN;

// Maps a written_against key to a function that returns the current version string.
// Keys without an entry are reported as "no source" (e.g. local-by-wpengine, mysql).
const VERSION_SOURCES = {
  gh:                     () => githubLatest('cli/cli'),
  terminus:               () => githubLatest('pantheon-systems/terminus'),
  'wp-cli':               () => githubLatest('wp-cli/wp-cli'),
  phpcs:                  () => githubLatest('squizlabs/PHP_CodeSniffer'),
  wpcs:                   () => githubLatest('WordPress/WordPress-Coding-Standards'),
  wordpress:              () => wordpressLatest(),
  'wordpress-scripts':    () => npmLatest('@wordpress/scripts'),
  'wordpress-components': () => npmLatest('@wordpress/components'),
};

async function githubLatest(repo) {
  const res = await fetch(`https://api.github.com/repos/${repo}/releases/latest`, {
    headers: {
      Accept: 'application/vnd.github+json',
      'X-GitHub-Api-Version': '2022-11-28',
      ...(GH_TOKEN ? { Authorization: `Bearer ${GH_TOKEN}` } : {}),
    },
  });
  if (!res.ok) throw new Error(`GitHub API ${res.status} for ${repo}`);
  const { tag_name } = await res.json();
  return tag_name?.replace(/^v/, '') ?? null;
}

async function npmLatest(pkg) {
  const res = await fetch(`https://registry.npmjs.org/${encodeURIComponent(pkg)}/latest`);
  if (!res.ok) throw new Error(`npm ${res.status} for ${pkg}`);
  const { version } = await res.json();
  return version;
}

async function wordpressLatest() {
  const res = await fetch('https://api.wordpress.org/core/version-check/1.7/');
  if (!res.ok) throw new Error(`WordPress API ${res.status}`);
  const data = await res.json();
  return data.offers?.[0]?.current ?? null;
}

function parseWrittenAgainst(content) {
  const frontmatterMatch = content.match(/^---\n([\s\S]*?)\n---/);
  if (!frontmatterMatch) return {};

  const frontmatter = frontmatterMatch[1];
  const blockMatch = frontmatter.match(/written_against:\n((?:[ \t]+\S[^\n]*\n?)*)/);
  if (!blockMatch) return {};

  const result = {};
  for (const line of blockMatch[1].split('\n')) {
    const kv = line.match(/^\s+([^:]+):\s*"?([^"\n]+)"?/);
    if (kv) result[kv[1].trim()] = kv[2].trim();
  }
  return result;
}

function parseWrittenDate(content) {
  const m = content.match(/written:\s*"?(\d{4}-\d{2}-\d{2})"?/);
  return m ? m[1] : null;
}

const skillDirs = readdirSync(SKILLS_DIR).filter(name =>
  statSync(join(SKILLS_DIR, name)).isDirectory()
);

const rows = [];

for (const skillName of skillDirs.sort()) {
  const skillMdPath = join(SKILLS_DIR, skillName, 'SKILL.md');
  let content;
  try { content = readFileSync(skillMdPath, 'utf8'); } catch { continue; }

  const writtenAgainst = parseWrittenAgainst(content);
  if (!Object.keys(writtenAgainst).length) continue;

  const writtenDate = parseWrittenDate(content);

  for (const [tool, writtenVersion] of Object.entries(writtenAgainst)) {
    const fetcher = VERSION_SOURCES[tool];
    let current = null;
    let fetchError = null;

    if (fetcher) {
      try { current = await fetcher(); }
      catch (e) { fetchError = e.message; }
    }

    rows.push({
      skill: skillName,
      writtenDate,
      tool,
      writtenVersion,
      current: current ?? (fetchError ? `error: ${fetchError}` : 'no source'),
    });
  }
}

process.stdout.write(JSON.stringify(rows, null, 2) + '\n');
