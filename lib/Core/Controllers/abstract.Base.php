<?php
namespace DarkHelmet\Core\Controllers
{
	use \DateTime;

	use DarkHelmet\Core\Context;
	use DarkHelmet\Core\Exception;
	use DarkHelmet\Core\LogEntry;
	use DarkHelmet\Core\Object;
	use DarkHelmet\Core\Request;
	use DarkHelmet\Core\Settings;
	use DarkHelmet\Core\Template;
	use DarkHelmet\Core\TimeLog;

	use DarkHelmet\Core\Hooks\History as HistoryHook;
	use DarkHelmet\Core\Hooks\Persistence as PersistenceHook;
	use DarkHelmet\Core\Hooks\Tags as TagsHook;

	use DarkHelmet\Connectors\Hooks\Init;
	use DarkHelmet\Connectors\Hooks\History as ConnectorsHistoryHook;
	use DarkHelmet\Connectors\Hooks\Tags as ConnectorsTagsHook;
	use DarkHelmet\Connectors\Hooks\Persistence;

	/*
	 * To avoid confusion, one should be aware that connectors are
	 * applied in the order they are registered in the config.xml
	 *
    //
	 * This application works with the paradigm that there are 2 layers of
	 * responsibility: The first layer are controllers that correspond to an URL
	 * through which the application is accessed, the second are connectors that
	 * provide the controllers with functionality.
	 *
	 * Connectors provide methods that only need to be called for certain
	 * Controllers.
	 *
	 * Each controller should handle their own hooks. The Controllers prescribe
	 * the methods a connector needs to offer to alter the behaviour in a
	 * certain controller.
	 *
	 * For this to work it is *imperative* that Interfaces are defined for
	 * the controller to extend that describe what is expected of the connector
	 * and what behaviour to expect (in/out).
	 *
	//
	 * To build output, a controller can do one of (currently) 2 things:
	 * It can either use the template infrastructure provided by the
	 * project or use a custom method to generate output. To make it as
	 * easy as possible to use a template, the API contains a method for
	 * generating Template output for a given context, so that the code
	 * only needs to compose a context and point it towards a template.
	 *
	//
	 * A question that remains unanswered at this point is how to add or
	 * remove items to the view of this tool... For instance, in it's
	 * most basic incarnation, this tool would not have any form of log
	 * or history. Once a history is available however, there would also
	 * need to be an easy way of navigating it. Adding < and > buttons
	 * on either side of the form would be desirable. Now how could we
	 * add those to the output, without altering the main template?
	 *
	 * I guess for DOM oriented output we could render content from a
	 * separate source (or template), use XPath to locate whatever it is
	 * we want to add to or remove and alter the DOM. If the main template is
	 * sufficiently structured (using IDs for all major players) we could simple
	 * use "insertBefore($oDomNode, $sID)" and "insertAfter($oDomNode, $sID)".
	 *
	 * Ideally, this would mean moving the response to a separate object
	 * that would allow us to set the content-type so we have awareness
	 * as to which tooling we can use to add/remove items.
	 *
	 * Maybe it can be simpler than that... have the logic in a base
	 * class and only expose a single API point:
	 *
	 *      Response::insertContent(Content, Location, Response::Prepend/Response::Append/Response::Replace);
	 *
	 * The location could be an XPath for DOM or a regexp for string content.
	 */

	abstract class Base extends Object {
////////////////////////////////// Properties \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		protected $m_oSettings;
		protected $m_oTimeLog;
		protected $m_oContext;

		static protected $m_oRequest;       //@TODO: This should be a object at some point

////////////////////////////// Getters and Setters \\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		public function setContext(Context $m_oContext)
		{
			$this->m_oContext = $m_oContext;
		}

		/**
		 * @return \DarkHelmet\Core\Context
		 */
		public function getContext()
		{
			return $this->m_oContext;
		}

		/**
		 * @param DarkHelmet\Core\Request $p_oRequest
		 */
		static public function setRequest(Request $p_oRequest)
		{
			self::$m_oRequest = $p_oRequest;
		}

		/**
		 * @return \DarkHelmet\Core\Request
		 */
		static public function getRequest()
		{
			return self::$m_oRequest;
		}
		/**
		 * @param DarkHelmet\Core\Settings $p_oSettings
		 * @return \DarkHelmet\Core\Settings The previous Settings.
		 */
		public function setSettings(Settings $p_oSettings)
		{
			$oOldSettings = $this->m_oSettings;
			$this->m_oSettings = $p_oSettings;
			return $oOldSettings;
		}

		/**
		 * @return \DarkHelmet\Core\Settings The previous Settings.
		 */
		public function getSettings()
		{
			return $this->m_oSettings;
		}

		public function setTimeLog(TimeLog $m_oTimeLog)
		{
			$this->m_oTimeLog = $m_oTimeLog;
		}

		public function getTimeLog()
		{
			return $this->m_oTimeLog;
		}

//////////////////////////////// Public Methods \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		/**
		 * This constructor is protected. Any Controller that extends this class
		 * needs to explicitly declare itself public if it wants to be world accessible.
		 */
		protected function __construct()
		{
			$this->m_oTimeLog = new TimeLog();
			$this->m_oContext = new Context();
		}
		
		final static public function getResponse(Request $p_oRequest, Settings $p_oSettings)
		{

			// Get the appropriate Controller
			$sCall = self::getCallFromUrl($p_oRequest->getUrl(), $p_oSettings->get('BaseUrl'));
			$oInstance = self::getFor($sCall);

			// Set the very minimum we need to function
			//@TODO: Validate we have all the settings we need.
			//       Ideally this should be done in a way that other controllers/connectors can append Settings they require...
			// self::validate($p_oRequest, $p_oSettings);
			// Shouldn't all the relevant settings be put into the context so that we don't have to access the Settings again later?
			$oInstance->setRequest($p_oRequest);
			$oInstance->setSettings($p_oSettings); //?

			//@NOTE: Where we don't have a date yet we set to today as sensible default.
			$sToday = date('Ymd');
			$oToday = new DateTime($sToday);


			//@FIXME: TimeLog doesn't belong here, only in controllers that need it...
			// Setup the current TimeLog
			//@TODO: move this to protected function setupTimeLog(\DateTime $p_oDate, Array $p_aPrefixes){}
			$oTimeLog = $oInstance->getTimeLog();
			$oTimeLog->setTagPrefixes($p_oSettings->getPrefixes());
			$oTimeLog->setDate($oToday);

			$sUrl = '';
			$aPieces = explode('/', $p_oSettings->get('BaseUrl'));
			foreach($aPieces as $t_sPiece){
					$sUrl .= rawurlencode($t_sPiece).'/';
			}#foreach
			$sUrl = str_replace('//','/',$sUrl);

			// Setup the current Context
			//@TODO: Move this to protected function setupContext(){}
			$oContext = $oInstance->getContext()
					->set('sBaseUrl', $sUrl)
					->set('sToday', $sToday)
					->set('bShowForm', true)
					->set('oTimeLog', $oTimeLog)//@CHECKME: Not all Controllers need $oTimeLog... shouldn't this be set elsewhere?
					->set('oDate', $oToday)     //@FIXME: $oContext shouldn't know about any date other than today!
				//        If it needs a date it should take it from the TimeLog
				//->set('sTaskTotalTime', $oTimeLog->outputTaskTotalTime())
			;

			$oContext->setTagPrefixes($p_oSettings->getPrefixes());
			$oContext->setTemplate('main.html');

			try{
				$oInstance->invokeHooks();
			}
			catch(Exception $oException){
				// Kill any redirects
				$oInstance->m_sRedirectUrl = null;

				//Set error to be displayed
				$oInstance->getContext()->addMessage(new \DarkHelmet\Core\Message($oException->getMessage()));
			}#try
			
			//@TODO: This can be cleaned up by using a response object instead of plain strings!
			$sOutput = $oInstance->buildOutput();
			if(isset($oInstance->m_sRedirectUrl)){
//				echo '<a href="' . $oInstance->m_sRedirectUrl .'">--redirect--</a>';
//				return $sOutput;
				header('Location: ' . $oInstance->m_sRedirectUrl, true, 303);
			}
			else {
				return $sOutput;
			}#if

		}
		
		/**
		 * Returns the text (html) that is to be sent to the browser
		 * 
		 * @return string
		 */
		abstract public function buildOutput();

//////////////////////////////// Helper Methods \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		protected function invokeHookFor(\ReflectionMethod $t_oMethodReflector){
			/*
			 * The Hooks for both the Controllers and the Connector are named
			 * the same, so we can call the same $sName in different namespaces.
			 *
			 * We can use reflection to get the method we need to call and call
			 * them in the order they are defined in the interface.
			 */
		}

		protected function invokeHooks()
		{
			$oContext = $this->getContext();
			$aConnectors = $this->getSettings()->getConnectors();

			// Get all hooks (interfaces) the current controller implements
			// Get all the interfaces for the Connector hooks
			// Iterate all Connectors

//@TODO: Implement logic below
//		For this to work we either need to give them all the same params (not pretty)
//      or we need a smarter way of providing the various methods with different params,
//		using reflection and some voodoo magic.
//
//			$oReflector = new \ReflectionClass($oInstance);
//			foreach($oReflector->getInterfaces() as $t_oInstanceInterfaces){
//				$oConnectorHooks = new \ReflectionClass(
//					  '\DarkHelmet\Connectors\Hooks\\'
//					. $t_oInstanceInterfaces->getShortName()
//				);
//				$aMethods = $oConnectorHooks->getMethods(\ReflectionMethod::IS_PUBLIC);
//				foreach($aMethods as $t_oMethodReflector){
//					$oInstance->invokeHookFor($t_oMethodReflector);
//				}#foreach
//			}#foreach

			// Let any Connectors that need it prepare their things.
			foreach($aConnectors as $t_oConnector){
				try{
					if($t_oConnector instanceof Init){
						$t_oConnector->init($oContext);
					}#if
				}catch(Exception $oException){
					$this->getContext()->set('sMessage', $oException->getMessage());
					//@TODO: Remember the offending class as to not call it again!
				}
			}#foreach

////////////////////////////////////////////////////////////////////////////////
//          @TODO: Clean up Controller specific variants
//------------------------------------------------------------------------------
			$aTags = array();
////////////////////////////////////////////////////////////////////////////////

			// Call all methods in the connector in order they are defined in the Interface for that connector
			foreach($aConnectors as $t_oConnector){
				if($this instanceof TagsHook && $t_oConnector instanceof ConnectorsTagsHook){
					//@TODO: Instead of passing the Tags in, they should be kept out so Connectors can't mess with each others tags.
					//@TODO: To actually allow tampering with the tags, we should add something like TagPostProcessor
					try{
						$aTags = $t_oConnector->provideTags($aTags, $oContext);
					}catch(Exception $oException){
						$aTags = array_merge($aTags
							, array(Tags::tagArray($oContext, '__ERROR__', $t_oConnector->getShortName().' '.$oException->getMessage(), ''))
						);
					}
					$this->setTags($aTags);
				}
//				else

                if($this instanceof HistoryHook && $t_oConnector instanceof ConnectorsHistoryHook){
					$t_oConnector->provideHistory($oContext);
				}
//				else

                if(
					   $this->getRequest()->getPostFields() !== array() // can't use !empty()...
					&& $this instanceof PersistenceHook
				    && $t_oConnector instanceof Persistence
				){
					$oTimeLog = $this->populateTimeLogFromPostData();

					if($t_oConnector instanceof Persistence){
						$t_oConnector->handlePersistenceFor(clone $oTimeLog, $oContext);
					}#if

					/////////////////////////////////////////////////////////////////////////////////
					// We only handle persistence on $_POST so we always redirect as
					// well. Currently there is only *one* Controller that handles
					// $_POST, this needs a better level of abstraction before there
					// are more. Maybe require each Persistence controller to provide
					// a location to redirect to?
					////////////////////////////////////////////////////////////////////////////////
					$this->m_sRedirectUrl = $_SERVER['REQUEST_URI'];
				}
				else {
					// What about pre/post-render stuff, Like adding/removing/replacing, as mentioned in start comments?
				}#if
			}#foreach
		}

		/**
		 * @static
		 * @throws Exception
		 * @param  string $p_sCall
		 * @return \DarkHelmet\Core\Controllers\Base
		 */
		static protected function getFor($p_sCall)
		{
			$aParameters = array();
			if(strpos($p_sCall, '/') === false){
				$sCall = $p_sCall;
			}
			else{
				list($sCall, $aParameters) = explode('/', (string) $p_sCall, 2);
			}

			if(!is_array($aParameters)){
				$aParameters = array($aParameters);
			}

			if($sCall === false){
				$sCall = 'home';
			}#if

			$sClass = 'DarkHelmet\Core\Controllers\\' . ucfirst($sCall);

			// Creating a ReflectionClass for a non-existing class throws exceptions
			try {
				$oReflector = new \ReflectionClass($sClass);
			} catch(\Exception $ex) {
				throw new Exception('404 - ' . $sCall);
			}
			
			if(! $oReflector->isInstantiable()) {
				throw new Exception('404 - ' . $sCall);
			}

			$oInstance = $oReflector->newInstanceArgs($aParameters);

			return $oInstance;
		}

		static protected function getCallFromUrl($p_sUrl, $p_sBaseUrl)
		{
			$sCall = urldecode((string) $p_sUrl);

			// Strip query if present
			$sQuestionMark = strrpos($sCall,'?');
			if($sQuestionMark !== false){
				$sCall = substr($sCall, 0, $sQuestionMark);
			}#if

			// Strip prefixed folder(s)
			$sCall = substr($sCall, strlen($p_sBaseUrl));

			// Strip trailing slash
			if(substr($sCall,-1) === '/'){
				$sCall = substr($sCall,0,-1);
			}#if

			return $sCall;
		}


		final protected function buildTemplateOutputFromContext(Context $p_oContext)
		{
			$sOutput = null;

			$oTemplate = new Template();//$this->getTemplate(); ?
			$sTemplatePath = $p_oContext->getTemplate();
			if($sTemplatePath === null){
				throw new Exception('Could not build output as given context does not contain a template path.');
			}
			else{
				$oTemplate->setTemplate($sTemplatePath);
				$oTemplate->setContext($p_oContext);
				$sOutput = $oTemplate->execute();
			}#if

			return $sOutput;
		}

		public function buildTags()
		{
			$aTags = $this->array_unique_multi($this->m_aTags);

			if ($this->getContext()->offsetExists('sMessage')) {
				$sMessage = $this->getContext()->get('sMessage');
				if (!empty($sMessage)) {
					$aTags = array_merge(
						$aTags
						, Tags::tagArray($this->getContext(), '__ERROR__', $sMessage, '')
					);
				}
				#if
			}
			#if

			// Sanitize Tags
			foreach ($aTags as $t_iIndex => $t_aTag) {
				$aTags[$t_iIndex]['caption'] = $this->sanitizeText($t_aTag['caption']);
				$aTags[$t_iIndex]['value'] = $this->sanitizeText($t_aTag['value']);
			}
			
			return $aTags;
		}
		
		/**
		 * Converts the input string into a string that is safe to use in html / json
		 * 
		 * @TODO: Improve this method. Maybe use different methods for caption and value? // AK - 2012-06-05
		 * 
		 * @param string $p_sInput
		 * 
		 * @return string 
		 */
		protected function sanitizeText($p_sInput)
		{
			$sResult = $p_sInput;
			
			// Single quotes seem to cause the browser to hang or respond very slowly.
			$sResult = str_replace("'", '`', $sResult);
			// Double quotes cause invalid html, resulting in parts of the string missing.
			// This solution is not perfect, it hampers search methods.
			$sResult = str_replace('"', '&quot;', $sResult);
			
			return $sResult;
		}

		private function array_unique_multi($p_aArray)
		{
			$aUniqueArray = array();
			$aTempArray   = array();

			foreach($p_aArray as $t_iKey => $t_mValue) {
				$aTempArray[] = serialize($t_mValue);
			}#foreach

			$aTempArray = array_unique($aTempArray);

			foreach($aTempArray as $t_iKey => $t_mValue) {
				$aUniqueArray[] = unserialize($t_mValue);
			}#foreach

			return $aUniqueArray;
		}

		protected function populateTimeLogFromPostData()
		{
			static $oTimeLog;

			if($oTimeLog === null){
				$oTimeLog = $this->getTimeLog();

				$aPostFields = $this->getRequest()->getPostFields();
				if(!empty($aPostFields)){
					$oLogEntry = new LogEntry();

					//@TODO: Special cases should be handled by connectors that implement 'Ticket' or something similar
					if(count($aPostFields) === 1 && strpos($aPostFields[0]{0}, $this->getContext()->getTagPrefix('Ticket')) !== false){
							// Only A ticket ID
							// @TODO: replace post fields with full tag set for ticket
					}else if(strpos($aPostFields[0], $this->getContext()->getTagPrefix('Meta')) !== false){
						// Special Meta Tag
						$sMeta = array_shift($aPostFields);

						if($sMeta{0} === $this->getContext()->getTagPrefix('Meta')
							&& array_search($sMeta{1}, $this->getContext()->getTagPrefixes()) !== false
						){
							$sMeta = substr($sMeta, 1);
						}
						$aPostFields = array_merge(explode(' ', $sMeta), $aPostFields);
					}#if

					$oLogEntry->setMessageFromArray($aPostFields, $oTimeLog->getTagPrefixes());

					$oTimeLog->addEntry($oLogEntry);
				}#if
			}#if

			return $oTimeLog;
		}
	}
}
