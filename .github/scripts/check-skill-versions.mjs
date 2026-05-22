#!/usr/bin/env node
/**
 * Reads every skill under skills/, parses the written_against frontmatter block,
 * fetches the current upstream version for each tool, and writes a JSON report
 * to stdout for the version-check workflow to consume.
 */

import { readFileSync, readdirSync, statSync } from "fs";
import { join, dirname } from "path";
import { fileURLToPath } from "url";

const __dirname = dirname(fileURLToPath(import.meta.url));
const SKILLS_DIR = join(__dirname, "../../skills");
const GH_TOKEN = process.env.GITHUB_TOKEN;
const versionCache = new Map();

// Maps a written_against key to a function that returns the current version string.
// Keys without an entry are reported as "no source" (e.g. local-by-wpengine, mysql).
const VERSION_SOURCES = {
  gh: () => githubLatest("cli/cli"),
  terminus: () => githubLatest("pantheon-systems/terminus"),
  "wp-cli": () => githubLatest("wp-cli/wp-cli"),
  phpcs: () => githubLatest("squizlabs/PHP_CodeSniffer"),
  wpcs: () => githubLatest("WordPress/WordPress-Coding-Standards"),
  wordpress: () => wordpressLatest(),
  "wordpress-scripts": () => npmLatest("@wordpress/scripts"),
  "wordpress-components": () => npmLatest("@wordpress/components"),
};

async function githubLatest(repo) {
  const res = await fetch(
    `https://api.github.com/repos/${repo}/releases/latest`,
    {
      headers: {
        Accept: "application/vnd.github+json",
        "X-GitHub-Api-Version": "2022-11-28",
        ...(GH_TOKEN ? { Authorization: `Bearer ${GH_TOKEN}` } : {}),
      },
    },
  );
  if (!res.ok) throw new Error(`GitHub API ${res.status} for ${repo}`);
  const { tag_name } = await res.json();
  return tag_name?.replace(/^v/, "") ?? null;
}

async function npmLatest(pkg) {
  const res = await fetch(
    `https://registry.npmjs.org/${encodeURIComponent(pkg)}/latest`,
  );
  if (!res.ok) throw new Error(`npm ${res.status} for ${pkg}`);
  const { version } = await res.json();
  return version;
}

async function wordpressLatest() {
  const res = await fetch("https://api.wordpress.org/core/version-check/1.7/");
  if (!res.ok) throw new Error(`WordPress API ${res.status}`);
  const data = await res.json();
  return data.offers?.[0]?.current ?? null;
}

function parseFrontmatter(content) {
  const frontmatterMatch = content.match(/^---\r?\n([\s\S]*?)\r?\n---/);
  return frontmatterMatch?.[1] ?? null;
}

function parseWrittenAgainst(frontmatter) {
  if (!frontmatter) return {};

  const blockMatch = frontmatter.match(
    /(?:^|\n)\s*written_against:\r?\n((?:[ \t]+\S[^\n\r]*(?:\r?\n|$))*)/,
  );
  if (!blockMatch) return {};

  const result = {};
  for (const line of blockMatch[1].split(/\r?\n/)) {
    const kv = line.match(/^\s+([^:]+):\s*"?([^"\n]+)"?/);
    if (kv) result[kv[1].trim()] = kv[2].trim();
  }
  return result;
}

function parseWrittenDate(frontmatter) {
  if (!frontmatter) return null;

  const m = frontmatter.match(/(?:^|\n)\s*written:\s*"?(\d{4}-\d{2}-\d{2})"?/);
  return m ? m[1] : null;
}

function getCurrentVersion(tool) {
  if (versionCache.has(tool)) {
    return versionCache.get(tool);
  }

  const fetcher = VERSION_SOURCES[tool];
  const request = (async () => {
    if (!fetcher) {
      return { current: "no source" };
    }

    try {
      const current = await fetcher();
      return { current: current ?? "no source" };
    } catch (error) {
      return { current: `error: ${error.message}` };
    }
  })();

  versionCache.set(tool, request);
  return request;
}

const skillDirs = readdirSync(SKILLS_DIR).filter((name) =>
  statSync(join(SKILLS_DIR, name)).isDirectory(),
);

const rows = [];

for (const skillName of skillDirs.sort()) {
  const skillMdPath = join(SKILLS_DIR, skillName, "SKILL.md");
  let content;
  try {
    content = readFileSync(skillMdPath, "utf8");
  } catch {
    continue;
  }

  const frontmatter = parseFrontmatter(content);
  const writtenAgainst = parseWrittenAgainst(frontmatter);
  if (!Object.keys(writtenAgainst).length) continue;

  const writtenDate = parseWrittenDate(frontmatter);

  for (const [tool, writtenVersion] of Object.entries(writtenAgainst)) {
    const { current } = await getCurrentVersion(tool);

    rows.push({
      skill: skillName,
      writtenDate,
      tool,
      writtenVersion,
      current,
    });
  }
}

process.stdout.write(JSON.stringify(rows, null, 2) + "\n");
