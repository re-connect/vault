<?php

namespace App\Entity;

/**
 * RestClient.
 */
class RestClient
{
    private $_url;

    public function setUrl($pUrl): static
    {
        $this->_url = $pUrl;

        return $this;
    }

    public function get($pParams = []): array|false
    {
        return $this->_launch(
            $this->_makeUrl($pParams),
            $this->_createContext('GET')
        );
    }

    protected function _launch($pUrl, $context): array|false
    {
        if (($stream = fopen($pUrl, 'r', false, $context)) !== false) {
            $content = stream_get_contents($stream);
            $header = stream_get_meta_data($stream);
            fclose($stream);

            return ['content' => $content, 'header' => $header];
        } else {
            return false;
        }
    }

    protected function _makeUrl($pParams): string
    {
        return $this->_url
            .(strpos((string) $this->_url, '?') ? '' : '?')
            .http_build_query($pParams);
    }

    protected function _createContext($pMethod, $pContent = null)
    {
        $opts = [
            'http' => [
                'method' => $pMethod,
                'header' => 'Content-type: application/x-www-form-urlencoded',
            ],
        ];
        if (null !== $pContent) {
            if (is_array($pContent)) {
                $pContent = http_build_query($pContent);
            }
            $opts['http']['content'] = $pContent;
        }

        return stream_context_create($opts);
    }

    public function post($pPostParams = [], $pGetParams = []): array|false
    {
        return $this->_launch(
            $this->_makeUrl($pGetParams),
            $this->_createContext('POST', $pPostParams)
        );
    }

    public function put($pContent = null, $pGetParams = []): array|false
    {
        return $this->_launch(
            $this->_makeUrl($pGetParams),
            $this->_createContext('PUT', $pContent)
        );
    }

    public function delete($pContent = null, $pGetParams = []): array|false
    {
        return $this->_launch(
            $this->_makeUrl($pGetParams),
            $this->_createContext('DELETE', $pContent)
        );
    }
}
