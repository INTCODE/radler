<?php
/**
 * Copyright © 2017 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magenest\QuickBooksOnline\Model;

use Magenest\QuickBooksOnline\Helper\Oauth as OauthHelper;
use Magento\Framework\Exception\LocalizedException;
use Magenest\QuickBooksOnline\Model\OauthFactory as OauthModelFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Config\Model\Config as ConfigModel;
use Magento\Framework\Validator\Exception;

/**
 * Class Authenticate
 * @package Magenest\QuickBooksOnline\Model
 */
class Authenticate
{
    const HTTP_METHOD_POST = 'POST';
    /**@#+
     * Constants
     */
    const URL_CONNECT_BEGIN = 'https://appcenter.intuit.com/connect/oauth2';
    const URL_REQUEST_TOKEN = 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer';
    const URL_ACCESS_TOKEN  = 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer';
    const URL_TOKEN_END_POINT  = 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer';
    const STATE_QBO_CONNECT = 'RandomState';

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var OauthHelper
     */
    protected $_oauthHelper;

    /**
     * @var OauthModelFactory
     */
    protected $oauthModelFactory;

    /**
     * @var ConfigModel
     */
    protected $configModel;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Client
     */
    protected $redirect_uri;

    /**
     * Authenticate constructor.
     *
     * @param RequestInterface $request
     * @param OauthHelper $oauthHelper
     * @param OauthFactory $oauthModelFactory
     * @param ConfigModel $configModel
     * @param Client $client
     */
    public function __construct(
        RequestInterface $request,
        OauthHelper $oauthHelper,
        OauthModelFactory $oauthModelFactory,
        ConfigModel $configModel,
        Client $client
    ) {
        $this->_request = $request;
        $this->_oauthHelper = $oauthHelper;
        $this->oauthModelFactory = $oauthModelFactory;
        $this->configModel = $configModel;
        $this->client = $client;
    }

    /**
     * Oauth Helper
     *
     * @return OauthHelper
     */
    public function getHelper()
    {
        return $this->_oauthHelper;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getParam($key = '')
    {
        return $this->_request->getParam($key);
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->_request->getParams();
    }


    public function getRedirectUri()
    {
        return $this->redirect_uri;
    }

    /**
     * @param $params
     *
     * @return $this
     */
    public function setRedirectUri($uri)
    {
        $this->redirect_uri = $uri;

        return $this;
    }

    /**
     * Authenticate Url
     *
     * @param $callBackUrl
     * @return string
     * @throws LocalizedException
     */
    public function redirectUrl($callBackUrl)
    {
        $desUrl = self::URL_CONNECT_BEGIN
            . '?client_id=' . $this->getHelper()->getClientId()
            . '&scope=com.intuit.quickbooks.accounting'
            . '&redirect_uri=' . $callBackUrl
            . '&response_type=code'
            . '&state=' . self::STATE_QBO_CONNECT;

        return $desUrl;
    }

    /**
     * @param $grant_type
     * @param $code
     * @throws Exception
     * @throws LocalizedException
     */
    public function getAccessToken($grant_type, $code)
    {
        $link = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Store\Model\StoreManagerInterface')
                ->getStore()->getBaseUrl() . 'qbonline/connection/success';

        $model = $this->oauthModelFactory->create();
        $parameters = array(
            'client_id' => $this->_oauthHelper->getClientId(),
            'client_secret' => $this->_oauthHelper->getClientSecret(),
            'grant_type' =>  $grant_type,
            'code'=> $code,
            'redirect_uri' => $link
        );

        $tokenEndPointUrl = self::URL_TOKEN_END_POINT;
        //Try catch???
        try {
            $oauth = $this->client->authenticate($tokenEndPointUrl, $parameters, \Zend_Http_Client::POST);
            $params = $this->getParams();
            if (is_array($oauth)
                && !empty($oauth['access_token'])
                && !empty($oauth['refresh_token'])
            ) {
                try {
                    $data = [
                        'oauth_access_token'        => $oauth['access_token'],
                        'refresh_token'             => $oauth['refresh_token'],
                        'qb_realm'                  => $params['realmId']
                    ];

                    $model->saveAccessToken($data);
                    $this->saveToConfigTable($params);
                } catch (\Exception $e) {
                    throw new LocalizedException(
                        __('Something went wrong while saving token: ' . $e->getMessage())
                    );
                }
            } else {
                throw new LocalizedException(
                    __('We can\'t receive the access token. Please try to connect again!')
                );
            }
        } catch (\Zend_Http_Client_Exception $e) {
            throw new Exception(
                __('We can\'t get the access token. Please try to connect again!')
            );
        }
    }

    /**
     * @param $tokenEndPointUrl
     * @param $grant_type
     * @param $refresh_token
     * @return mixed
     */
    public function refreshAccessToken($tokenEndPointUrl, $grant_type, $refresh_token)
    {
        $parameters = array(
            'grant_type' => $grant_type,
            'refresh_token' => $refresh_token
        );

        $authorizationHeaderInfo = $this->generateAuthorizationHeader();
        $http_header = array(
            'Accept' => 'application/json',
            'Authorization' => $authorizationHeaderInfo,
            'Content-Type' => 'application/x-www-form-urlencoded'
        );
        $result = $this->executeRequest($tokenEndPointUrl, $parameters, $http_header, self::HTTP_METHOD_POST);
        return $result;
    }

    /**
     * Prepare Params
     *
     * @param Oauth $model
     *
     * @return array
     * @throws LocalizedException
     */
    protected function prepareParamsForRetrieveAccessToken($model)
    {
        if (!$model->getId()) {
            throw new LocalizedException(
                __('We can\'t fetch the request token from the database!')
            );
        }
        $oauthVerifier = $this->getParam('oauth_verifier');

        if (!$oauthVerifier) {
            throw new LocalizedException(
                __('We can\'t receive Oauth Verifier Token from QuickBooks Online')
            );
        }

        $params = [
            'oauth_token'    => $model->getOauthRequestToken(),
            'oauth_secret'   => $model->getOauthRequestTokenSecret(),
            'oauth_verifier' => $oauthVerifier,
        ];

        return $params;
    }

    /**
     * Save to `core_config_data` table
     *
     * @param $params
     */
    protected function saveToConfigTable($params)
    {
        $this->saveDataByPath(Config::XML_PATH_QBONLINE_IS_CONNECTED, 1);
        $this->saveDataByPath(Config::XML_PATH_QBONLINE_COMPANY_ID, $params['realmId']);
    }

    /**
     * @param $path
     * @param $value
     * @return $this
     * @throws \Exception
     */
    public function saveDataByPath($path, $value)
    {
        $this->configModel->setDataByPath($path, $value);
        $this->configModel->save();

        return $this;
    }
}
