<?php

namespace DarkHelmet\Core
{
	use \DateTime;

	/* This context should be general to the entire tool, not just the template.
	 * It should be allowed to be *passed* to the template, it should not reside
	 * or originate there. That way, relevant data can be generated, populated
	 * and shared across the entire application, not just a subset of the View.
	 *
	 * To achieve this, the Context should not inherit from the PHPTAL_Context,
	 * as a matter of fact, it shouldn't even encapsulate it. It should simply
	 * be passed to it. If straight referencing isn't possible (one-on-one mapping)
	 * A conversion utility needs to be created and put ... somewhere.
	 *
	 * My first guess is, however, that because the use-cases for both sides are
	 * very similar, there is a large chance it is in fact straight-mappable.
	//
	 * For this to properly work and be easily debug-able, we need to implement
	 * a history for each item that is being set in the context. That way when
	 * unexpected behaviour occurs, we can see whom set what when.
	 */
	class Context extends \PHPTAL_Context implements \ArrayAccess
	{
////////////////////////////////// Properties \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		private $m_sTemplate;

////////////////////////////// Getters and Setters \\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		/**
		 * @return \DateTime
		 */
		public function getDate()
		{
			return $this->get('oDate');
		}

		/**
		 * @param \DateTime $p_oNewDate
		 */
		public function setDate(DateTime $p_oNewDate)
		{
			$this->set('oDate', $p_oNewDate);
		}

		/**
		 * @return string The Template Name
		 */
		public function getTemplate()
		{
			return $this->get('sTemplate');
		}

		public function setTemplate($p_sTemplate)
		{
			return $this->set('sTemplate', (string) $p_sTemplate);
		}

		public function setTagPrefixes($m_aTagPrefixes)
		{
			$this->set('aPrefix', $m_aTagPrefixes);
		}

		public function getTagPrefixes()
		{
			return $this->get('aPrefix');
		}

		public function getTagPrefix($p_sName)
		{
			$aPrefixes = $this->getTagPrefixes();
			return $aPrefixes[$p_sName];
		}

		/**
		 * Retrieve an item from the context.
		 *
		 * @param string $p_sVarName The name of the item to retrieve.
		 *
		 * @return mixed The value of the retrieved item.
		 */
		public function get($p_sVarName)
		{
			if (isset($this->$p_sVarName)) {
			    $mValue = $this->$p_sVarName;
			}else{
				$mValue = parent::__get($p_sVarName);
			}#if

			return $mValue;
		}

		/**
		 * Add an item to the Context.
		 *
		 * @param string $p_sVarName The name of the item to add.
		 * @param mixed  $p_mValue   The value of the item to add.
		 *
		 * @return \DarkHelmet\Core\Context
		 */
		public function set($p_sVarName, $p_mValue)
		{
			parent::__set($p_sVarName, $p_mValue);

			return $this;
		}

/////////////////////////// ArrayAccess Implementation \\\\\\\\\\\\\\\\\\\\\\\\\
		/**
		 * @param mixed $p_mOffset
		 * @return boolean
		 */
		public function offsetExists ( $p_mOffset )
		{
			try{
				$this->get($p_mOffset);
				$bExists = true;
			}
			catch(\PHPTAL_VariableNotFoundException $oException){
				$bExists = false;
			}

			return $bExists;
		}

		/**
		 * @param mixed $p_mOffset
		 * @return mixed
		 */
		public function offsetGet ( $p_mOffset )
		{
			return $this->get($p_mOffset);
		}

		/**
		 * @param mixed $p_mOffset
		 * @param mixed $p_mValue
		 * @return void
		 */
		public function offsetSet ( $p_mOffset , $p_mValue )
		{
			$this->set($p_mOffset,$p_mValue);
		}
		/**
		 * @param mixed $p_mOffset
		 * @return void
		 */
		public function offsetUnset ( $p_mOffset )
		{
			$this->set($p_mOffset, null);
		}

//////////////////////////////// Public Methods \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		/**
		 * @static
		 * @param  $p_oContext
		 * @return \DarkHelmet\Core\Context
		 */
		static public function from($p_oContext){
			$oContext = new static();

			foreach(get_class_vars($p_oContext) as $t_sVar){
				$oContext->$t_sVar = $p_oContext->$t_sVar;
			}#foreach

			return $oContext;
		}

//////////////////////////////// Helper Methods \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
	}
}
