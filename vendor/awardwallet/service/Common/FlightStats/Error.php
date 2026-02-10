<?php


namespace AwardWallet\Common\FlightStats;

use JMS\Serializer\Annotation as JMS;

class Error
{
    /**
     * @var int
     * @JMS\Type("integer")
     */
    private $httpStatusCode;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $errorCode;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $errorId;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $errorMessage;

    /**
     * Error constructor.
     * @param int $httpStatusCode
     * @param string $errorCode
     * @param string $errorId
     * @param string $errorMessage
     */
    public function __construct($httpStatusCode, $errorCode, $errorId, $errorMessage)
    {
        $this->httpStatusCode = $httpStatusCode;
        $this->errorCode = $errorCode;
        $this->errorId = $errorId;
        $this->errorMessage = $errorMessage;
    }

    /**
     * @return int
     */
    public function getHttpStatusCode()
    {
        return $this->httpStatusCode;
    }

    /**
     * @return string
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @return string
     */
    public function getErrorId()
    {
        return $this->errorId;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}