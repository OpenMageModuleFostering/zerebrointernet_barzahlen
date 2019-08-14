<?php
/**
 * Barzahlen Payment Module for Magento
 *
 * @category    ZerebroInternet
 * @package     ZerebroInternet_Barzahlen
 * @copyright   Copyright (c) 2014 Cash Payment Solutions GmbH (https://www.barzahlen.de)
 * @author      Alexander Diebler
 * @author      Martin Seener
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL-3.0)
 */

class ZerebroInternet_Barzahlen_Model_Api extends ZerebroInternet_Barzahlen_Model_Api_Abstract
{
    protected $_shopId; //!< merchants shop id
    protected $_paymentKey; //!< merchants payment key
    protected $_language = 'de'; //!< langauge code
    protected $_allowLanguages = array('de', 'en'); //!< allowed languages for requests
    protected $_sandbox = false; //!< sandbox settings
    protected $_madeAttempts = 0; //!< performed attempts
    protected $_userAgent = 'PHP SDK v1.1.7';

    /**
     * Constructor. Sets basic settings. Adjusted for Magento
     *
     * @param array $arguements array with settings
     */
    public function __construct(array $arguements)
    {
        $this->_shopId = $arguements['shopId'];
        $this->_paymentKey = $arguements['paymentKey'];
        $this->_sandbox = $arguements['sandbox'];
    }

    /**
     * Sets the language for payment / refund slip.
     *
     * @param string $language Langauge Code (ISO 639-1)
     */
    public function setLanguage($language = 'de')
    {
        if (in_array($language, $this->_allowLanguages)) {
            $this->_language = $language;
        } else {
            $this->_language = $this->_allowLanguages[0];
        }
    }

    /**
     * Sets user agent
     *
     * @param string $userAgent used user agent
     */
    public function setUserAgent($userAgent)
    {
        $this->_userAgent = $userAgent;
    }

    /**
     * Handles request of all kinds.
     *
     * @param Barzahlen_Request $request request that should be made
     */
    public function handleRequest($request)
    {
        $requestArray = $request->buildRequestArray($this->_shopId, $this->_paymentKey, $this->_language);
        $this->_debug("API: Sending request array to Barzahlen.", $requestArray);
        $xmlResponse = $this->_connectToApi($requestArray, $request->getRequestType());
        $this->_debug("API: Received XML response from Barzahlen.", $xmlResponse);
        $request->parseXml($xmlResponse, $this->_paymentKey);
        $this->_debug("API: Parsed XML response and returned it to request object.", $request->getXmlArray());
    }

    /**
     * Connects to Barzahlen Api as long as there's a xml response or maximum attempts are reached.
     *
     * @param array $requestArray array with the information which shall be send via POST
     * @param string $requestType type for request
     * @return xml response from Barzahlen
     */
    protected function _connectToApi(array $requestArray, $requestType)
    {
        $this->_madeAttempts++;
        $curl = $this->_prepareRequest($requestArray, $requestType);

        try {
            return $this->_sendRequest($curl);
        } catch (Exception $e) {
            if ($this->_madeAttempts >= self::MAXATTEMPTS) {
                Mage::throwException($e->getMessage());
            }
            return $this->_connectToApi($requestArray, $requestType);
        }
    }

    /**
     * Prepares the curl request.
     *
     * @param array $requestArray array with the information which shall be send via POST
     * @param string $requestType type of request
     * @return cURL handle object
     */
    protected function _prepareRequest(array $requestArray, $requestType)
    {
        $callDomain = $this->_sandbox ? self::APIDOMAINSANDBOX . $requestType : self::APIDOMAIN . $requestType;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $callDomain);
        curl_setopt($curl, CURLOPT_POST, count($requestArray));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $requestArray);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_USERAGENT, $this->_userAgent);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_CAINFO, dirname(__FILE__) . '/Api/certs/ca-bundle.crt');
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, 1.1);
        return $curl;
    }

    /**
     * Send the information via HTTP POST to the given domain. A xml as anwser is expected.
     * SSL is required for a connection to Barzahlen.
     *
     * @return cURL handle object
     * @return xml response from Barzahlen
     */
    protected function _sendRequest($curl)
    {
        $return = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error != '') {
            Mage::throwException('Error during cURL: ' . $error);
        }

        return $return;
    }
}
