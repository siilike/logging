<?php
/*
 * Copyright 2015-present, Lauri Keel
 * All rights reserved.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Siilike\Logging;

class Logger
{
	protected const OUTPUT_FORMAT_PLAIN = 'plain';
	protected const OUTPUT_FORMAT_JSON = 'json';
	protected const OUTPUT_FORMAT_HTML = 'html';
	protected const OUTPUT_TYPES =
	[
		'buffer',
		'stderr',
		'stdout',
		'output',
		'symfony',
		'syslog',
		'remoteSyslog',
		'errorLog',
		'sentry',
	];

	const TRACE0 = 0;
	const TRACE = 1;
	const DEBUG = 2;
	const INFO = 3;
	const WARN = 4;
	const ERROR = 5;
	const FATAL = 6;
	const NONE = 99;

	protected static array $levelMap =
	[
		'TRACE0' => self::TRACE0,
		'TRACE' => self::TRACE,
		'DEBUG' => self::DEBUG,
		'INFO' => self::INFO,
		'WARN' => self::WARN,
		'ERROR' => self::ERROR,
		'FATAL' => self::FATAL,
		'NONE' => self::NONE,
	];

	protected static array $levelMap0 =
	[
		self::TRACE0 => 'TRACE0',
		self::TRACE => 'TRACE',
		self::DEBUG => 'DEBUG',
		self::INFO => 'INFO',
		self::WARN => 'WARN',
		self::ERROR => 'ERROR',
		self::FATAL => 'FATAL',
		self::NONE => 'NONE',
	];

	protected static array $levelToSyslog =
	[
		self::TRACE0 => 7,
		self::TRACE => 7,
		self::DEBUG => 7,
		self::INFO => 6,
		self::WARN => 4,
		self::ERROR => 3,
		self::FATAL => 2,
	];

	protected static array $levelToSentry =
	[
		self::TRACE0 => 'debug', // \Sentry\Breadcrumb::LEVEL_DEBUG
		self::TRACE => 'debug', // \Sentry\Breadcrumb::LEVEL_DEBUG
		self::DEBUG => 'debug', // \Sentry\Breadcrumb::LEVEL_DEBUG
		self::INFO => 'info', // \Sentry\Breadcrumb::LEVEL_INFO
		self::WARN => 'warning', // \Sentry\Breadcrumb::LEVEL_WARNING
		self::ERROR => 'error', // \Sentry\Breadcrumb::LEVEL_ERROR
		self::FATAL => 'fatal', // \Sentry\Breadcrumb::LEVEL_FATAL
	];

	protected static array $levelToSentryType =
	[
		self::TRACE0 => 'default', // \Sentry\Breadcrumb::TYPE_DEFAULT,
		self::TRACE => 'default', // \Sentry\Breadcrumb::TYPE_DEFAULT,
		self::DEBUG => 'default', // \Sentry\Breadcrumb::TYPE_DEFAULT,
		self::INFO => 'default', // \Sentry\Breadcrumb::TYPE_DEFAULT,
		self::WARN => 'default', // \Sentry\Breadcrumb::TYPE_DEFAULT,
		self::ERROR => 'error', // \Sentry\Breadcrumb::TYPE_ERROR,
		self::FATAL => 'error', // \Sentry\Breadcrumb::TYPE_ERROR,
	];

	protected static ?Logger $instance = null;

	protected string $requestId;
	protected string $clientId;
	protected int $requestStart;

	protected string $syslogHost = '127.0.0.1';
	protected string $syslogPort = '514';
	protected string $syslogHostname = 'localhost';
	protected string $syslogProcess = 'php-fpm';

	protected ?string $contextStr = null;
	protected array $context = [];

	protected array $levels = [];
	protected ?int $level = null;
	protected ?int $sentryLevel = null;

	protected ?\Sentry\State\HubInterface $sentry = null;

	protected ?string $rootDirectory = null;

	protected bool $outputBuffer = false;
	protected string $outputBufferFormat = self::OUTPUT_FORMAT_PLAIN;
	protected bool $outputStderr = false;
	protected string $outputStderrFormat = self::OUTPUT_FORMAT_PLAIN;
	protected bool $outputStdout = false;
	protected string $outputStdoutFormat = self::OUTPUT_FORMAT_PLAIN;
	protected $outputOutput = false;
	protected string $outputOutputFormat = self::OUTPUT_FORMAT_PLAIN;
	protected bool $outputSymfony = false;
	protected string $outputSymfonyFormat = self::OUTPUT_FORMAT_PLAIN;
	protected bool $outputSentry = false;
	protected string $outputSentryFormat = self::OUTPUT_FORMAT_PLAIN;

	protected bool $outputSyslog = false;
	protected string $outputSyslogFormat = self::OUTPUT_FORMAT_PLAIN;
	protected bool $outputRemoteSyslog = false;
	protected string $outputRemoteSyslogFormat = self::OUTPUT_FORMAT_PLAIN;
	protected bool $outputErrorLog = false;
	protected string $outputErrorLogFormat = self::OUTPUT_FORMAT_PLAIN;
	protected array $enabledFormats =
	[
		self::OUTPUT_FORMAT_PLAIN => false,
		self::OUTPUT_FORMAT_JSON => false,
		self::OUTPUT_FORMAT_HTML => false,
	];

	protected $buffer = null;
	protected $stdout = null;
	protected $stderr = null;
	protected $output = null;
	protected $symfony = null;
	protected $logsDirectory = null;
	protected $logFile = null;

	public function __construct(array $opts)
	{
		foreach(static::OUTPUT_TYPES as $a)
		{
			$k = 'output'.\ucfirst($a);

			if(\array_key_exists($k, $opts))
			{
				$this->configureOutput($a, $opts[$k]);
			}
		}

		foreach(
		[
			'rootDirectory',
			'syslogHost',
			'syslogPort',
			'syslogHostname',
			'syslogProcess',
			'level',
			'sentryLevel',
			'logsDirectory',
			'logFile',
			'sentry',
		] as $a)
		{
			if(\array_key_exists($a, $opts))
			{
				$this->$a = $opts[$a];
			}
		}

		$this->requestId = \time() . \substr(\str_replace([ "/", "+", "=" ], '1', \base64_encode(\random_bytes(5))), 0, 5);
		$this->clientId = \substr(\preg_replace('#[^a-zA-Z0-9]+#', '', $_SERVER['HTTP_X_CLIENT_ID'] ?? ''), 0, 10);
		$this->requestStart = (int)(\microtime(true)*1000);

		$this->level = $this->level ?? (static::$levelMap[$this->getEnvOrConst('LOGLEVEL') ? \strtoupper($this->getEnvOrConst('LOGLEVEL')) : ($this->getEnvOrConst('APP_ENV') === 'development' ? 'TRACE0' : 'TRACE')]);
		$this->sentryLevel = $this->sentryLevel ?? (static::$levelMap[$this->getEnvOrConst('SENTRY_LOGLEVEL') ? \strtoupper($this->getEnvOrConst('SENTRY_LOGLEVEL')) : ($this->getEnvOrConst('APP_ENV') === 'development' ? 'NONE' : 'WARN')]);

		$this->rootDirectory = $this->rootDirectory ?? (\defined('ROOT') ? ROOT : (\defined('ABSPATH') ? ABSPATH : ''));

		if(!str_ends_with($this->rootDirectory, "/"))
		{
			$this->rootDirectory .= '/';
		}

		if($this->outputErrorLog)
		{
			$this->logsDirectory = $this->logsDirectory ?? (($this->rootDirectory ?: __DIR__) . '/logs/');
			$this->logFile = $this->logFile ?? ($this->logsDirectory . '/' . date('Y-m-d') . '.log');
		}

		$this->init();

		if(!empty($opts['context']))
		{
			$this->setContext($opts['context']);
		}

		if(($opts['defineGlobals'] ?? true) !== false)
		{
			$this->defineGlobals();
		}
	}

	public static function create(array $opts = []): self
	{
		$instance = new static($opts);

		static::$instance = $instance;

		$GLOBALS['__siilikeLogger'] = $instance;

		return $instance;
	}

	public static function instance(): ?self
	{
		return static::$instance;
	}

	protected function init(): void
	{
		if($this->outputBuffer)
		{
			$this->setOutputBuffer($this->outputBufferFormat);
		}

		if($this->outputStderr)
		{
			$this->setOutputStderr($this->outputStderrFormat);
		}

		if($this->outputStdout)
		{
			$this->stdout = \fopen('php://stdout', 'a');
		}

		if($this->outputOutput)
		{
			$this->setOutputOutput($this->outputOutputFormat);
		}

		if($this->outputSymfony)
		{
			$this->symfony = $GLOBALS['symfonyOutput'];
		}

		if($this->outputSyslog)
		{
			\openlog($this->syslogProcess, LOG_PID, LOG_LOCAL0);
		}

		$this->setContext(
		[
			'id' => $this->requestId,
			'client' => $this->clientId,
		]);

		if(isset($_SERVER['REQUEST_URI']))
		{
			$this->setContext('uri', $_SERVER['REQUEST_URI']);
		}
	}

	public function initSentry(array $opts): \Sentry\State\HubInterface
	{
		\Sentry\init($opts);

		$this->sentry = \Sentry\SentrySdk::getCurrentHub();

		$this->getContextAsString(true);

		return \Sentry\SentrySdk::getCurrentHub();
	}

	public function defineGlobals(): void
	{
		define('REQUEST_ID', $this->requestId);
		define('CLIENT_ID', $this->clientId);
		define('REQUEST_START', $this->requestStart);
	}

	public function log(string $file, int $line, int $level, mixed $message, array $args, bool $withCtx = false): void
	{
		if(($this->levels[$file] ?? $this->level) > $level)
		{
			return;
		}

		$levelName = static::$levelMap0[$level];
		$ctx = !empty($withCtx) ? \array_pop($args) : null;

		$m = $message;
		$throwables = null;

		if($m instanceof \Throwable)
		{
			$throwables = [ $m ];
			$m = $m->getMessage();
		}
		else if(!empty($args))
		{
			$m = '';
			$o = 0;
			$i = 0;
			$max = \count($args);

			do
			{
				$a = $args[$i];

				if($a instanceof \MongoDB\BSON\ObjectId)
				{
					$a = (string)$a;
				}
				else if(\is_array($a) || $a instanceof \JsonSerializable || $a instanceof \stdClass)
				{
					$a = \json_encode($a, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_ERROR_RECURSION | JSON_ERROR_INF_OR_NAN | JSON_ERROR_UNSUPPORTED_TYPE);
				}
				else if($a === true)
				{
					$a = 'true';
				}
				else if($a === false)
				{
					$a = 'false';
				}
				else if($a === null)
				{
					$a = 'null';
				}
				else if($a instanceof \Throwable)
				{
					if(empty($throwables))
					{
						$throwables = [];
					}

					$throwables[] = $a;

					$a = $a->getMessage();
				}
				else
				{
					$a = $this->convertArgumentToString($a);
				}

				$pos = \strpos($message, '{}', $o);

				if($pos === false)
				{
					$m .= ' '.$a;
				}
				else
				{
					$m .= \substr($message, $o, $pos-$o) . $a;
					$o = $pos+2;
				}

				$i++;
			}
			while($i < $max);

			if(!empty($args))
			{
				$m .= \substr($message, $o);
			}
		}

		if(!\is_string($m))
		{
			$m = $this->convertArgumentToString($m) ?? '';
		}

		$file0 = ltrim($file, $this->rootDirectory);

		$plainValue = null;
		$jsonValue = null;

		if($this->enabledFormats[self::OUTPUT_FORMAT_PLAIN] || $this->enabledFormats[self::OUTPUT_FORMAT_HTML])
		{
			$full = $m;

			if(!empty($ctx))
			{
				$full .= ' '.\json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_ERROR_RECURSION | JSON_ERROR_INF_OR_NAN | JSON_ERROR_UNSUPPORTED_TYPE);
			}

			if(!empty($throwables))
			{
				foreach($throwables as $a)
				{
					$full .= "\n".$a;
				}
			}

			$this->postprocessLogMessage($full, $throwables);

			$plainValue = '['.date('Y-m-d H:i:s').'] '.$file0.":".$line.' ['.$levelName.']['.$this->getContextAsString().'] '.$full;
		}

		if($this->enabledFormats[self::OUTPUT_FORMAT_JSON])
		{
			$record =
			[
				'timestamp' => date(DATE_ATOM),
				'level' => \strtolower($levelName),
				'file' => $file0,
				'line' => $line,
				'context' => $this->context,
				'message' => $m,
				'extra' => $ctx,
				'exceptions' => $this->normalizeThrowables($throwables),
			];

			$this->postprocessLogRecord($record, $throwables);

			$jsonValue = $this->encodeJson($record);
		}

		$this->doLog0($level, $levelName, $plainValue, $jsonValue);

		if($level >= $this->sentryLevel)
		{
			$this->captureSentry($line, $file, $levelName, $m, $throwables, $ctx);
		}
	}

	protected function doLog0(int $level, string $levelName, ?string $plainValue, ?string $jsonValue): void
	{
		if($this->outputBuffer)
		{
			\fwrite($this->buffer, $this->getFormattedValue($this->outputBufferFormat, $plainValue, $jsonValue)."\n");
		}

		if($this->outputStderr)
		{
			\fwrite($this->stderr, $this->getFormattedValue($this->outputStderrFormat, $plainValue, $jsonValue)."\n");
		}

		if($this->outputStdout)
		{
			\fwrite($this->stdout, $this->getFormattedValue($this->outputStdoutFormat, $plainValue, $jsonValue)."\n");
		}

		if($this->outputOutput)
		{
			\fwrite($this->output, $this->getFormattedValue($this->outputOutputFormat, $plainValue, $jsonValue)."\n");
		}

		if($this->outputSymfony)
		{
			$this->symfony->write($this->getFormattedValue($this->outputSymfonyFormat, $plainValue, $jsonValue)."\n", false, \Symfony\Component\Console\Output\Output::OUTPUT_RAW);
		}

		if($this->outputSyslog)
		{
			\syslog(LOG_LOCAL0, $this->getFormattedValue($this->outputSyslogFormat, $plainValue, $jsonValue));
		}

		if($this->outputRemoteSyslog)
		{
			$this->sendRemoteSyslog($this->getFormattedValue($this->outputRemoteSyslogFormat, $plainValue, $jsonValue), static::$levelToSyslog[$level]);
		}

		if($this->outputErrorLog)
		{
			\error_log($this->getFormattedValue($this->outputErrorLogFormat, $plainValue, $jsonValue)."\n", 3, $this->logFile);
		}

		if($this->outputSentry)
		{
			$this->sentry->addBreadcrumb(new \Sentry\Breadcrumb(
				static::$levelToSentry[$level],
				static::$levelToSentryType[$level],
				$levelName,
				$this->getFormattedValue($this->outputSentryFormat, $plainValue, $jsonValue)
			));
		}
	}

	protected function getFormattedValue(string $format, ?string $plainValue, ?string $jsonValue): string
	{
		return match($format)
		{
			self::OUTPUT_FORMAT_PLAIN => $plainValue,
			self::OUTPUT_FORMAT_JSON => $jsonValue,
			self::OUTPUT_FORMAT_HTML => htmlspecialchars($plainValue),
			default => null,
		};
	}

	protected function encodeJson(mixed $value): string
	{
		return (string)\json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_ERROR_RECURSION | JSON_ERROR_INF_OR_NAN | JSON_ERROR_UNSUPPORTED_TYPE);
	}

	protected function normalizeThrowables(?array $throwables): array
	{
		if(empty($throwables))
		{
			return [];
		}

		return \array_map(fn(\Throwable $a) =>
		[
			'type' => $a::class,
			'message' => $a->getMessage(),
			'code' => $a->getCode(),
			'file' => $a->getFile(),
			'line' => $a->getLine(),
			'trace' => $a->getTraceAsString(),
		], $throwables);
	}

	protected function convertArgumentToString(mixed $a): ?string
	{
		try
		{
			$a = (string)$a;
		}
		catch(\Throwable $e)
		{
			try
			{
				$a = $this->encodeJson($a);
			}
			catch(\Throwable $ee)
			{
				error("Unable to encode object: {}", $ee);
			}
		}

		return $a;
	}

	protected function postprocessLogMessage(string &$logMsg, ?array $throwables): void
	{
		//
	}

	protected function postprocessLogRecord(array &$record, ?array $throwables): void
	{
		//
	}

	public function captureSentry(?int $line, ?string $file, string $levelName, ?string $message, ?array $throwables, ?array $ctx = []): void
	{
		if(!$this->sentry)
		{
			return;
		}

		$this->sentry->withScope(function(\Sentry\State\Scope $scope) use($line, $file, $levelName, $message, $throwables, $ctx)
		{
			if($message !== null)
			{
				$scope->setExtra('message', $message);
			}

			$scope->setExtra('file', $file);
			$scope->setExtra('line', $line);

			$scope->setTags($this->context);
			$scope->setLevel($levelName == "WARN" ? \Sentry\Severity::warning() : \Sentry\Severity::error());

			if(!empty($throwables) && count($throwables) > 1)
			{
				$scope->setExtra('errors', \array_map(fn($a) => (string)$a, $throwables));
			}

			if(!empty($ctx))
			{
				$scope->setExtras($ctx);
			}

			$this->postprocessSentryScope($scope, $throwables);

			if(!empty($throwables))
			{
				if($message === null)
				{
					$error = $throwables[0];
				}
				else
				{
					$error = new \Exception($message, 0, $throwables[0]);
				}

				$this->sentry->captureException($error);
			}
			else
			{
				$this->sentry->captureMessage($message);
			}
		});
	}

	protected function postprocessSentryScope(\Sentry\State\Scope $scope, ?array &$throwables): void
	{
		//
	}

	public function enableImplicitFlush(): void
	{
		while(ob_get_level()) ob_end_clean();
		ob_implicit_flush(1);

		ini_set('implicit_flush', 1);
		ini_set('output_buffering', 0);
		ini_set('zlib.output_compression', 0);

		header('Content-Encoding: none');
	}

	public function setOutputBuffer(bool|string $outputBuffer): void
	{
		$this->configureOutput('buffer', $outputBuffer);

		if($this->outputBuffer)
		{
			$this->buffer = \fopen('php://memory', 'w+');
		}
		else if($this->buffer)
		{
			@\fclose($this->buffer);

			$this->buffer = null;
		}
	}

	public function setOutputOutput(bool|string $outputOutput): void
	{
		$this->configureOutput('output', $outputOutput);

		if($this->outputOutput && !$this->output)
		{
			$this->output = \fopen('php://output', 'a');
		}
	}

	public function setOutputStderr(bool|string $outputStderr): void
	{
		$this->configureOutput('stderr', $outputStderr);

		if($this->outputStderr && !$this->stderr)
		{
			$this->stderr = \fopen('php://stderr', 'a');
		}
	}

	protected function configureOutput(string $name, mixed $value): void
	{
		$enabledProperty = 'output'.\ucfirst($name);
		$formatProperty = $enabledProperty.'Format';
		$allowedFormats =
		[
			self::OUTPUT_FORMAT_PLAIN,
			self::OUTPUT_FORMAT_JSON,
		];

		if($enabledProperty === 'outputOutput')
		{
			$allowedFormats[] = self::OUTPUT_FORMAT_HTML;
		}

		if($value === false || $value === null)
		{
			$this->$enabledProperty = false;
			$this->$formatProperty = self::OUTPUT_FORMAT_PLAIN;
			$this->refreshEnabledFormats();

			return;
		}

		$format = self::OUTPUT_FORMAT_PLAIN;

		if(\is_string($value))
		{
			$format = \strtolower($value);
		}

		if(!\in_array($format, $allowedFormats, true))
		{
			$format = self::OUTPUT_FORMAT_PLAIN;
		}

		$this->$enabledProperty = true;
		$this->$formatProperty = $format;
		$this->refreshEnabledFormats();
	}

	protected function refreshEnabledFormats(): void
	{
		$this->enabledFormats =
		[
			self::OUTPUT_FORMAT_PLAIN => false,
			self::OUTPUT_FORMAT_JSON => false,
			self::OUTPUT_FORMAT_HTML => false,
		];

		foreach(static::OUTPUT_TYPES as $name)
		{
			$enabledProperty = 'output'.\ucfirst($name);
			$formatProperty = $enabledProperty.'Format';

			if($this->$enabledProperty)
			{
				$this->enabledFormats[$this->$formatProperty] = true;
			}
		}
	}

	public function setContext(array|string $k, mixed $v = null): void
	{
		if(\is_array($k))
		{
			$this->context = \array_merge($this->context, $k);
		}
		else
		{
			$this->context[$k] = $v;
		}

		$this->contextStr = null;
	}

	public function setLevel(int $level): void
	{
		$this->level = $level;
	}

	public function setPathLevel(string $path, int $level): void
	{
		if(\is_dir($path))
		{
			foreach(new \DirectoryIterator($path) as $a)
			{
				if(!$a->isDot())
				{
					$this->setPathLevel($a->getPathname(), $level);
				}
			}
		}
		else
		{
			$this->levels[$path] = $level;
		}
	}

	public function setSentryLevel(int $sentryLevel): void
	{
		$this->sentryLevel = $sentryLevel;
	}

	protected function getContextAsString(bool $forceRebuild = false): string
	{
		if($forceRebuild || $this->contextStr === null)
		{
			$contextStr = '';
			foreach($this->context as $a => $b)
			{
				$contextStr .= "$a=$b ";
			}

			if($this->sentry)
			{
				$this->sentry->configureScope(function(\Sentry\State\Scope $scope)
				{
					$scope->setUser(
					[
						'id' => $this->context['uid'] ?? null,
						'ip_address' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
					]);

					$scope->setExtras($this->context);
				});
			}

			$this->contextStr = \trim($contextStr);
		}

		return $this->contextStr;
	}

	protected function sendRemoteSyslog(string $message, int $severity = 6): void
	{
		static $socket;

		if(!$socket)
		{
			$socket = \socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		}

		$msg = "<".((16*8)+$severity).">" . date('M d H:i:s') . ' ' . $this->syslogHostname . ' '.$this->syslogProcess.': ' . $message;

		\socket_sendto($socket, $msg, strlen($msg), 0, $this->syslogHost, $this->syslogPort);
	}

	protected function getEnvOrConst(string $const): mixed
	{
		if(function_exists('\env'))
		{
			return \env($const);
		}

		return \getenv($const) ?: (defined($const) ? constant($const) : null);
	}

	public function getLevel(): int
	{
		return $this->level;
	}

	public function getSentryLevel(): int
	{
		return $this->sentryLevel;
	}

	public function getBuffer(): mixed
	{
		return $this->buffer;
	}

	public function getClientId(): string
	{
		return $this->clientId;
	}

	public function getRequestId(): string
	{
		return $this->requestId;
	}

	public function getRequestStart(): int
	{
		return $this->requestStart;
	}
}
