<?php
namespace DarkHelmet\Core\Controllers
{
	use \SoapClient;
	use \SoapFault;

	use DarkHelmet\Core\Context;
	use DarkHelmet\Core\Settings;
	use DarkHelmet\Core\Hooks\Tags as TagsHook;

	class Tags extends Base implements TagsHook
	{
////////////////////////////////// Properties \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
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
			//@TODO: The header "Content-Type" should not be set here, but marked in the response.
			if(headers_sent() === false){
				header('Content-Type: text/plain;');
			}#if

			return json_encode($this->buildTags(), JSON_HEX_TAG);//|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
		}

//////////////////////////////// Helper Methods \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		protected function getPrefixes()
		{
			return $this->getSettings()->getPrefixes();
		}

#==============================================================================#
#   Utility methods: These still need serious refactoring!
#------------------------------------------------------------------------------#
		/**
		 * Replace underscores with an HTML entity and replace spaces with underscores
		 *
		 * @param string $p_sString
		 *
		 * @return string
		 */
		final static public function stripSpaces($p_sString)
		{
			$sReplacement       = '_';
			$sReplacementEntity = '&#95;';

			$sString = str_replace($sReplacement, $sReplacementEntity, $p_sString);
			$sString = str_replace(' ', $sReplacement, $sString);

			return $sString;
		}

		/**
		 * Replace underscores with spaces and replace underscore HTML entities with underscores.
		 *
		 * @param string $p_sString
		 * @return string
		 */
		final static public function unStripSpaces($p_sString)
		{
			$sReplacement       = '_';
			$sReplacementEntity = '&#95;';

			$sString = str_replace($sReplacement, ' ', $p_sString);
			$sString = str_replace($sReplacementEntity, $sReplacement, $sString);

			return $sString;
		}

		/**
		 *
		 * @param \DarkHelmet\Core\Context $p_oContext
		 * @param  string $p_sCategory
		 * @param  string $p_sCaption
		 * @param  string $p_sValue
		 *
		 * @return array
		 */
		final static public function tagArray(Context $p_oContext, $p_sCategory, $p_sCaption, $p_sValue)
		{
			$aPrefixes = $p_oContext->get('aPrefix');
			if(array_key_exists($p_sCategory, $aPrefixes)){

				$sPrefix = $aPrefixes[$p_sCategory];
			}
			else{
				$sPrefix = $p_sCategory . ': ';
			}

			return array(
				  'caption' => $sPrefix . Tags::unStripSpaces($p_sCaption)
				, 'value' => $sPrefix . $p_sValue
				, 'addClass' => $p_sCategory
			);
		}
#==============================================================================#
	}
}
