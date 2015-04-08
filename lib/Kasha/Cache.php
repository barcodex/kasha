<?php

namespace Kasha;

use Kasha\Caching\Cache as BaseCache;
use Kasha\Model\Cache as ModelCache;
use Kasha\Templar\Cache as TemplateCache;
use Kasha\Core\Config;
use Temple\Util;
use Kasha\Profiler\Profiler;

class Cache extends BaseCache
{
	/** @var ModelCache */
	protected $modelCache = null;
	/** @var TemplateCache */
	protected $templateCache = null;

	/** @var array */
	protected $templates = array(); // each item stored in this array is a string

	/** @var array */
	protected $dictionaries = array(); // each item stored in this array is also an array

	protected $settings = array();

	public function getModelCache()
	{
		return $this->modelCache;
	}

	public function getTemplateCache()
	{
		return $this->templateCache;
	}

	public function __construct()
	{
		$appFolder = Config::getInstance()->getFolderPath('app');
		$this->setRootFolder($appFolder . 'cache/');
		// also include all custom caches that extend BaseCache
		$this->modelCache = ModelCache::getInstance()->setRootFolder($appFolder . 'cache/');
		$this->templateCache = TemplateCache::getInstance()->setRootFolder($appFolder . 'cache/');
	}

	public static function getTemplate($templateName)
	{
		/** @var $instance Cache */
		$instance = self::getInstance();
		if (array_key_exists($templateName, $instance->templates)) {
			// we already have read template into the cache (it means it was already used by the Runtime)
			$template = $instance->templates[$templateName];
		} else {
			// try to get serialized model from the cache
			$template = (self::hasKey('template/' . $templateName)) ? self::get('template/' . $templateName) : false;
			$instance->templates[$templateName] = $template;
		}

		return $template;
	}

	public static function setTemplate($templateName, $template)
	{
		/** @var $instance Cache */
		$instance = self::getInstance();
		if (Util::lavnn('templates', $instance->settings, false)) {
			$instance->templates[$templateName] = $template;
			self::set('template/' . $templateName, $template);
		}
	}

	public static function deleteTemplate($templateName)
	{
		/** @var $instance Cache */
		$instance = self::getInstance();
		if (isset($instance->templates[$templateName])) {
			unset($instance->templates[$templateName]);
		}
		// global cache might have the key even if dictionary cache does not -> delete it
		self::delete('template/' . $templateName);
	}

	public static function getDictionary($dictionaryName)
	{
		/** @var $instance Cache */
		$instance = self::getInstance();
		$timeStarted = Profiler::microtimeFloat();
		if (array_key_exists($dictionaryName, $instance->dictionaries)) {
			// we already have de-serialized version in cache (it means it was already used by the Runtime)
			$dictionary = $instance->dictionaries[$dictionaryName];
		} else {
			// try to get serialized model from the cache
			$dictionarySerialized = (self::hasKey('dictionary/' . $dictionaryName)) ? self::get('dictionary/' . $dictionaryName) : false;
			$dictionary = $dictionarySerialized ? json_decode($dictionarySerialized, true) : false;
			$instance->dictionaries[$dictionaryName] = $dictionary;
		}

		return $dictionary;
	}

	public static function setDictionary($dictionaryName, $dictionary)
	{
		/** @var $instance Cache */
		$instance = self::getInstance();
		if (Util::lavnn('dictionaries', $instance->settings, false)) {
			$instance->dictionaries[$dictionaryName] = $dictionary;
			self::set('dictionary/' . $dictionaryName, json_encode($dictionary, JSON_UNESCAPED_UNICODE + JSON_PRETTY_PRINT));
		}
	}

	public static function deleteDictionary($dictionaryName)
	{
		/** @var $instance Cache */
		$instance = self::getInstance();
		if (isset($instance->dictionaries[$dictionaryName])) {
			unset($instance->dictionaries[$dictionaryName]);
		}
		// global cache might have the key even if dictionary cache does not -> delete it
		self::delete('dictionary/' . $dictionaryName);
	}

	public static function invalidateEverything()
	{
		/** @var $instance Cache */
		$instance = self::getInstance();
		return array(
            'templates' => self::invalidateAllTemplates(), // @TODO ideally, it should go to Templar\Cache
            'dictionaries' => self::invalidateAllDictionaries(),
            'metadata' => $instance->getModelCache()->invalidateAllMetadata(),
            'models' => $instance->getModelCache()->invalidateAllModels(),
            'settings' => self::invalidateSettings()
        );
	}

	public static function invalidateAllTemplates($moduleName = '')
	{
		$prefix = 'template/' . ($moduleName != '' ? $moduleName.':' : '');
		return self::deleteByPrefix($prefix);
	}

	public static function invalidateAllDictionaries($moduleName = '')
	{
		$prefix = 'dictionary/' . ($moduleName != '' ? $moduleName.':' : '');
		return self::deleteByPrefix($prefix);
	}

	public static function invalidateSettings()
	{
		$prefix = 'settings:';
		return self::deleteByPrefix($prefix);
	}

	public static function getStats()
	{
		return array(
			'templates' => count(self::listKeysByPrefix('template/')),
			'dictionaries' => count(self::listKeysByPrefix('dictionary/')),
			'models' => count(self::listKeysByPrefix('metadata/'))
		);
	}

	public static function getTemplates()
	{
		$output = array();

		foreach (self::listKeysByPrefix('template/') as $templateFile) {
			list($module, $name, $language) = explode(':', basename($templateFile), 3);
			$language = str_replace('.txt', '', $language);
			$output[] = array(
				'path' => $templateFile,
				'module' => $module,
				'name' => $name,
				'language' => $language
			);
		}

		return $output;
	}

	public static function getDictionaries()
	{
		$output = array();

		foreach (self::listKeysByPrefix('dictionary/') as $dictionaryFile) {
			list($module, $name, $language) = explode(':', basename($dictionaryFile), 3);
			$language = str_replace('.txt', '', $language);
			$output[] = array(
				'path' => $dictionaryFile,
				'module' => $module,
				'name' => $name,
				'language' => $language
			);
		}

		return $output;
	}

	public static function getMetadata()
	{
		$output = array();

		foreach (self::listKeysByPrefix('metadata/') as $metadataFile) {
			$name = str_replace('.txt', '', basename($metadataFile));
			$output[] = array(
				'path' => $metadataFile,
				'name' => $name
			);
		}

		return $output;
	}

	public static function getModels()
	{
		$output = array();
		$basePath = self::getInstance()->getRootFolder() . 'models/';

		foreach (self::listKeysByPrefix('models/*/') as $modelFile) {
			list($model, $id) = explode('/', str_replace($basePath, '', $modelFile), 2);
			$id = str_replace('.txt', '', $id);
			$output[] = array(
				'path' => $modelFile,
				'name' => $model,
				'id' => $id
			);
		}

		return $output;
	}

}
