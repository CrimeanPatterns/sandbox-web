<?php

namespace AwardWallet\ExtensionWorker;

class ParseHistoryOptions
{

    private ?\DateTimeInterface $startDate;
    /**
     * @var array - ['subAccountCode' => ?\DateTimeInterface]
     */
    private array $subAccountStartDates;
    private bool $strictHistoryStartDate;

    public function __construct(?\DateTimeInterface $startDate, array $subAccountStartDates, bool $strictHistoryStartDate)
    {

        $this->startDate = $startDate;
        $this->subAccountStartDates = $subAccountStartDates;
        $this->strictHistoryStartDate = $strictHistoryStartDate;
    }

    public static function complete() : self
    {
        return new self(null, [], true);
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function isStrictHistoryStartDate(): bool
    {
        return $this->strictHistoryStartDate;
    }

    public function getSubAccountStartDate(string $subAccountCode): ?\DateTimeInterface
    {
        return $this->subAccountStartDates[$subAccountCode] ?? null;
    }

    /**
     * @return \DateTimeInterface[] - ['subAccountCode' => \DateTimeInterface]
     */
    public function getAllSubAccountStartDates(): array
    {
        return $this->subAccountStartDates;
    }

}