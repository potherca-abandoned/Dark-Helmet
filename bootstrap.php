<?php

date_default_timezone_set("Europe/Amsterdam");

error_reporting(E_STRICT | E_ALL);

set_error_handler(
	function($p_iError, $p_sError, $p_sFile, $p_iLine ) {
		throw new \ErrorException($p_sError, 0, $p_iError, $p_sFile, $p_iLine);
	}
);

define('PROJECT_DIR',   realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
define('LOGS_DIR',      PROJECT_DIR . 'logs/');
define('TEMPLATE_DIR',  PROJECT_DIR . 'lib/Templates/');
define('CONFIG_DIR',	PROJECT_DIR . 'conf/');

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
