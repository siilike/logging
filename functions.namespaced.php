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

use Siilike\Logging\Logger;

function trace0($message, ...$args)
{
	$a = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
	$GLOBALS['__siilikeLogger']->log($a['file'], $a['line'], Logger::TRACE0, $message, $args, false);
}

function trace($message, ...$args)
{
	$a = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
	$GLOBALS['__siilikeLogger']->log($a['file'], $a['line'], Logger::TRACE, $message, $args, false);
}

function debug($message, ...$args)
{
	$a = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
	$GLOBALS['__siilikeLogger']->log($a['file'], $a['line'], Logger::DEBUG, $message, $args, false);
}

function info($message, ...$args)
{
	$a = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
	$GLOBALS['__siilikeLogger']->log($a['file'], $a['line'], Logger::INFO, $message, $args, false);
}

function warn($message, ...$args)
{
	$a = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
	$GLOBALS['__siilikeLogger']->log($a['file'], $a['line'], Logger::WARN, $message, $args, false);
}

function error($message, ...$args)
{
	$a = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
	$GLOBALS['__siilikeLogger']->log($a['file'], $a['line'], Logger::ERROR, $message, $args, false);
}

function fatal($message, ...$args)
{
	$a = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
	$GLOBALS['__siilikeLogger']->log($a['file'], $a['line'], Logger::FATAL, $message, $args, false);
}

function ctrace0($message, ...$args)
{
	$a = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
	$GLOBALS['__siilikeLogger']->log($a['file'], $a['line'], Logger::TRACE0, $message, $args, true);
}

function ctrace($message, ...$args)
{
	$a = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
	$GLOBALS['__siilikeLogger']->log($a['file'], $a['line'], Logger::TRACE, $message, $args, true);
}

function cdebug($message, ...$args)
{
	$a = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
	$GLOBALS['__siilikeLogger']->log($a['file'], $a['line'], Logger::DEBUG, $message, $args, true);
}

function cinfo($message, ...$args)
{
	$a = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
	$GLOBALS['__siilikeLogger']->log($a['file'], $a['line'], Logger::INFO, $message, $args, true);
}

function cwarn($message, ...$args)
{
	$a = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
	$GLOBALS['__siilikeLogger']->log($a['file'], $a['line'], Logger::WARN, $message, $args, true);
}

function cerror($message, ...$args)
{
	$a = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
	$GLOBALS['__siilikeLogger']->log($a['file'], $a['line'], Logger::ERROR, $message, $args, true);
}

function cfatal($message, ...$args)
{
	$a = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
	$GLOBALS['__siilikeLogger']->log($a['file'], $a['line'], Logger::FATAL, $message, $args, true);
}
