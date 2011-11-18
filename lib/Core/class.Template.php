<?php

namespace DarkHelmet\Core
{
	class Template extends \PHPTAL
	{
		/**
		 * @var DarkHelmet\Core\Context
		 */
		protected $_context;

		public function __construct($path = false)
		{
			parent::__construct($path);
			$this->setContext(new Context());
			$this->setOutputMode(self::HTML5);
			$this->setTemplateRepository(TEMPLATE_DIR);
		}

		/**
		 * @param DarkHelmet\Core\Context $p_oContext
		 */
		public function setContext(Context $p_oContext)
		{
			$this->_context = $p_oContext;
		}

		/**
		 * @return \DarkHelmet\Core\Context
		 */
		public function getContext()
		{
			$oContext1 = $this->_context;

			if(!$oContext1 instanceof Context){
				$oContext2 = Context::from($oContext1);
			}else{
				$oContext2 = $oContext1;
			}

			return $oContext2;
		}

		protected function findTemplate()
		{
			if($this->_path === false ){ //? might be NULL ?
				// See if we can set the template from local Context
				$oContext = $this->getContext();
				if($oContext !== null){
					$sTemplate = $oContext->getTemplate();
					if($sTemplate !== null){
						$this->setTemplate($sTemplate);
					}#if
				}#if
			}#if

			if($this->_path !== false){
				// Template is set
				return parent::findTemplate();
			}#if
		}
	}
}