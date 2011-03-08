<?php

namespace DarkHelmet\Connectors\Hooks
{
	use DarkHelmet\Core\TimeLog;
	use DarkHelmet\Core\Context;

	/**
	 * A controller should implement this interface if it needs to do work
	 * before any of it's other hooks get called.
	 */
	interface Init  extends Base {
		/**
		 * @abstract
		 * @param \DarkHelmet\Core\Context $p_oContext
		 * @return void
		 */
		public function init(Context $p_oContext);
	}
}