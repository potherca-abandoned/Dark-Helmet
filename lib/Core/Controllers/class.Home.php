<?php
namespace DarkHelmet\Core\Controllers
{
	use DarkHelmet\Core\Hooks\Persistence as PersistenceHook;
	use DarkHelmet\Core\Hooks\History as HistoryHook;
	use DarkHelmet\Core\Hooks\Tags as TagsHook;

	// @TODO: Maybe the Home Controller should become "Persistence controller"
	//        and be replaced by a configurable Dashboard-style controller?
	// @TODO: Think about Navigation/Menus ...

	/**
	 * This controller provides the most basic functionality for this tool.
	 *
	 * It enables a user to log work to a TimeLog, adding entries for as long as
	 * the session stays alive. All by itself it does not know of any tags or
	 * previous state other than the current session.
	 *
	 * To be able to add support for tags, (local) storage and/or a history,
	 * several Connectors need to be registered to the tool in the settings.xml.
	 *
	 * @NOTE: At this point it untested whether or not the sessions hold beyond midnight.
	 */
	class Home extends Base implements PersistenceHook  // Persistence = Handle any $_POST data
                                     , HistoryHook
                                     , TagsHook
	{
		protected $m_aTags = array();

////////////////////////////// Getters and Setters \\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		public function setTags(Array $p_aTags){
			//@TODO: $this->validateTags($aTags);
			$this->m_aTags = $p_aTags;
		}
		public function getTags(Array $p_aTags){
			//@TODO: $aTags = array_merge_unique($aTags);
			return $this->m_aTags;
		}

//////////////////////////////// Public Methods \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		public function __construct()
		{
			parent::__construct();
		}

		public function buildOutput()
		{
			// Things like this really do belong in the init() method

			$oTagsController = new Tags();
			$oTagsController->setContext($this->getContext());
			$aTagsList = $oTagsController->buildTags();


			$this->getContext()
				->setTemplate('main.html')
				->set('bShowForm', true)
				->set('aList', $aTagsList)
			;

			return $this->buildTemplateOutputFromContext($this->getContext());
		}
	}
}
