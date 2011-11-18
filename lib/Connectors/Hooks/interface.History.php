<?php

namespace DarkHelmet\Connectors\Hooks
{
	use DarkHelmet\Core\Context;
	use DarkHelmet\Core\TimeLog;

	interface History extends Base {
		public function provideHistory(Context $p_oContext);
	}
}