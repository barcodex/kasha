<?php

namespace Kasha;

use Temple\Util;
use Kasha\Templar\TextProcessor;
use Kasha\Core\Config;
use Kasha\Core\Runtime as BaseRuntime;
use Kasha\Profiler\Profiler;

class Runtime extends BaseRuntime
{
	private $allCurrencies = array();
	private $allSiteCurrencies = array();

	private $allLanguages = array();
	private $allSiteLanguages = array();
	private $translatableLanguages = array();

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

	public function getPageRenderer()
	{
		return new PageRenderer();
	}

	protected function routeFormAction($f, $fileName) {
		$this->addProfilerMessage("Started to include action $f");
		parent::routeFormAction($f, $fileName);
		$this->addProfilerMessage("Finished to include action $f");
	}

	protected function routeInlineAction($i, $fileName) {
		$this->addProfilerMessage("Started to include action $i");
		parent::routeInlineAction($i, $fileName);
		$this->addProfilerMessage("Finished to include action $i");
	}

	protected function routeJsonAction($json) {
		$this->addProfilerMessage("Started to include action $json");
		parent::routeJsonAction($json);
		$this->addProfilerMessage("Finished to include action $json");
	}

	protected function routePdfAction($pdf, $fileName) {
		$this->addProfilerMessage("Started to include action $pdf");
		parent::routePdfAction($pdf, $fileName);
		$this->addProfilerMessage("Finished to include action $pdf");
	}

	protected function routePageAction($p, $fileName) {
		$this->addProfilerMessage("Started to include action $p");
		parent::routePageAction($p, $fileName);
		$this->addProfilerMessage("Finished to include action $p");
	}

	protected function routeCronAction($cron, $fileName) {
		$this->prologueAction('cron', $cron); // this will also set special executionContext
		$this->addProfilerMessage("Started to include action $cron");
		if ($fileName = $this->checkAction($cron)) {
			require $fileName;
		}
		$this->addProfilerMessage("Finished to include action $cron");
		$this->sendWarnings('none');
	}

	public function addProfilerMessage($text, $activityStarted = null)
	{
		Profiler::getInstance()->addMessage($text, $activityStarted);
	}

/*
	//region currency and language functions for the framework
	public function isMultilingual()
	{
		return count($this->getAllSiteLanguages()) > 1;
	}

	public function getAllCurrencies()
	{
		if (count($this->allCurrencies) == 0) {
			$this->allCurrencies = Model::getInstance('currency')->getList();
		}

		return $this->allCurrencies;
	}

	public function getAllSiteCurrencies()
	{
		if (count($this->allSiteCurrencies) == 0) {
			$this->allSiteCurrencies = Model::getInstance('currency')->getList(array('is_enabled' => 1));
		}

		return $this->allSiteCurrencies;
	}

	public function getAllLanguages()
	{
		if (count($this->allLanguages) == 0) {
			$this->allLanguages = Model::getInstance('human_language')->getList();
		}

		return $this->allLanguages;
	}

	public function getAllSiteLanguages()
	{
		if (count($this->allSiteLanguages) == 0) {
			$this->allSiteLanguages = Model::getInstance('human_language')->getList(array('is_enabled' => 1));
		}

		return $this->allSiteLanguages;
	}

	public function getTranslatableLanguages()
	{
		if (count($this->translatableLanguages) == 0) {
			$query = file_get_contents(__DIR__ . "/sql/ListTranslatableLanguages.sql");
			$this->translatableLanguages = Database::getInstance()->getArray($query);
		}

		return $this->translatableLanguages;
	}

	// $scope - 'all', 'translatable' or 'site'
	public function getLanguages($scope)
	{
		switch($scope) {
			case 'all':
				return $this->getAllLanguages();
				break;
			case 'translatable':
				return $this->getTranslatableLanguages();
				break;
			case 'site':
			default:
				return $this->getAllSiteLanguages();
				break;
		}
	}

	public function getCurrencies($scope)
	{
		switch($scope) {
			case 'all':
				return $this->getAllCurrencies();
				break;
			case 'site':
			default:
				return $this->getAllSiteCurrencies();
				break;
		}
	}

	public function getLanguagesMap($excludeCodes = array())
	{
		$languagesMap = array();
		foreach($this->getAllSiteLanguages() as $languageInfo) {
			if (!in_array($languageInfo['code'], $excludeCodes)) {
				$languagesMap[$languageInfo['code']] = $languageInfo;
			}
		}

		return $languagesMap;
	}

	public function getCurrenciesMap($excludeCodes = array())
	{
		$currenciesMap = array();
		foreach($this->getAllSiteCurrencies() as $currencyInfo) {
			if (!in_array($currencyInfo['code'], $excludeCodes)) {
				$currenciesMap[$currencyInfo['code']] = $currencyInfo;
			}
		}

		return $currenciesMap;
	}


	//endregion
*/

/*
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
*/

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

}
