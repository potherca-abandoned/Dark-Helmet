<?php

namespace DarkHelmet\Connectors\Hooks
{
	use DarkHelmet\Core\Context;
	use DarkHelmet\Core\TimeLog;

	interface Persistence extends Base {
		public function handlePersistenceFor(TimeLog $p_oTimeLog, Context $p_oContext);
	}
}