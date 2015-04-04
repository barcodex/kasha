<?php

namespace Kasha;

use Kasha\Core\PageRenderer as BasePageRenderer;
use Kasha\Templar\TextProcessor;

class PageRenderer extends BasePageRenderer
{
	/**
	 * Renders master template using block values as set by the time of calling
	 *
	 * @param Page $page
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function render($page)
	{
		// get initial set of blocks from the page
		$blocks = $page->getBlocks();

		//region Normalize styles and scripts, accumulated in meta, styles, scripts, cssFiles and jsFiles

		$templateContent = $this->getTemplate('index.meta');
		foreach ($page->getMeta() as $key => $value) {
			$blocks['meta'] .= TextProcessor::doText($templateContent, array('key' => $key, 'value' => $value));
		}

		$templateContent = $this->getTemplate('index.og');
		foreach ($page->getOg() as $property => $content) {
			$blocks['og'] .= TextProcessor::doText($templateContent, array('property' => $property, 'content' => $content));
		}

		$templateContent = $this->getTemplate('index.cssFile');
		foreach ($page->getCcsFiles() as $cssFileName) {
			$blocks['cssFiles'] .= TextProcessor::doText($templateContent, array('filename' => $cssFileName));
		}

		$styles = $page->getStyles();
		if (count($styles) > 0) {
			$blocks['styles'] = join(PHP_EOL, $styles);
		}

		$templateContent = $this->getTemplate('framework', 'index.jsFile');
		foreach ($page->getHeadJsFiles() as $jsFileName) {
			$blocks['headJsFiles'] .= TextProcessor::doText($templateContent, array('filename' => $jsFileName));
		}
		foreach ($page->getBodyJsFiles() as $jsFileName) {
			$blocks['bodyJsFiles'] .= TextProcessor::doText($templateContent, array('filename' => $jsFileName));
		}

		$headScripts = $page->getHeadScripts();
		if (count($headScripts) > 0) {
			$blocks['headScripts'] = join(PHP_EOL, $headScripts);
		}

		$bodyScripts = $page->getBodyScripts();
		if (count($bodyScripts) > 0) {
			$blocks['bodyScripts'] = join(PHP_EOL, $bodyScripts);
		}

		//endregion

		// Parse the location of master template
		list($moduleName, $templateName) = explode(':', $page->getMasterTemplate(), 2);

		// Return parsed value
		return TextProcessor::doTemplate($moduleName, $templateName, $blocks);
	}

}
