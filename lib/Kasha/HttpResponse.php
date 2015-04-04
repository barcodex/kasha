<?php

namespace Kasha;

use Kasha\Templar\TextProcessor;

class HttpResponse
{
	public static function dynamic401($params = array()) {
		header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthorized");

		$params['bodyId'] = 'p402';
		$params['bodyClasses'] = 'error-page';

		print TextProcessor::doTemplate('main', 'http.401', $params);
		die();
	}

	public static function dynamic403($params = array()) {
		header($_SERVER["SERVER_PROTOCOL"]." 403 Forbidden");

		$params['bodyId'] = 'p403';
		$params['bodyClasses'] = 'error-page';

		print TextProcessor::doTemplate('main', 'http.403', $params);
		die();
	}

	public static function dynamic404($params = array()) {
		header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");

		$params['bodyId'] = 'p404';
		$params['bodyClasses'] = 'error-page';

		print TextProcessor::doTemplate('main', 'http.404', $params);
		die();
	}
}

