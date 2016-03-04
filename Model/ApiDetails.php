<?php


namespace Werkspot\BingAdsApiBundle\Model;


class ApiDetails
{
    /**
     * @var string
     */
    private $refreshToken;

    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $secret;

    /**
     * @var string
     */
    private $redirectUri;

    /**
     * @var string
     */
    private $devToken;

    /**
     * @param string $refreshToken
     * @param string $clientId
     * @param string $secret
     * @param string $redirectUri
     * @param string $devToken
     */
    public function __construct($refreshToken, $clientId, $secret, $redirectUri, $devToken)
    {
        $this->refreshToken = $refreshToken;
        $this->clientId = $clientId;
        $this->secret = $secret;
        $this->redirectUri = $redirectUri;
        $this->devToken = $devToken;
    }

    /**
     * @param string $refreshToken
     */
    public function setRefreshToken($refreshToken)
    {
        $this->refreshToken = $refreshToken;
    }

    /**
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * @return string
     */
    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    /**
     * @return string
     */
    public function getDevToken()
    {
        return $this->devToken;
    }



}
