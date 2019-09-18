<?php

namespace DarkHelmet\Core
{
    class Request {

////////////////////////////////// Properties \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		protected $m_sUrl = '';
	    protected $m_aPostFields = array();

////////////////////////////// Getters and Setters \\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		public function setPostFields(Array $p_aPostFields)
	    {
		    $this->m_aPostFields = $p_aPostFields;
	    }

        /**
         * @return array
         */
	    public function getPostFields()
	    {
		    return $this->m_aPostFields;
	    }

	    public function setUrl($p_sUrl)
	    {
		    $this->m_sUrl = (string) $p_sUrl;
	    }

        /**
         * @return string
         */
		public function getUrl()
		{
			return $this->m_sUrl;
		}

        /**
         * @deprecated Has been deprecated as the plugins make it ambiguous what the base-url is. Use getParamsFor() instead.
         *
         * @return array
         */
		public function getParams()
		{
            throw new DeprecatedException('Use getParamsFor() instead');
        }

        /**
         * Get the parameters from the Request for a given base URL.
         *
         * Imagine that the application is reachable online at
         *  http://example.com/DarkHelmet and a plugin called 'foo' has an
         * action method called 'bar' that is called with a parameters 'baz',
         * 'biz' and 'boz', then the URL for the current request would be
         * '/DarkHelmet/foo/bar/baz/biz/boz'.
         *
         * To get the parameters for the 'bar' action method we would call this
         * method like this: $oRequest->getParamsFor('/foo/bar'); and we would
         * receive array('baz', 'biz', 'boz').
         *
         * @param $p_sBaseUrl
         *
         * @return array
         */
		public function getParamsFor($p_sBaseUrl)
		{
			$sUrl = strtolower($p_sBaseUrl);
			$iStart = strpos($this->m_sUrl, $sUrl) + strlen($sUrl);
			$sParameters = substr($this->m_sUrl, $iStart);
			$aParameters = preg_split('#/#', $sParameters, null, PREG_SPLIT_NO_EMPTY);

			return $aParameters;
		}
//////////////////////////////// Public Methods \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
	    /**
         * @static
         * @param $p_sUrl
         * @param array $p_aPostFields
         * @return \DarkHelmet\Core\Request
         */
        static public function get($p_sUrl, Array $p_aPostFields){
			$oInstance = new static;

			$oInstance->setUrl($p_sUrl);
			$oInstance->setPostFields($p_aPostFields);

			return $oInstance;
        }

        /**
         * @return string
         */
		public function __toString()
		{
			return (string) $this->getUrl();
		}


    }
}

#EOF