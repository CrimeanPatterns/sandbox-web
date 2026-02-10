<?php


namespace AwardWallet\Common\Parsing\LuminatiProxyManager;


class Port
{
    private $data = [
        'proxy' => [
            'ssl' => true,
            'tls_lib' => 'open_ssl',
        ],
        'create_users' => false
    ];

    /**
     * @param int $port
     * @return $this
     */
    public function setProxyPort(int $port): self
    {
        $this->data['proxy']['port'] = $port;

        return $this;
    }

    /**
     * Set the number of proxy ports to be created with the current settings
     * @param int|null $cnt
     * @return $this
     */
    public function setMultiplyProxyPort(?int $cnt = null): self
    {
        if ($cnt) {
            $this->data['proxy']['multiply'] = $cnt;
        } else {
            unset($this->data['proxy']['multiply']);
        }

        return $this;
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setEnableSslAnalyzing(bool $value): self
    {
        $this->data['proxy']['ssl'] = $value;

        return $this;
    }

    /**
     * @param string $lib values: 'open_ssl' | 'flex_tls'
     * @return $this
     */
    public function setTlsLib(string $lib): self
    {
        if (in_array($lib, ['open_ssl', 'flex_tls'])) {
            $this->data['proxy']['tls_lib'] = $lib;
        }

        return $this;
    }

    /**
     * @param string $type values: 'http' | 'https' | 'socks'
     * @return $this
     */
    public function setProxyConnectionType(string $type): self
    {
        if (in_array($type, ['http', 'https', 'socks'])) {
            $this->data['proxy']['proxy_connection_type'] = $type;
        }

        return $this;
    }

    /**
     * @param array $extProxies example: ['1.1.1.2', 'my_username:my_password@1.2.3.4:8888']
     * @return $this
     */
    public function setExternalProxy(array $extProxies): self
    {
        $this->data['proxy']['ext_proxies'] = $extProxies;
        $this->data['proxy']['internal_name'] = time();

        if (count($extProxies) > 1) {
            $this->data['proxy']['preset'] = 'rotating';
            $this->data['proxy']['rotate_session'] = true;
        }

        return $this;
    }

    /**
     * @param array|null $types
     * @return $this
     */
    public function banMediaContent(?array $types = null): self
    {
        $rules = $this->getNullResponseTemplate();

        $types = $types ?? ['jpg', 'png', 'jpeg', 'svg', 'gif', 'mp3', 'mp4', 'avi'];
        $typesString = '';

        foreach ($types as $type) {
            $typesString .= $type . '|';
        }

        $typesString = substr($typesString, 0, strlen($typesString) - 1);

        $rules['url'] = "\\.({$typesString})(#.*|\\?.*)?$";

        $this->data['proxy']['rules'][] = $rules;

        return $this;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setBanUrlContent(string $url): self
    {
        $rules = $this->getNullResponseTemplate();

        $rules['url'] = $url;

        $this->data['proxy']['rules'][] = $rules;

        return $this;
    }

    /**
     * @internal
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    private function getNullResponseTemplate()
    {
        return [
            'action' => [
                'null_response' => true
            ],
            'action_type' => 'null_response',
            'trigger_type' => 'url',
            'url' => null
        ];
    }
}