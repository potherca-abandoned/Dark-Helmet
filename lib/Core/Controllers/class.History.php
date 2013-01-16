<?php

namespace DarkHelmet\Core\Controllers
{
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

            $oContext
				->setTemplate('main.html')
				->set('bShowForm', false)
				->set('sDate', $this->m_sDate)
				->set('oDate', new DateTime($this->m_sDate))
//			$oContext	->set('sMessage', 'History Listing has not been implemented yet.')
			;

//			return $this->buildTemplateOutputFromContext($this->getContext());
////////////////////////////////////////////////////////////////////////////////
            //@TODO: Show the History Listing
            /*
                To properly implement this we need to add another required
                method to the historyConnector interface that requires triggered
                HistoryProviders to provide a list we can then turn into:

                    <li><a href="history/$tag"></a></li>
            */
            $aHistory = array();

			if(empty($this->m_sDate)){
                $oContext->set('bShowForm', false);

				$aDayTotals = array();
				$aWeekTotals = array();

                $iCurrentWeek = 0;
                $iWeekTally = 0;
				foreach($this->getSettings()->getConnectors() as $t_oConnector){
					if($t_oConnector instanceof \DarkHelmet\Connectors\Local\LocalConnector){
						/** @var $t_oConnector \DarkHelmet\Connectors\Local\LocalConnector */
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
                            echo sprintf(
                                '<li>
                                    <a href="history/%1$s"> %2$s </a>
                                    <span class="day-total">%3$s</span>
                                </li>'
                                , $t_sDay
                                , date('D j F Y', $sTimestamp)
                                , gmdate("H:i:s", $t_sDayTotalSeconds)
                            );

                            $iWeek = date('W', $sTimestamp);
                            if($iWeek !== $iCurrentWeek) {
                                $iWeekTally += $t_sDayTotalSeconds;
                                echo sprintf(
                                      '</ul><p class="week-total">Week Total: <span>%2$s</span></p><hr/>
                                       <p class="week">Week %1$s/%3$s: <span>%2$s</span></span></p><ul>
                                      '
                                    , $iWeek
                                    , sprintf("%02d:%02d:%02d", floor($iWeekTally/3600), ($iWeekTally/60)%60, $iWeekTally%60)
                                    , date('Y', $sTimestamp)

                                );
                                $iCurrentWeek = $iWeek;
                                $iWeekTally = 0;
                            } else {
                                $iWeekTally += $t_sDayTotalSeconds;
                            }
						}
					}#if
				}#foreach
			}
			else if($this->m_sDate !== $oContext->get('sToday')){
				foreach($this->getSettings()->getConnectors() as $t_oConnector){
					if($t_oConnector instanceof History){
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
	}
}
