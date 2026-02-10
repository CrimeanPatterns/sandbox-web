<?php

namespace AwardWallet\Common\Geo\Google;

use JMS\Serializer\Annotation as JMS;

abstract class GoogleResponse
{
    const STATUS_OK               = 'OK';
    const STATUS_UNKNOWN_ERROR    = 'UNKNOWN_ERROR';
    const STATUS_ZERO_RESULTS     = 'ZERO_RESULTS';
    const STATUS_OVER_QUERY_LIMIT = 'OVER_QUERY_LIMIT';
    const STATUS_REQUEST_DENIED   = 'REQUEST_DENIED';
    const STATUS_INVALID_REQUEST  = 'INVALID_REQUEST';
    const STATUS_NOT_FOUND        = 'NOT_FOUND';

    /**
     * Contains metadata on the request. @link https://developers.google.com/places/web-service/details#PlaceDetailsStatusCodes
     *
     * @JMS\Type("string")
     *
     * @var string
     */
    private $status;

    /**
     * @JMS\SerializedName("html_attributions")
     * @JMS\Type("array<string>")
     *
     * @var string[]|null
     */
    private $htmlAttributions;

    /**
     * Detailed information about the reasons behind the given status code.
     * Returned only when the status code is not OK
     *
     * @JMS\Type("string")
     *
     * @var string|null
     */
    private $errorMessage;

    /**
     * GoogleResponse constructor.
     * @param string $status
     */
    public function __construct(string $status)
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return \string[]
     */
    public function getHtmlAttributions()
    {
        return $this->htmlAttributions;
    }

    /**
     * @param string $htmlAttributions
     * @return $this
     */
    public function setHtmlAttributions(string $htmlAttributions)
    {
        $this->htmlAttributions = $htmlAttributions;

        return $this;
    }

    /**
     * @link $errorMessage
     * @return null|string
     */
    public function getErrorMessage()
    {
        if (isset($this->errorMessage)) {
            return $this->errorMessage;
        }

        switch ($this->getStatus()) {
            case self::STATUS_OK:
                return null;
            case self::STATUS_UNKNOWN_ERROR:
                return 'Unknown error.';
            case self::STATUS_ZERO_RESULTS:
                return 'Reference no longer refers a valid result.';
            case self::STATUS_OVER_QUERY_LIMIT:
                return 'Request limit reached.';
            case self::STATUS_INVALID_REQUEST:
                return 'Invalid request.';
            case self::STATUS_REQUEST_DENIED:
                return 'Request denied.';
            case self::STATUS_NOT_FOUND:
                return 'Nothing found.';
            default:
                return "Unknown status";
        }
    }

    /**
     * @param string $errorMessage
     * @return $this
     */
    public function setErrorMessage(string $errorMessage)
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }
}