<?php

namespace DarkHelmet\Core
{
	class Settings extends \DOMDocument {

		const TYPE_BOOL   = 'boolean';
		const TYPE_INT    = 'integer';
		const TYPE_FLOAT  = 'float';
		const TYPE_STRING = 'string';
		const TYPE_ARRAY  = 'array';
		const TYPE_OBJECT = 'object';
		const TYPE_NULL   = 'null';

		/**
		 * @var \DOMXPath
		 */
		private $m_oXPath;
		private $m_aConnectors;

		protected $m_oCredentials;

		public function setCredentials($m_oCredentials)
		{
			$this->m_oCredentials = $m_oCredentials;
		}

		public function getCredentials(\DOMElement $p_oChild)
		{
			$aCredentials = array();

			$oCredentials = $this->m_oCredentials;
			if($oCredentials !== null){
				foreach($p_oChild->childNodes as $t_oChild){
					$aCredentials[$t_oChild->nodeName] = $oCredentials->get($t_oChild->nodeName.'[@id="'.$t_oChild->nodeValue.'"]');
				}#foreach
			}#if

			return $aCredentials;
		}

		public function getPrefixes()
		{
			$aPrefixes = array();
			if($this->m_oXPath instanceof \DOMXPath){
				$oResults = $this->m_oXPath->query('//Prefix');
				foreach($oResults as $index => $oResult){
					if($oResult instanceof \DOMNode){
						$aPrefixes[$oResult->getElementsByTagName('Name')->item(0)->nodeValue]
							= $oResult->getElementsByTagName('Character')->item(0)->nodeValue
						;
					}#if
				}#foreach
			}
			else{
				//? throw new \LogicException('Can\'t get Prefixes from Settings file if no Setting File has been loaded!');
			}#if

			return $aPrefixes;
		}

		public function getConnectors()
		{
			if(!isset($this->m_aConnectors)){
				$aConnectors = array();
				if($this->m_oXPath instanceof \DOMXPath){
					$oConnectorList = $this->m_oXPath->query('//Connectors');
					$aConnectorsList = $this->getChildrenFromList($oConnectorList);
					//@TODO: Validate what happens when 'Connectors' is missing or empty
					//       or only contains empty 'Connector's
					if(isset($aConnectorsList['Connectors']) && !empty($aConnectorsList['Connectors'])){
						$aConnectorsList = $aConnectorsList['Connectors'];
						if(isset($aConnectorsList['Connector']) && !empty($aConnectorsList['Connector'])){
							$aConnectorsList = $aConnectorsList['Connector'];
						}
					}else{
						$aConnectorsList = array();
					}#if
				}
				else{
					$aConnectorsList = array();
				}#if

				foreach($aConnectorsList as $t_aConnector){
					//@TODO: Validate these actually exist
					$sName = $t_aConnector['Name'];
					if(isset($t_aConnector['Params'])){
						$aParams = $t_aConnector['Params'];
					}else{
						$aParams = array();
					}#if
					$sClassName = $t_aConnector['Class'];

					$oReflector = new \ReflectionClass($sClassName);
					if(!is_subclass_of($sClassName, '\DarkHelmet\Connectors\Base')){
						throw new Exception('Given Connector "' . $sClassName .'" does not extend the Base Connector');
					}else{
						//@TODO: Validate contains class $oReflector ?
						$aConnectors[$sName] = $oReflector->newInstance($aParams);
					}#if
				}#foreach

				$this->m_aConnectors = $aConnectors;
			}#if

			return $this->m_aConnectors;
		}

		public function get($p_sSetting, $p_CastAs=null)
		{
			$mSetting = null;

			if($this->m_oXPath instanceof \DOMXPath){
				$oResults = $this->m_oXPath->query('//' . $p_sSetting);
				if($oResults->length > 1){
					throw new Exception('Found more than one setting named "'.$p_sSetting.'".');
				}
				else if($oResults->length === 0){
					throw new Exception('Could not find setting named "'.$p_sSetting.'".');
				}
				else{
					$mSetting = $oResults->item(0)->nodeValue;
					if(isset($p_CastAs)){
						settype($mSetting,$p_CastAs);
					}#if
				}#if
			}else{
				throw new Exception('No XPath available to query settings with!');
			}#if

			return $mSetting;
		}

		public function __call($p_sMethodName, $p_aArguments)
		{
			if(strpos($p_sMethodName, 'get') === 0){
				$sMethodName = substr($p_sMethodName,3);
				array_unshift($p_aArguments, $sMethodName);
				return call_user_func_array(array($this, 'get'), $p_aArguments);
			}else{
				throw new Exception('Call to undefined method ' . get_called_class() . '::'.$p_sMethodName.'()');
			}
		}

		public function credentialsFromFile($p_sFilePath)
		{
			$this->setCredentials(self::loadFromFile($p_sFilePath));
		}

		static public function loadFromFile($p_sFilePath, $p_iOptions = null)
		{
			//@TODO: replace $p_sFilePath by an splFileObject
			$oInstance = new self;

			$oInstance->preserveWhiteSpace = false;
			//@TODO: Validate $p_sFilePath actually exists and is readable
			$oInstance->load((string) $p_sFilePath, $p_iOptions);
			$oInstance->m_oXPath = new \DOMXPath($oInstance);

			return $oInstance;
		}

		protected function getChildrenFromList(\DOMNodeList $p_oConnectorList, $p_bAllowMultipleValues=false)
		{
			$aList = array();

			foreach($p_oConnectorList as $t_oChild){
				if($t_oChild instanceof \DOMElement){
					$bAllowMultipleValues =
							$t_oChild->hasAttribute('multiple-values')
						&& (bool) $t_oChild->getAttribute('multiple-values')
					;

					if($t_oChild->hasAttribute('source')
						&& is_callable(array($this, 'get' . $t_oChild->getAttribute('source')))
					){
						// Replace the value and anything beneath with the given source
						$mValue = call_user_func(
							  array($this, 'get' . $t_oChild->getAttribute('source'))
							, $t_oChild
						);
					}
					else {
						// Check if this node actually has any children, as $t_oChild->haschildNodes() will always give 'true'
						foreach($t_oChild->childNodes as $t_oGrandChild){
							if($t_oGrandChild->nodeType === XML_ELEMENT_NODE){
								// Call ourselves to get name / value of children as well
								$mValue = call_user_func(__CLASS__.'::'.__FUNCTION__, $t_oChild->childNodes, $bAllowMultipleValues);
								break; // We don't need to know if there is more than one child
							}
							else{
								// Get name / value
								$mValue = trim($t_oChild->nodeValue);
							}#if
						}#foreach
					}#if

					if($p_bAllowMultipleValues === true){
						$aList[$t_oChild->nodeName][] = $mValue;
					}
					else{
						$aList[$t_oChild->nodeName] = $mValue;
					}#if
				}#if
			}#foreach

			return $aList;
		}
	}
}

#EOF