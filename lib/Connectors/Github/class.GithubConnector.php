<?php
namespace DarkHelmet\Connectors\Github
{
	use \DateInterval;

	use DarkHelmet\Core\Context;
	use DarkHelmet\Core\Exception;
	use DarkHelmet\Core\TimeLog;

	use DarkHelmet\Core\Controllers\Tags;

	use DarkHelmet\Connectors\Base;
	use DarkHelmet\Connectors\Hooks\Persistence as PersistenceHook;
	use DarkHelmet\Connectors\Hooks\Tags        as TagsHook;
	use DarkHelmet\Connectors\Hooks\Init        as InitHook;

	class GithubConnector extends Base implements InitHook, TagsHook
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
			$this->m_aConnector['Name']    = $this->getShortName();
			$this->m_aConnector['GitUser'] = $this->getParam('GitUser');
			$this->m_aConnector['GitRepo'] = $this->getParam('GitRepo');

		/* // As we don't support private accounts yet this is not yet needed:
			$aCredentials = $this->getParam('Credentials');
			if(empty($aCredentials)){
				throw new Exception('Could not get credentials');
			}
			else{
				$this->m_aConnector['User']     = $aCredentials['User'];
				$this->m_aConnector['Password'] = $aCredentials['Password'];
			}#if
		*/
		}

		/*
		 * As long as this class does not implement the Persistence Hook this
		 * will not get called.
		 *
		 * What is there to persist, other than creating or closing an issue
		 * (optionally with a comment)? Seems a bit daft, as closing can already
		 * be done through the commit message... Although we could, of course,
		 * always add a comment if one is provided?
		 */
		public function handlePersistenceFor(TimeLog $p_oTimeLog, Context $p_oContext)
		{
			$aEntries = $p_oTimeLog->getEntries();

			//@TODO: The following code is copy/paste straight from the JiraConnector,
			//       Maybe there should be an utility method in Base that gives us this
			//       info?
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
				}#if
			}#if
		}

		public function provideTags(Array $p_aTags, Context $p_oContext)
		{
			// API: http://develop.github.com/ && http://develop.github.com/p/issues.html
			$aParsedTags = $p_aTags;

			//@TODO: I'm guessing that a cache mechanism should also be generic
			//       and provided by the Base
			/*
			$bUseCache=false;
			if($bUseCache === true){
				$sCacheName = md5($this->m_aConnector['Wsdl'] . $p_oContext->get('sToday'));
				$aParsedTags = getTagsFromCache($sCacheName);
			}
			else*/

			//$rFilePointer = fsockopen($this->m_aConnector['Wsdl'], 80, $iError, $sError, 30);

			$sUserName = $this->m_aConnector['GitUser'];
			$sRepoName = $this->m_aConnector['GitRepo'];

			$sUrl =
				  'http://github.com/api/v2/json/issues/list/' . $sUserName
				. '/' . $sRepoName . '/open'
			;

			//@TODO: Poll the server before trying to get content
			$oIssues = \json_decode(
				@\file_get_contents($sUrl)
			);

			if(empty($oIssues)){
				$aParsedTags[] = Tags::tagArray($p_oContext, '__ERROR__', 'Could not retrieve data from "' . $sUrl .'"', '');
			}else{
				$aIssues = $oIssues->issues;

				$aPrefixes = $p_oContext->getTagPrefixes();

				try {
					foreach($aIssues as $t_oIssue){
						if($t_oIssue !== null){
							$tag = $this->m_aConnector['Name'] . ' ' . $aPrefixes['Project'] . $sRepoName;
							if(isset($t_oIssue->labels[0])){
								foreach($t_oIssue->labels as $t_sLabel){
									$tag .= ' ' . Tags::stripSpaces($aPrefixes['Group'] . $t_sLabel);
								}
							}#if
							$tag .= ' ' . Tags::stripSpaces($aPrefixes['Task'] . $t_oIssue->title);
							$tag .= ' ' . Tags::stripSpaces($aPrefixes['Ticket'] . 'GH-' . $t_oIssue->number);

							$aParsedTags[] = Tags::tagArray($p_oContext, 'Meta', $tag, $tag);
						}#if
					}#foreach
				}catch(\Exception $e){
					$aParsedTags[] = Tags::tagArray($p_oContext, '__ERROR__', $e->getMessage(), '');
				}#catch
			}#if

			return $aParsedTags;
		}

//////////////////////////////// Helper Methods \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
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