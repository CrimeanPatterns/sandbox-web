<?php

namespace AwardWallet\ExtensionWorker;

/**
 * @link https://developer.mozilla.org/en-US/docs/Web/API/Response
 */
class FetchResponse
{

    public string $body;
    public array $headers;
    public int $status;
    public string $statusText;
    public string $type;
    public string $url;

}