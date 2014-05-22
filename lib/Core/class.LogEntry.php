<?php

namespace DarkHelmet\Core
{
	use DateTime;
	use DateInterval;

	class LogEntry{
		// <editor-fold defaultstate="collapsed" desc="Property string Message">
		private $message = '';
		/**
		 * @return string
		 */
		public function getMessage()
		{
			return $this->message;
		}

		/**
		 * @param string $newMessage
		 */
		public function setMessage($newMessage)
		{
			$this->message = (string) $newMessage;
		}

		public function setMessageFromArray(Array $p_aTags, Array $p_aPrefixes)
		{
			$aTags = array();
			foreach($p_aTags as $t_sKey => $t_sTag) {
				if($t_sTag{0} === $p_aPrefixes['Time']){
					//@TODO: Validate that substracted time is not before last added time ?
					//       Or do we want to allow jumping the queue?
					$sInterval = substr($t_sTag,1);

					$sErrorMessage =
						  'Given Subtraction Time <code>'.$t_sTag.'</code> is not valid. <br>'
						. 'The Correct Subtraction Format is the amount of hours or '
						. 'minutes to subtract followed by the letter "h" to denote hours '
						. 'and/or the letter "m" to denote minutes.<br>'
						. '<br>For example: <code>20m</code> to subtract 20 '
						. 'minutes or <code>1h20m</code> to subtract 1 hour '
						. 'and 20 minutes.'
					;

					if(preg_match('/([0-9]+[hm])+/', $sInterval) === 0){
						throw new Exception($sErrorMessage);
					}else{
						try{
							$oInterval = new DateInterval('PT'.strtoupper($sInterval));
						}catch(Exception $eAny){
							throw new Exception($sErrorMessage);
						}#try
					}
					$this->getTime()->sub($oInterval);
				}else{
					$aTags[$t_sKey] = self::stripspaces($t_sTag);
				}
			}#foreach
			$this->message = implode(' ', $aTags);
		}
		// </editor-fold>
		// <editor-fold defaultstate="collapsed" desc="Property DateTime Time">
		private $time;

		/**
		 * @return \DateTime
		 */
		public function getTime()
		{
			return $this->time;
		}
		/**
		 * @param DateTime $newTime
		 */
		public function setTime(DateTime $newTime)
		{
			$this->time = $newTime;
		}
		// </editor-fold>
		// <editor-fold defaultstate="collapsed" desc="Property string DateTimeFormat">
		private $dateTimeFormat = DateTime::ATOM;
		/**
		 * @return string
		 */
		public function getDateTimeFormat()
		{
			return $this->dateTimeFormat;
		}
		/**
		 * @param string $newDateTimeFormat
		 */
		public function setDateTimeFormat($newDateTimeFormat)
		{
			$this->dateTimeFormat = $newDateTimeFormat;
		}
		// </editor-fold>

		public function __construct(){
			$this->setTime(new DateTime());
		}

		public function toString(){
			return $this->getTime()->format($this->getDateTimeFormat()) . ' ' . self::unstripspaces($this->getMessage());
		}

		public function __toString(){
			return $this->toString();
		}

		static public function fromString($p_sEntry){
			//@TODO: $p_sEntry format should be validated, use sscanf() maybe?
			$oSelf = new self;

			$sMessage = '';
			$sTime = null;

			if(strpos($p_sEntry, ' ') !== false){
				list($sTime, $sMessage) = explode(' ', $p_sEntry, 2);
			}
			$oTime = new DateTime($sTime);
			$oSelf->setTime(new DateTime($sTime));
			$oSelf->setMessage($sMessage);

			return $oSelf;
		}

		static public function fromArray(array $p_aEntry){
			return self::fromString(implode(' ', $p_aEntry));
		}

		public function toArray(Array $p_aTagPrefixes){
			$aMessage = array();
			foreach(explode(' ', $this->getMessage()) as $t_sMessage){
				$sKey = array_search($t_sMessage{0},$p_aTagPrefixes);
				if($sKey === false){
					$sKey = 'Comment';
				}else{
					$t_sMessage = substr($t_sMessage,1);
				}
				if(!isset($aMessage[$sKey])){
					$aMessage[$sKey] = self::unstripspaces($t_sMessage);
				}
				else{
					//@TODO: Find a more elegant solution to handle multiple tag entries
					$aMessage[$sKey] .= "\n\0" . self::unstripspaces($t_sMessage);
				}#if

			}#foreach
			return $aMessage;
		}

		/**
		 * Replace underscores with an HTML entity and replace spaces with underscores
		 *
		 * @param string $p_sString
		 * @return string
		 */
		static protected function stripspaces($p_sString) {
			$sReplacement       = '_';
			$sReplacementEntity = '&#95;';

			$sString = str_replace($sReplacement, $sReplacementEntity, $p_sString);
			$sString = str_replace(' ', $sReplacement, $p_sString);

			return $sString;
		}

		/**
		 * Replace underscores with spaces and replace underscore HTML entities with underscores.
		 *
		 * @param string $p_sString
		 * @return string
		 */
		static public function unstripspaces($p_sString) {
			$sReplacement       = '_';
			$sReplacementEntity = '&#95;';

			$sString = str_replace($sReplacement, ' ', $p_sString);
			$sString = str_replace($sReplacementEntity, $sReplacement, $sString);

			return $sString;
		}
	}
}

#EOF