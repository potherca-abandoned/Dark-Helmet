<?php
namespace DarkHelmet\Core\Controllers
{

	use \DateTime;
	use \SplFileInfo;

	use \DarkHelmet\Core\Context as Context;
	use \DarkHelmet\Core\TimeLog;

	class Total extends Base
	{
	//////////////////////////////// Public Methods \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		public function __construct()
		{
			parent::__construct();
		}


		protected function handleConnectors()
		{
		}

		protected function buildOutput() {
			$oTimeLog = new Timelog();
			$oContext = $this->getContext();

//			$sTemplate = 'total';
			$aTotals = array();
			foreach($this->m_aLogFiles as $t_sDate => $t_sLogFile){
				// Setup the Log
				$oTimeLog->setDate(new DateTime($t_sDate));
				$oTimeLog->setFile(new SplFileInfo($t_sLogFile));
				$oTimeLog->setTagPrefixes($oContext->get('aPrefix'));
				$oTimeLog->loadFromFile();
				$aTotals = $oTimeLog->getTotalTime($aTotals);
			}#foreach

			$sTotals = TimeLog::htmlUniqueTotals($aTotals);
			$keys = array_keys($this->m_aLogFiles);
			$oContext->set('sTotals', $sTotals);
			$oContext->set('sFrom', array_pop($keys));
			$oContext->set('sTo', array_shift($keys));

			return $this->buildTemplateOutputFromContext($oContext);
		}
	}
}
