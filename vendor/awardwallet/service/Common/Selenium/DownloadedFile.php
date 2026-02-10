<?php

namespace AwardWallet\Common\Selenium;

class DownloadedFile
{

    /**
     * @var string
     */
    private $name;
    /**
     * @var int
     */
    private $size;
    /**
     * @var string
     */
    private $contents;

    public function __construct(string $name, int $size, string $contents)
    {
        $this->name = $name;
        $this->size = $size;
        $this->contents = $contents;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getContents(): string
    {
        return $this->contents;
    }

}
