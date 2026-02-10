<?php


namespace AwardWallet\Common\Selenium\HotSession;


class KeepActiveHotSessionResponse
{

    private $logDir;

    private $errors;

    public function __construct(string $logDir, array $errors)
    {
        $this->logDir = $logDir;
        $this->errors = $errors;
    }

    /**
     * @return string
     */
    public function getLogDir(): string
    {
        return $this->logDir;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

}