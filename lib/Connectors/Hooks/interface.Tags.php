<?php

namespace DarkHelmet\Connectors\Hooks
{
	use DarkHelmet\Core\Context;

	interface Tags extends Base {
		public function provideTags(Array $p_aTags, Context $p_oContext);
	}
}

#EOF