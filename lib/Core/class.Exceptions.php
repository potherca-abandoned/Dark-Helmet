<?php

namespace DarkHelmet\Core
{
	// Make sure we can identify Application Exceptions
	class BaseException extends \Exception {}

	// General Application Exceptions
	class Exception extends BaseException {}

    class DeprecatedException extends BaseException {}

	// Specific Application Exceptions
	class TimeLogException extends BaseException {}

	/**
	 * @see http://en.wikiquote.org/wiki/Spaceballs
	 */
	class ExceptionQuotes
	{
		static protected $p_aQuotes = array(
			  'I can\'t breathe in this thing!'
			, 'So, Lone Starr, now you see that evil will always triumph, because good is dumb.'
			, 'Yogurt! Yogurt! [referring to the character] I hate Yogurt! Even with Strawberries!'
			, 'You have the ring. And I see that your Schwartz is as big as mine. Now, let\'s see how well you handle it.'
			, 'What\'s the matter, Colonel Sandurz? Chicken?'
			, 'What have I done?! My brains are going into my feet!'
			, '"Out of order"? Fuck! Even in the future nothing works!'
			, 'Say goodbye to your two best friends- and I don\'t mean your pals in the Winnebago!'
			, 'I\'ll bet she gives great helmet.'
			, 'Commence operation... "Vacu-Suck"!'
			, 'There\'s only one man who would dare give me the raspberry: Lone Starr!'
			, 'I am your father\'s brother\'s nephew\'s cousin\'s former roommate.'
			, 'Come back here, you fat bearded bitch!'
			, 'I knew it! I\'m surrounded by Assholes!'
			, 'Keep firing, Assholes!'
			, 'Aw, buckle this! Ludicrous Speed! Go!'
			, '1-2-3-4-5? That\'s the stupidest combination I\'ve ever heard of in my life! That\'s the kinda thing an idiot would have on his luggage!'
			, 'So Princess Vespa, you thought you could outwit the imperious forces of Planet Spaceball. Well you were wrong. You are now our prisoner and you will be held captive until such time as all the air is transferred from your planet... to ours. (She\'s not in there!)'
			, 'Shit! I hate it when I get my Schwartz twisted!'
		);

		static public function getExceptionForQuote($p_sQuote)
		{
			if(isset(self::$p_aQuotes[$p_sQuote]))
			{
				$sMessage = self::$p_aQuotes[$p_sQuote];
			}
			else {
				$sMessage = $p_sQuote;
			}

			return $sMessage;
		}
	}
}

#EOF