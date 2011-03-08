<?php

namespace DarkHelmet
{
	use DarkHelmet\Core\Exception;
	use DarkHelmet\Core\Request;
	use DarkHelmet\Core\Settings;

	use DarkHelmet\Core\Controllers\Base as BaseController;

////////////////////////////////////////////////////////////////////////////////
//	I'm pretty sure there's a nicer solution for this... either move it to a
//	separate bootstrap file, include it in the controller or get rid of it altogether.
//	Especially the 'require' and the constants are a bit of an eye-sore to me.
//----------------------------------------------------------------------------//
	error_reporting(E_STRICT | E_ALL);

	define('PROJECT_DIR',   realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
	define('LOGS_DIR',      PROJECT_DIR  . 'logs/');
	define('TEMPLATE_DIR',  PROJECT_DIR  . 'lib/Templates/');

	require '3rd-party/PHPTAL/classes/PHPTAL.php'; // Template Autoloader
	spl_autoload_register(
		function($p_sFullyQualifiedClassName){
			if(strpos($p_sFullyQualifiedClassName, 'DarkHelmet') === 0){

				$sFullClassName = str_replace('\\', '/', $p_sFullyQualifiedClassName);
				$sClassName = substr($sFullClassName, strrpos($sFullClassName, '/')+1);
				$sNameSpace = substr($sFullClassName, strlen('DarkHelmet'), -strlen($sClassName));

				if(strpos($sClassName,'Exception') !== false){
					$sClassName = 'Exceptions';
				}

				$aFilePrefixes = array('class','abstract','interface');
				foreach($aFilePrefixes as $t_sPrefix){
					$sFilePath = PROJECT_DIR . 'lib' . $sNameSpace . $t_sPrefix . '.' . $sClassName .'.php';
					if(file_exists($sFilePath)){
						require_once $sFilePath;
						break;
					}#if
				}#foreach
			}
			else{
				// fall through to the next autoloader
			}#if
		}#function
	);
////////////////////////////////////////////////////////////////////////////////
	$sUrl = $_SERVER['REQUEST_URI'];
	$aPostFields = array();
	if(isset($_POST['tags'])) {
		$aPostFields = $_POST['tags'];
	}
	$oRequest  = Request::get($sUrl, $aPostFields);
	$oSettings = Settings::loadFromFile(PROJECT_DIR.'settings.xml');
	$oSettings->credentialsFromFile(PROJECT_DIR.'credentials.xml');

	$sOutput = BaseController::getResponse($oRequest, $oSettings);

	die ($sOutput);
}

#EOF
