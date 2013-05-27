<?php
namespace DarkHelmet\Core\Hooks
{
	interface Base {
		/**
		 * Returns the text (html) that is to be sent to the browser
		 *
		 * @return string
		 */
		public function buildOutput();
	}
}
#EOF