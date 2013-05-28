<?php

namespace DarkHelmet\Core\Controllers
{
	use DarkHelmet\Connectors\Local\LocalConnector;
	use \DateTime;

	use DarkHelmet\Core\Hooks\History as HistoryHook;
//	use DarkHelmet\Connectors\Hooks\History as ConnectorHistoryHook;

	/**
	 * History is a Hook a Controller can opt-in on. Once it implements the hook,
	 * Connectors that support it can provide a history for the TimeLog from any
	 * source it is connected to, like local logs, previous work loads from Jira
	 * or commits from SVN.
	 */
	class History extends Base implements HistoryHook
	{
////////////////////////////////// Properties \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
////////////////////////////// Getters and Setters \\\\\\\\\\\\\\\\\\\\\\\\\\\\\
//////////////////////////////// Public Methods \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		public function __construct($p_sDate=null)
		{
			parent::__construct();
			$this->m_sDate = (string) $p_sDate;
		}

		public function buildOutput()
		{
			// As everything is already set up here, all we need to do is pass
			// everything to the template and let that do the work for us.
			$oContext = $this->getContext();

			$oDate = new DateTime($this->m_sDate);
			$oContext
				->setTemplate('main.html')
				->set('bShowForm', false)
				->set('sDate', $this->m_sDate)
				->set('oDate', $oDate)
			;

			$aHistory = array();

			if(empty($this->m_sDate)){
				$oContext->set('bShowForm', false);
				$oContext->setTemplate('history.html');

				$aDayTotals = array();
				$aWeekEntries = array();

				foreach($this->getSettings()->getConnectors() as $t_oConnector){

					$iCurrentWeek = 0;
					$iWeekTally = 0;

					if($t_oConnector instanceof LocalConnector){
						$aHistoryList = $t_oConnector->getHistoryList();
						$aKeys = array_keys($aHistoryList);
						$oContext->set('keys', $aKeys);


						foreach($aHistoryList as $t_sDate => $t_sFilePath){
							/** @var $oTimeLog \DarkHelmet\Core\TimeLog */
							$oTimeLog = $t_oConnector->createTimeLogFromDateString($t_sDate, $oContext);

							$t_aTotals = $oTimeLog->calculateTotals();
							$sTotalSeconds = null;
							if(isset($t_aTotals['ALL']['__TOTAL__']))
							{
								/** @var $oTotal DateTime */
								$oTotal = $t_aTotals['ALL']['__TOTAL__'];
								$sTotalSeconds = $oTotal->format('U');
							}#if
							$aDayTotals[$t_sDate ] = $sTotalSeconds;
						}#foreach

						krsort($aDayTotals); // Newest first
						foreach($aDayTotals as $t_sDay => $t_sDayTotalSeconds)
						{
							$sTimestamp = strtotime($t_sDay);
							$iWeek = date('W', $sTimestamp);
							$sWeekKey = $iWeek . '/' . date('Y', $sTimestamp);

							if(isset($aWeekEntries[$sWeekKey]) === false){
								$aWeekEntries[$sWeekKey] = array('aDayEntries'=>array());
							}#if

							$aWeekEntries[$sWeekKey]['aDayEntries'][$t_sDay] = array(
								  'sDate' => date('D j F Y', $sTimestamp)
								, 'sTime' => gmdate("H:i:s", $t_sDayTotalSeconds)
							);

							if($iWeek !== $iCurrentWeek) {
								// Reset for a new week
								$iCurrentWeek = $iWeek;
								$iWeekTally = 0;
							}#if

							$iWeekTally += $t_sDayTotalSeconds;
							$aWeekEntries[$sWeekKey]['sTotal'] = $this->weekTallyToString($iWeekTally);
						}#foreach

						$oContext->set('aWeekEntries', $aWeekEntries);
					}#if
				}#foreach
			}
			else if($this->m_sDate !== $oContext->get('sToday')){
				foreach($this->getSettings()->getConnectors() as $t_oConnector){
					if($t_oConnector instanceof History){
						/** @var \DarkHelmet\Connectors\Hooks\History $t_oConnector  */
						$aHistory = array_merge(
							  $aHistory
							, $t_oConnector->provideHistory($oContext)
						);
					}#if
				}#foreach
			}
			else {
				$sDate = $this->m_sDate;
			}#if

			//@FIXME: This should be moved to the LocalConnector
			// Validate that a TimeLog is available for the given date
//			else if(!array_key_exists($this->m_sDate, $this->m_aHistoryList)){

			//@TODO: Retrieve "the History" which should either be a collection or an array of TimeLogs
			//       And build output from that.
			//       Of course, if no date is asked for we should show an overview of available history dates.
			//       Otherwise we should validate that a history is available for the asked date.
			//       So we need both a HistoryList as a single History object?
			return $this->buildTemplateOutputFromContext($oContext);
		}

//////////////////////////////// Helper Methods \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		/**
		 * @param $iWeekTally
		 *
		 * @return string
		 */
		protected function weekTallyToString($iWeekTally)
		{
			$sWeekTally = sprintf(
				"%02d:%02d:%02d",
				floor($iWeekTally / 3600),
				($iWeekTally / 60) % 60,
				$iWeekTally % 60
			);

			return $sWeekTally;
		}
	}
}
