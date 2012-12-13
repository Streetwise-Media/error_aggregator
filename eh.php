<?php

namespace Primal;

class ErrorHandler {
	
	public static function error_array($type, $level, $message, $file, $line, $trace)
	{
		return array(
			'date' => date('m/d/Y h:i:s a'),
			'referer' => (strtolower($_SERVER['HTTPS']) === 'on')
				? "https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']
				: "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'],
			'type' => $type,
			'level' => $level,
			'message' => $message,
			'file' => $file,
			'line' => $line,
			'trace' => $trace
		);
	}

	public function __construct($callback = null, $level = null) {

		$caught = false;

		set_exception_handler(function ($ex) use ($caught, $callback) {
			$caught = true;
			$callback(\Primal\ErrorHandler::error_array(
				'Unhandled '.get_class($ex),
				$ex->getCode(),
				$ex->getMessage(),
				$ex->getFile(),
				$ex->getLine(),
				$ex->getTrace()
			));
			error_log($ex->getMessage().' '.$ex->getFile().' '.$ex->getLine().' '.json_encode($ex->getTrace()));
			return false;
		});

		set_error_handler(function ($errno, $errstr, $errfile, $errline, $errcontext) use ($caught, $callback) {
			$caught = true;
			if (function_exists('xdebug_get_function_stack')) {
				$trace = array_reverse(xdebug_get_function_stack());
			} else {
				$trace = debug_backtrace();
			}
			array_shift($trace);
			$self = $this;

			$callback(\Primal\ErrorHandler::error_array(
				'RuntimeError',
				$errno,
				$errstr,
				$errfile,
				$errline,
				$errcontext,
				$trace
			));
			return false;
		}, $level);


		register_shutdown_function(function ()  use ($caught, $callback){
			//if error exists and an error wasn't previously caught and the error is allowed under error_reporting, handle it

			if (!($error = error_get_last())) {
				return;
			}

			$fatals = array(
			    E_USER_ERROR      => 'Fatal Error',
			    E_ERROR           => 'Fatal Error',
			    E_PARSE           => 'Parse Error', 
			    E_CORE_ERROR      => 'Core Error',
			    E_CORE_WARNING    => 'Core Warning',
			    E_COMPILE_ERROR   => 'Compile Error',
			    E_COMPILE_WARNING => 'Compile Warning'
			);

			if (isset($fatals[$error['type']])) {
				if (function_exists('xdebug_get_function_stack')) {
					$trace = array_reverse(xdebug_get_function_stack());
				} else {
					$trace = debug_backtrace();
				}
				array_shift($trace);
				$self = $this;

				$callback(\Primal\ErrorHandler::error_array(
					'Fatal Error',
					$error['type'],
					$error['message'],
					$error['file'],
					$error['line'],
					$trace
				));
				return false;
			}
		});
	}

	public static function Init($callback = null) {
		return new static($callback);
	}
}

define('LOGGER_LOG_FILE', '/home/brian/error.log');

\Primal\ErrorHandler::Init(function($error){
    $fh = fopen(LOGGER_LOG_FILE, 'a');
    if (!$fh) return;
    fwrite($fh, json_encode($error).',');
    fclose($fh);
    return false;
});