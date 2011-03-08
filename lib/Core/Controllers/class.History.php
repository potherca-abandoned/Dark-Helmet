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
			$this->getContext()
				->setTemplate('main.html')
				->set('bShowForm', false)
				->set('sDate', $this->m_sDate)
				->set('oDate', new DateTime($this->m_sDate))
				->set('sMessage', 'History Listing has not been implemented yet.')
			;

			return $this->buildTemplateOutputFromContext($this->getContext());
////////////////////////////////////////////////////////////////////////////////
			$aHistory = array();
			if(empty($this->m_sDate)){
				// Show the History Listing
				die('@TODO: Show History Listing...');
			}
			else if($this->m_sDate !== $this->m_sToday){
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
