# Installing Terminus

## Prerequisites

- PHP 8.2 or later: `php -v`
- PHP extensions: `php-xml`, `mbstring`, `xml`, `curl`, CLI
- Composer
- Git
- OpenSSH 7.8 or later: `ssh -V`

## macOS (Homebrew — recommended)

```bash
brew install openssh
brew install pantheon/pantheon/terminus
```

To upgrade:

```bash
brew upgrade pantheon/pantheon/terminus
```

## Linux (PHAR)

```bash
mkdir -p ~/terminus && cd ~/terminus
curl -L https://github.com/pantheon-systems/terminus/releases/latest/download/terminus.phar \
  --output terminus
chmod +x terminus
sudo ln -s ~/terminus/terminus /usr/local/bin/terminus
```

To upgrade, re-run the `curl` command above.

## Verify installation

```bash
terminus --version
```

## Further reading

- [Terminus Documentation](https://docs.pantheon.io/terminus)
- [Terminus on GitHub](https://github.com/pantheon-systems/terminus)
