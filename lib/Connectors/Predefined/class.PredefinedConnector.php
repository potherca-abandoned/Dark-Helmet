<?php
namespace DarkHelmet\Connectors\Predefined
{
	use DarkHelmet\Core\TimeLog;
	use DarkHelmet\Core\Context;

	use DarkHelmet\Core\Controllers\Tags;

	use DarkHelmet\Connectors\Base;
	use DarkHelmet\Connectors\Hooks\Tags as TagsHook;

	class PredefinedConnector extends Base implements TagsHook
	{
//////////////////////////////// Public Methods \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		public function provideTags(Array $p_aTags, Context $p_oContext)
		{
			$aParsedTags = $p_aTags;
			$aPrefixes = $p_oContext->get('aPrefix');
			$sFilePath   = $this->getParam('TagFilePath');

			if(!is_readable($sFilePath)){
				$aParsedTags[] = array(Tags::tagArray($p_oContext, '__ERROR__', 'Could not read predefined tags file.', ''));
			}
			else
			{
				$aTags = json_decode(file_get_contents($sFilePath),true);
				foreach($aTags as $t_sCategory => $t_aTags) {
					foreach($t_aTags as $t_sTag) {
						$sTag = Tags::stripspaces($t_sTag);

						//@WORKAROUND: If there's an apostrophe (') in a value the JS hangs the browser.
						$sTag = str_replace('\'', '&apos;', $sTag);

						$sCaption = str_replace('_', ' ', $sTag);

						if(isset ($aPrefixes[$t_sCategory])){
							$aParsedTags[] = array(
								  'caption' => $aPrefixes[$t_sCategory] . $sCaption
								, 'value' => $aPrefixes[$t_sCategory] . $sTag
								, 'addClass' => $t_sCategory
							);
						}
					}#foreach
				}#foreach
			}#if

			return $aParsedTags;
		}
//////////////////////////////// Helper Methods \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
	}
}
#EOF