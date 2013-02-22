<?php

/**
 * Slim-Admin
 *
 * @author      Arron <arronzhang@me.com>
 * @copyright   2013 Arron
 * @version     0.1.0
 * @package     Slim-Admin
 *
 * MIT LICENSE
 */

namespace Slim;

/**
 * Admin
 *
 * @since 0.1.0
 *
 */

class Admin extends \Slim\Slim
{
	/********************************************************************************
	 * PSR-0 Autoloader
	 *
	 * Do not use if you are using Composer to autoload dependencies.
	 *******************************************************************************/

	/**
	 * Slim PSR-0 autoloader
	 */
	public static function autoload($className)
	{
		$thisClass = str_replace(__NAMESPACE__.'\\', '', __CLASS__);

		$baseDir = __DIR__;

		if (substr($baseDir, -strlen(__NAMESPACE__)) === __NAMESPACE__) {
			$baseDir = substr($baseDir, 0, -strlen(__NAMESPACE__));
		}


		$className = ltrim($className, '\\');
		$fileName  = $baseDir;
		$namespace = '';
		if ($lastNsPos = strripos($className, '\\')) {
			$namespace = substr($className, 0, $lastNsPos);
			$className = substr($className, $lastNsPos + 1);
			$fileName  .= str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
		}
		$fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

		if (file_exists($fileName)) {
			require $fileName;
		}
	}

	/**
	 * Register PSR-0 autoloader
	 */
	public static function registerAutoloader()
	{
		spl_autoload_register(__NAMESPACE__ . "\\Admin::autoload");
	}
}

?>
