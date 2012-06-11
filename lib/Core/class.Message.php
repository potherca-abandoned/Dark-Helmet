<?php

namespace DarkHelmet\Core
{
	class Message {
		
		const SEVERITY_NOTICE = 1;
		const SEVERITY_WARNING = 2;
		const SEVERITY_ERROR = 3;
		
		// <editor-fold defaultstate="collapsed" desc="property int Severity">
		/**
		 * @var int
		 */
		private $iSeverity = self::SEVERITY_ERROR;
		/**
		 * @return int
		 */
		public function getSeverity() {
			return $this->iSeverity;
		}
		/**
		 * @param int
		 * 
		 * @return void
		 */
		public function setSeverity($newSeverity) {
			$this->iSeverity = $newSeverity;
		}
		// </editor-fold>
		// <editor-fold defaultstate="collapsed" desc="property String Text">
		/**
		 * @var String
		 */
		private $sText = '';
		/**
		 * The message body text
		 * 
		 * @return String
		 */
		public function getText() {
			return $this->sText;
		}
		/**
		 * @param String
		 * 
		 * @return void
		 */
		public function setText($newText) {
			$this->sText = $newText;
		}
		// </editor-fold>
		
		/**
		 * Returns the text corresponding to the severity for this message.
		 *
		 * @return string
		 * @throws Exception 
		 */
		public function getSeverityText() {
			$result = '';
			switch($this->getSeverity()) {
				case self::SEVERITY_NOTICE:
					$result = 'Notice';
					break;
				case self::SEVERITY_WARNING:
					$result = 'Warning';
					break;
				case self::SEVERITY_ERROR:
					$result = 'Error';
					break;
				default:
					throw new Exception('Unknown severity');
			}
			return $result;
		}

		/**
		 *
		 * @param string $sText
		 * @param int $iSeverity
		 * 
		 * @return \DarkHelmet\Core\Message
		 */
		public function __construct($sText, $iSeverity = self::SEVERITY_ERROR) {
			$this->setText($sText);
			$this->setSeverity($iSeverity);
		}
		
	}
}