# siilike/logging

Convenient logging for PHP applications.

This package provides logging functions such as `info()` and `error()` as the
main logging API. The `Siilike\Logging\Logger` class configures the application
logger: output routing, levels, context, Sentry, and other behavior.

## Requirements

- PHP 8.2 or newer
- Composer autoloading for the `Siilike\Logging` classes
- `sentry/sentry` if Sentry capture or breadcrumbs are used
- `psr/log` if `LoggerAdapter` is used
- `symfony/console` if Symfony console output is used
- `ext-sockets` if remote syslog output is used

## Installation

Require the package with Composer:

```sh
composer require siilike/logging
```

If you use this package from a local checkout instead of an installed Composer
dependency, make sure your application autoloads the package root as the
`Siilike\\Logging\\` namespace.

## Basic Usage

Load one of the package function files, then initialize the application logger
once early in your request or command. Application code should log through the
functions.

```php
<?php

use Siilike\Logging\Logger;

$logger = Logger::create([
	'rootDirectory' => dirname(__DIR__),
	'level' => Logger::INFO,
	'outputStderr' => true,
]);

info('Processed order {}', $orderId);
```

Use one logger per application. Keep the returned `Logger`, or fetch it later
with `Logger::instance()`, when you need to adjust configuration afterwards:

```php
Logger::instance()?->setContext('uid', $userId);
Logger::instance()?->setLevel(Logger::WARN);
```

`Logger::instance()` returns `null` before `Logger::create()` has initialized
the application logger.

Direct calls to `Logger::log()` are low-level. Normal application logging
should use `trace()`, `debug()`, `info()`, `warn()`, `error()`, `fatal()`, or
their context-aware `c*` variants.

By default, the logger also defines these constants:

- `REQUEST_ID`
- `CLIENT_ID`
- `REQUEST_START`

Pass `'defineGlobals' => false` when creating short-lived or test logger
instances where defining global constants is not wanted.

## Logging Functions

The logging function files are not loaded by `composer.json` automatically.
Load one of them from this package after Composer's autoloader.

For global logging functions:

```php
use Siilike\Logging\Logger;

Logger::create([
	'outputStderr' => true,
]);

info('Application started');
warn('Slow request {}ms', $durationMs);
error('Unable to save user {}', $userId);
```

For namespaced logging functions:

```php
use Siilike\Logging\Logger;
use function Siilike\Logging\info;

Logger::create([
	'outputStderr' => true,
]);

info('Application started');
```

Available logging functions are:

- `trace0()`
- `trace()`
- `debug()`
- `info()`
- `warn()`
- `error()`
- `fatal()`

Each function also has a context-aware `c*` variant:

- `ctrace0()`
- `ctrace()`
- `cdebug()`
- `cinfo()`
- `cwarn()`
- `cerror()`
- `cfatal()`

The `c*` variants treat the last argument as per-call context:

```php
cinfo('Processed job {}', $jobId, [
	'queue' => 'imports',
	'attempt' => 2,
]);
```

In plain text, that context is appended to the message as compact JSON. In JSON
output, it is emitted as the record's `extra` value.

## Log Levels

The levels, from most verbose to least verbose, are:

```php
Logger::TRACE0;
Logger::TRACE;
Logger::DEBUG;
Logger::INFO;
Logger::WARN;
Logger::ERROR;
Logger::FATAL;
Logger::NONE;
```

The configured `level` is the minimum level that will be emitted. For example,
`Logger::INFO` logs `INFO`, `WARN`, `ERROR`, and `FATAL`, but skips `DEBUG`,
`TRACE`, and `TRACE0`.

If `level` is not configured explicitly, it is read from `LOGLEVEL`. When
`APP_ENV=development`, the fallback level is `TRACE0`; otherwise it is `TRACE`.

You can change levels after construction:

```php
$logger->setLevel(Logger::WARN);
$logger->setPathLevel(__DIR__ . '/noisy-module', Logger::ERROR);
```

## Output Sinks

Configure output with `output*` options:

| Option | Destination |
| --- | --- |
| `outputBuffer` | In-memory stream |
| `outputStderr` | `php://stderr` |
| `outputStdout` | `php://stdout` |
| `outputOutput` | `php://output` |
| `outputSymfony` | Symfony Console output for CLI apps |
| `outputSyslog` | Local syslog |
| `outputRemoteSyslog` | UDP remote syslog |
| `outputErrorLog` | File written with `error_log(..., 3, ...)` |
| `outputSentry` | Sentry breadcrumbs |

Each output option accepts:

- `false` or `null` to disable it
- `true` or `'plain'` for plain text
- `'json'` for newline-delimited JSON

Format strings are case-insensitive, so `'JSON'` works. Unknown strings fall
back to plain text.

`outputOutput` also supports `'html'`, which writes the plain-text line through
`htmlspecialchars()`.

CLI JSON output:

```php
$logger = Logger::create([
	'level' => Logger::INFO,
	'outputStdout' => 'json',
]);
```

Mixed plain-text and JSON output:

```php
$logger = Logger::create([
	'level' => Logger::DEBUG,
	'outputStderr' => true,
	'outputBuffer' => 'json',
]);
```

Error log file output:

```php
$logger = Logger::create([
	'outputErrorLog' => true,
	'logFile' => '/var/log/my-app/current',
]);
```

Use `logsDirectory` instead of `logFile` when you want the logger to create the
default dated file path:

```php
$logger = Logger::create([
	'outputErrorLog' => true,
	'logsDirectory' => '/var/log/my-app',
]);
```

Symfony Console CLI output:

```php
#!/usr/bin/env php
<?php

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

$router = require __DIR__ . '/app/app.php';

$kernel = $router->make(
	'Illuminate\Contracts\Console\Kernel'
);

$symfonyOutput = new ConsoleOutput();

exit($kernel->handle(new ArgvInput(), $symfonyOutput));
```

In code that runs after the `ConsoleOutput` exists, enable the Symfony sink:

```php
$logger = Logger::create([
	'outputSymfony' => true,
]);
```

Remote syslog output:

```php
$logger = Logger::create([
	'outputRemoteSyslog' => true,
	'syslogHost' => '127.0.0.1',
	'syslogPort' => '514',
	'syslogHostname' => gethostname() ?: 'localhost',
	'syslogProcess' => 'php-app',
]);
```

## Message Formatting

Messages use `{}` placeholders:

```php
info('User {} logged in from {}', $userId, $ipAddress);
```

Arguments are converted to strings. Arrays, `JsonSerializable` values, and
`stdClass` objects are encoded as JSON. Booleans and `null` become `true`,
`false`, and `null`.

If there are more arguments than placeholders, the remaining arguments are
appended to the message separated by spaces.

## Context

The logger always starts with these context keys:

- `id`: generated request id
- `client`: sanitized `$_SERVER['HTTP_X_CLIENT_ID']`, truncated to 10 characters
- `uri`: `$_SERVER['REQUEST_URI']`, when present during logger initialization

Add application context at construction time or later:

```php
$logger = Logger::create([
	'context' => [
		'app' => 'billing',
		'env' => 'production',
	],
	'outputStderr' => 'json',
]);

$logger->setContext('uid', $userId);
$logger->setContext([
	'tenant' => $tenantId,
	'region' => 'eu-north',
]);
```

Plain-text output renders global context in the bracketed context block:

```text
[2026-05-24 12:34:56] src/App.php:42 [INFO][id=... client=... uid=123] message
```

JSON output places global context in `context` and per-call context in `extra`.

## JSON Output

JSON output is newline-delimited and uses this record shape:

```json
{
	"timestamp": "2026-05-24T12:34:56+00:00",
	"level": "info",
	"file": "src/App.php",
	"line": 42,
	"context": {
		"id": "...",
		"client": "..."
	},
	"message": "Processed job job-1",
	"extra": {
		"queue": "imports"
	},
	"exceptions": []
}
```

When `rootDirectory` is configured, `file` is emitted relative to that root in
both plain-text and JSON output.

## Throwable Logging

You can log a throwable directly:

```php
try {
	$worker->run();
} catch (Throwable $e) {
	error($e);
}
```

Or include it as a placeholder argument:

```php
try {
	$worker->run();
} catch (Throwable $e) {
	error('Worker failed: {}', $e);
}
```

Plain-text output includes the throwable string form, including stack trace, on
following lines. JSON output includes normalized exception records:

```json
{
	"type": "RuntimeException",
	"message": "boom",
	"code": 123,
	"file": "/path/to/file.php",
	"line": 13,
	"trace": "..."
}
```

## Sentry

Initialize Sentry on the logger and set a Sentry threshold:

```php
$logger = Logger::create([
	'level' => Logger::INFO,
	'sentryLevel' => Logger::ERROR,
	'outputSentry' => true,
]);

$logger->initSentry([
	'dsn' => $_ENV['SENTRY_DSN'] ?? null,
]);
```

When `outputSentry` is enabled, log entries are added as Sentry breadcrumbs.
When a log level is at or above `sentryLevel`, the logger captures a Sentry
message or exception. If `sentryLevel` is not configured explicitly, it is read
from `SENTRY_LOGLEVEL`. The default is `WARN`, or `NONE` when
`APP_ENV=development`.

Logger context is copied into Sentry scope data.

## PSR-3 Adapter

Install `psr/log` and load the namespaced logging function file before using
`Siilike\Logging\LoggerAdapter`:

```php
use Psr\Log\LoggerInterface;
use Siilike\Logging\Logger;
use Siilike\Logging\LoggerAdapter;

Logger::create([
	'outputStderr' => true,
]);

$psrLogger = new LoggerAdapter();

function handle(LoggerInterface $logger): void
{
	$logger->warning('Cache miss', [
		'key' => 'user:123',
	]);
}

handle($psrLogger);
```

If the PSR context contains an `exception` key with a `Throwable`, it is logged
as a throwable and the remaining PSR context is emitted as per-call context.

## Extension Hooks

Subclass `Logger` to customize final output:

```php
use Sentry\State\Scope;
use Siilike\Logging\Logger;

final class AppLogger extends Logger
{
	protected function postprocessLogMessage(string &$logMsg, ?array $throwables): void
	{
		$logMsg = str_replace("\n", ' | ', $logMsg);
	}

	protected function postprocessLogRecord(array &$record, ?array $throwables): void
	{
		$record['service'] = 'billing';
	}

	protected function postprocessSentryScope(Scope $scope, ?array &$throwables): void
	{
		$scope->setTag('service', 'billing');
	}
}
```
