# AST Metrics - bridge for PHP

This package allows to easily use [AST Metrics](https://github.com/Halleck45/ast-metrics/) in PHP projects.

## Installation

```bash
composer require halleck45/ast-metrics
```

## Usage

```bash
php vendor/bin/ast-metrics analyze --ci src
```

> [!NOTE]
> Please note this limitation: when used via this project, only the non-interactive mode is available.

## Updating the AST Metrics binary

```bash
php vendor/bin/ast-metrics self-update
```

## License

MIT. See [LICENSE](LICENSE) for more details.
