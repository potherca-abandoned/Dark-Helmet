<?php
namespace DarkHelmet\Connectors\Jira
{
	use \SoapClient;
	use \SoapFault;
	use \DateInterval;

	use DarkHelmet\Core\Context;
	use DarkHelmet\Core\Exception;
	use DarkHelmet\Core\TimeLog;

	use DarkHelmet\Core\Controllers\Tags;

	use DarkHelmet\Connectors\Base;
	use DarkHelmet\Connectors\Hooks\Persistence as PersistenceHook;
	use DarkHelmet\Connectors\Hooks\Tags        as TagsHook;
	use DarkHelmet\Connectors\Hooks\Init        as InitHook;

	class JiraConnector extends Base implements InitHook, PersistenceHook, TagsHook
	{
////////////////////////////////// Properties \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		protected $m_aConnector = array();
		protected $m_aErrors = array();

//////////////////////////////// Public Methods \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		/**
		 * @param \DarkHelmet\Core\Context $p_oContext
		 * @return void
		 */
		public function init(Context $p_oContext)
		{
			$this->m_aConnector['Name']     = $this->getShortName();
			$this->m_aConnector['Wsdl']     = $this->getParam('Wsdl');
			$this->m_aConnector['FilterId'] = $this->getParam('FilterID');

			$aCredentials = $this->getParam('Credentials');
			if(empty($aCredentials)){
				throw new Exception('Could not get credentials');
			}
			else{
				$this->m_aConnector['User']     = $aCredentials['User'];
				$this->m_aConnector['Password'] = $aCredentials['Password'];
			}#if
		}

		public function handlePersistenceFor(TimeLog $p_oTimeLog, Context $p_oContext)
		{
			$aEntries = $p_oTimeLog->getEntries();

			if(count($aEntries) > 1){
				$oLastEntry   = array_pop($aEntries);
				$oTargetEntry = array_pop($aEntries);

				$aMessage = $oTargetEntry->toArray($p_oContext->getTagPrefixes());

				if(
					isset($aMessage['Meta']) && $aMessage['Meta'] === $this->getShortName()
					&& isset($aMessage['Ticket'])  // No number, no service
				) {
					$oDateInterval = $oTargetEntry->getTime()->diff($oLastEntry->getTime());

					$sComment = (isset($aMessage['Comment'])
							?   $aMessage['Comment']."\n"
							:'<no comment>')
						. "\n" . ' ---'
						. "\n" . ' logged via ' . __CLASS__ ;

					$iTime = $this->dateIntervalToSeconds($oDateInterval);
					$sTime = $this->getTimeStringFromDateInterval($oDateInterval);
					$sTicketId = $aMessage['Ticket'];

					$oClient = $this->getClient();
					if($oClient !== null) {
						try{
							$this->startProgressOnTicket($oClient, $sTicketId);


							// Write Logged time to ticked using the SOAP API
							$oClient->addWorklogAndAutoAdjustRemainingEstimate (
								  $this->m_aConnector['oAuthentication']
								, $sTicketId
								, array(
							          'startDate' => $oTargetEntry->getTime()->format(DATE_ATOM)
							        , 'timeSpentInSeconds' => $iTime
									, 'timeSpent' => $sTime
									, 'comment' => $sComment
								  )
							);
							/*
							 // string
							 comment;
							 groupLevel;
							 id;
							 roleLevelId;
							 timeSpent;

							 // dateTime
							 startDate;

							 // long
							 timeSpentInSeconds;
							 */
						}catch(SoapFault $e){
							// @TODO: Decide on error handling...
							throw new Exception('Error logging hours to ticket: ' . $e->getMessage());
						}
					}#if
				}#if
			}#if
		}

		protected function startProgressOnTicket(SoapClient $p_oClient, $p_sTicketId)
		{
			// Start Progress if it has not already been started
			$oTicket = $p_oClient->getIssue($this->m_aConnector['oAuthentication'], $p_sTicketId);
			if (isset($oTicket->status)) {
				$aStatuses = $p_oClient->getStatuses($this->m_aConnector['oAuthentication']);

				foreach ($aStatuses as $t_oStatus)
				{
					if ($t_oStatus->id === $oTicket->status) {
						$oStatus = $t_oStatus;
						break;
					}
					#if
				}
				#foreach

				if (isset($oStatus) && $oStatus->name === 'Open') {
					$aActions = $p_oClient->getAvailableActions($this->m_aConnector['oAuthentication'], $p_sTicketId);
					foreach ($aActions as $t_oAction)
					{
						if ($t_oAction->name === 'Start Progress') {
							$iAction = $t_oAction->id;
						}
						#if
					}
					#foreach

					if (isset($iAction)) {
						$sComment = '<started working on ticket>'. "\n"
							. ' ---'. "\n" . ' logged via ' . __CLASS__
						;

						$p_oClient->progressWorkflowAction(
							$this->m_aConnector['oAuthentication']
							, $p_sTicketId
							, $iAction
							, array('comment' => $sComment)
						);
					}
					#if
				}
				#if
			}
			#if
		}

		public function provideTags(Array $p_aTags, Context $p_oContext)
		{
			// http://docs.atlassian.com/software/jira/docs/api/rpc-jira-plugin/latest/com/atlassian/jira/rpc/soap/JiraSoapService.html
			$aParsedTags = $p_aTags;

			//@TODO: Check if local cache... saves hitting the server too much
			//@TODO: Have a "Force cache" button somewhere and a "Cache Lifetime" setting
			/*
			$bUseCache=false;
			if($bUseCache === true){
				$sCacheName = md5($this->m_aConnector['Wsdl'] . $p_oContext->get('sToday'));
				$aParsedTags = getTagsFromCache($sCacheName);
			}
			else*/

			$oClient = $this->getClient();

			if($oClient !== null){
				$aPrefixes = $p_oContext->getTagPrefixes();

				try {
					$aIssues = $oClient->getIssuesFromFilter($this->m_aConnector['oAuthentication'], $this->m_aConnector['FilterId']);
					foreach($aIssues as $oIssue){
						$tag = $this->m_aConnector['Name'] . ' ' . Tags::stripSpaces($aPrefixes['Project'] . $oIssue->project);
						if(false){
							// @TODO: check for non-custom groups
						}
						elseif(isset($oIssue->components[0])){
							$tag .= ' ' . Tags::stripSpaces($aPrefixes['Group'] . $oIssue->components[0]->name); // $oIssue->components[0]->id);
						}#if
						$tag .= ' ' . Tags::stripSpaces($aPrefixes['Task'] . $oIssue->summary);//, $oIssue->id); // or $oIssue->key ?
						$tag .= ' ' . Tags::stripSpaces($aPrefixes['Ticket'] . $oIssue->key);

						$aParsedTags[] = Tags::tagArray($p_oContext, 'Meta', $tag, $tag);
					}#foreach
				}catch(SoapFault $e){
					$aParsedTags[] = Tags::tagArray($p_oContext, '__ERROR__', $e->getMessage(), '');
				}#catch
			} else {
				// @TODO: replace ~ with meta prefix
				$aParsedTags[] = Tags::tagArray($p_oContext, '__ERROR__', '~'.implode(', ', $this->m_aErrors), '');
			}#if

			return $aParsedTags;
		}

//////////////////////////////// Helper Methods \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		protected function getClient()
		{
			static $oClient;

			if($oClient === null){
//					$rFilePointer = fsockopen($this->m_aConnector['Wsdl'], 80, $iError, $sError, 30);
					if(!function_exists('use_soap_error_handler')){
						$this->m_aErrors[] = 'The SOAP extension is not installed!';
					}
//					else if($rFilePointer === false){
//						$this->m_aErrors[] = 'Could not reach server "'.$this->m_aConnector['Wsdl'].'".';
//					}
					else{
//						fclose($rFilePointer); // No longer needed
						use_soap_error_handler(false);

						try {
							$t_oClient = new SoapClient($this->m_aConnector['Wsdl'], array('exceptions'=>true));
							$oAuthentication = $t_oClient->login($this->m_aConnector['User'], $this->m_aConnector['Password']);
							if ($oAuthentication !== null){
								$this->m_aConnector['oAuthentication'] = $oAuthentication;
								$oClient = $t_oClient;
							}#if
						}catch(SoapFault $e){
							$this->m_aErrors[] = $e->getMessage();
						}#try
					}#if

				//}#if
			}#if

			return $oClient;
		}

		protected function dateIntervalToSeconds(DateInterval $p_oDateInterval)
		{
			return
				   $p_oDateInterval->s
				+ ($p_oDateInterval->h * 60 *60)
				+ ($p_oDateInterval->d * 24 * 60 * 60)
//				+ ($p_oDateInterval->m * 30 * 24 * 60 * 60)
//				+ ($p_oDateInterval->y * 365 * 24 * 60 * 60)
			;
		}

        protected function getTimeStringFromDateInterval(DateInterval $p_oDateInterval)
        {
			$sTime = '';

			// Round up any seconds to one minute
			if($p_oDateInterval->s > 0){
				$p_oDateInterval->i = $p_oDateInterval->i + 1;
			}#if

			//@NOTE: JIRA supports weeks, but we do not
			if($p_oDateInterval->d > 0){
				$sTime .= $p_oDateInterval->d . 'd ';
			}#if

			if($p_oDateInterval->h > 0){
				$sTime .= $p_oDateInterval->h . 'h ';
			}#if

			if($p_oDateInterval->i > 0){
				$sTime .= $p_oDateInterval->i . 'm';
			}#if

			return $sTime;
		}
	}
}
#EOF