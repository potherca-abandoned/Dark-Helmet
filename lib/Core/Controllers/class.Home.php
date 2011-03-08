<?php
namespace DarkHelmet\Core\Controllers
{
	use DarkHelmet\Core\Hooks\Persistence as PersistenceHook;

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
	class Home extends Base implements PersistenceHook // Persistence = Handle any $_POST data
	{
		public function __construct()
		{
			parent::__construct();
		}

		public function buildOutput()
		{
			// Things like this really do belong in the init() method
			$this->getContext()
				->setTemplate('main.html')
				->set('bShowForm', true)
			;

			return $this->buildTemplateOutputFromContext($this->getContext());
		}
	}
}
