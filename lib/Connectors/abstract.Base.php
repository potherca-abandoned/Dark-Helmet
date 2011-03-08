<?php

namespace DarkHelmet\Connectors
{
	use DarkHelmet\Core\Object;

	abstract class Base extends Object{

////////////////////////////////// Properties \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		private $m_aParams = array();

////////////////////////////// Getters and Setters \\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		public function setParams($p_aParams)
		{
			$this->m_aParams = $p_aParams;
		}

		public function getParams()
		{
			return $this->m_aParams;
		}

		public function getParam($p_sName)
		{
			$aParams = $this->getParams();
			//@TODO validate in_array() ? Depends on expected behaviour/exceptions
			return $aParams[$p_sName];
		}

//////////////////////////////// Public Methods \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		final public function __construct(Array $p_aParams){
			$this->m_aParams = $p_aParams;
			$oReflector = new \ReflectionClass($this);
			if(!$oReflector->implementsInterface('\DarkHelmet\Connectors\Hooks\Base')){
				throw new \DarkHelmet\Core\Exception(
				  'Connector "' . get_called_class(). '" does not implement any of the Connector Interfaces.'
				);
			}#if
		}
	}
}

#EOF