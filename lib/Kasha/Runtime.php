<?php

namespace Kasha;

use Temple\Util;
use Kasha\Templar\TextProcessor;

class Runtime extends Kasha\Core\Runtime
{
	private $warnings = array();

	/**
	 * Registers warning
	 *
	 * @param string $warning
	 */
	public function addWarning($warning)
	{
		$debugInfo = debug_backtrace();
		if (count($debugInfo) > 0) {
			$caller = $debugInfo[0]['class'] . ':' . $debugInfo[0]['function'];
			$codeLine = $debugInfo[0]['file'] . ':' . $debugInfo[0]['line'];
			$warning = 'Error in ' . $caller . ' at ' . $codeLine . ' with warning:' . $warning;
		}
		$this->warnings[] = $warning;
	}

	/**
	 * Renders ajax flash message (informational or error) and cleans up session
	 *
	 * @return string
	 */
	public function renderAjaxFlashMessage()
	{
		$output = '';

		if (array_key_exists('ajaxFlash', $_SESSION)) {
			$output .= TextProcessor::doTemplate('framework', '_ajaxFlash', array('flash' => $_SESSION['ajaxFlash']));
			unset($_SESSION['ajaxFlash']);
		}
		if (array_key_exists('ajaxError', $_SESSION)) {
			$output .= TextProcessor::doTemplate('framework', '_ajaxError', array('error' => $_SESSION['ajaxError']));
			unset($_SESSION['ajaxError']);
		}

		return $output;
	}

	/**
	 * Renders page flash message (informational or error) and cleans up session
	 *
	 * @return string
	 */
	public function renderFlashMessage()
	{
		$flash = Util::lavnn('flash', $_SESSION, '');
		$error = Util::lavnn('error', $_SESSION, '');
		$output = '';
		if ($flash != '') {
			$output .= TextProcessor::doTemplate('framework', '_flash', array('flash' => $flash));
			unset($_SESSION['flash']);
		}
		if ($error != '') {
			$output .= TextProcessor::doTemplate('framework', '_error', array('error' => $error));
			unset($_SESSION['error']);
		}

		return $output;
	}

	//region Shortcuts for database methods
	public static function s2r(
		$moduleName,
		$templateName,
		$params = array()
	) {
		return Database::getInstance()->sql2row($moduleName, $templateName, $params);
	}

	public static function s2a(
		$moduleName,
		$templateName,
		$params = array()
	) {
		return Database::getInstance()->sql2array($moduleName, $templateName, $params);
	}

	public static function spreview(
		$moduleName,
		$templateName,
		$params = array()
	) {
		return Database::getInstance()->preview($moduleName, $templateName, $params);
	}

	public static function sinsert(
		$moduleName,
		$templateName,
		$params = array()
	) {
		Database::getInstance()->insert($moduleName, $templateName, $params);
	}

	public static function sdelete(
		$moduleName,
		$templateName,
		$params = array()
	) {
		Database::getInstance()->delete($moduleName, $templateName, $params);
	}

	public static function supdate(
		$moduleName,
		$templateName,
		$params = array()
	) {
		Database::getInstance()->update($moduleName, $templateName, $params);
	}

//endregion

//region Shortcuts for text processing
	public function dot(
		$moduleName,
		$templateName,
		$params = array()
	) {
		return TextProcessor::doTemplate($moduleName, $templateName, $params);
	}

	public function loopt(
		$moduleName,
		$templateName,
		$rows = array()
	) {
		return TextProcessor::loopTemplate($moduleName, $templateName, $rows);
	}

//endregion

	/**
	 * Send accumulated warnings to site administrator by email (if allowed in the config)
	 */
	private function sendWarnings($channel = '')
	{
		if ($this->muted) {
			$channel = 'none';
		} else {
			$env = $this->config['ENV'];
			if ($channel == '') {
				$channel = $this->config['envConfig'][$env]['sendWarnings'];
			}
		}
		if (count($this->warnings) > 0) {
			switch ($channel) {
				case 'dump':
					d($this->warnings);
					break;
				case 'hidden':
					dh($this->warnings);
					break;
				case 'email':
					// TODO prepare pretty mail message for warnings
					Runtime::mail(
						$this->config['adminEmail'],
						'warnings',
						' warnings: ' . print_r($this->warnings, 1) .
							' server: ' . print_r($_SERVER, 1) .
							' request: ' . print_r($_REQUEST, 1)
					);
					break;
				case 'none':
					// fall through to default
				default:
					// do nothing
					break;
			}
		}
	}


}
