<?php

namespace App\Entity;

/**
 * PhoneApplication.
 */
class PhoneApplication
{
    private $baseUrl;
    private $get = '/oauth/v2/token?';
    private $url;
    private $access_token;
    private $grant_type = 'password';
    private $client_id = '1_60lllmougg840ck8ok4cso4c0w0ws4sgw4scowgscgckk4kcgw';
    private $client_secret = '6a2vvfkrn04c88sw8wsowggo048kcw40ocscok488gcc8swkww';
    private $username = '';
    private $password = '';

    public function __construct()
    {
        $this->url = $this->baseUrl.$this->get;
    }

    public function connexion()
    {
        $getParams = [
            'grant_type' => $this->grant_type,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'username' => $this->username,
            'password' => $this->password,
        ];

        $this->getContentGet($getParams);
    }

    private function getContentGet($getParams = null)
    {
        if (!empty($getParams)) {
            $this->addGetParams($getParams);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $this->url);
        $data = json_decode(curl_exec($ch));
        curl_close($ch);

        $this->setAccessToken($data->access_token);

        return $data;
    }

    public function addGetParams($getParams)
    {
        $first = true;
        foreach ($getParams as $key => $value) {
            if (!$first) {
                $this->url .= '&';
            }
            $this->url .= $key.'='.$value;
            $first = false;
            //    $url .= '/' . $key. '/'. $value;
        }
    }

    /**
     * @return string
     */
    public function getGet()
    {
        return $this->get;
    }

    /**
     * @param string $get
     *
     * @return PhoneApplication
     */
    public function setGet($get)
    {
        $this->get = $get;

        $this->url = $this->baseUrl.$get.'?access_token='.$this->access_token;

        return $this;
    }

    /**
     * @return string
     */
    public function getGrantType()
    {
        return $this->grant_type;
    }

    /**
     * @param string $grant_type
     */
    public function setGrantType($grant_type)
    {
        $this->grant_type = $grant_type;
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->client_id;
    }

    /**
     * @param string $client_id
     */
    public function setClientId($client_id)
    {
        $this->client_id = $client_id;
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->client_secret;
    }

    /**
     * @param string $client_secret
     */
    public function setClientSecret($client_secret)
    {
        $this->client_secret = $client_secret;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @param string $baseUrl
     *
     * @return PhoneApplication
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return PhoneApplication
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    public function getAccessToken()
    {
        return $this->access_token;
    }

    /**
     * @return PhoneApplication
     */
    public function setAccessToken($access_token)
    {
        $this->access_token = $access_token;

        return $this;
    }
}
