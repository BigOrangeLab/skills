# Installing gh

## macOS (Homebrew — recommended)

```bash
brew install gh
```

To upgrade:

```bash
brew upgrade gh
```

## Linux

**Debian/Ubuntu (apt):**

```bash
(type -p wget >/dev/null || (sudo apt update && sudo apt-get install wget -y)) \
&& sudo mkdir -p -m 755 /etc/apt/keyrings \
&& out=$(mktemp) && wget -nv -O$out https://cli.github.com/packages/githubcli-archive-keyring.gpg \
&& cat $out | sudo tee /etc/apt/keyrings/githubcli-archive-keyring.gpg > /dev/null \
&& sudo chmod go+r /etc/apt/keyrings/githubcli-archive-keyring.gpg \
&& echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/githubcli-archive-keyring.gpg] https://cli.github.com/packages stable main" | sudo tee /etc/apt/sources.list.d/github-cli.list > /dev/null \
&& sudo apt update \
&& sudo apt install gh -y
```

To upgrade: `sudo apt update && sudo apt install gh`

**Fedora/RHEL/CentOS (dnf):**

```bash
sudo dnf install 'dnf-command(config-manager)'
sudo dnf config-manager --add-repo https://cli.github.com/packages/rpm/gh-cli.repo
sudo dnf install gh --repo gh-cli
```

To upgrade: `sudo dnf update gh --repo gh-cli`

## Windows

**winget:**

```powershell
winget install --id GitHub.cli
```

**Scoop:**

```powershell
scoop install gh
```

## Verify installation

```bash
gh --version
```

## Further reading

- [Installation docs](https://github.com/cli/cli#installation)
- [All release downloads](https://github.com/cli/cli/releases/latest)
