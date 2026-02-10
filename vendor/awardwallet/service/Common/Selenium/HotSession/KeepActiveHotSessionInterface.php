<?php

namespace AwardWallet\Common\Selenium\HotSession;


interface KeepActiveHotSessionInterface
{
    // on/off
    public function isActive(): bool;

    // how often refresh (in minutes)
    public function getInterval(): int;

    // how much for each prefix (? +accountKey)
    public function getCountToKeep(): int;

    // date from which it is necessary to keep the session (what was previously is irrelevant)
    // if null - keep all
    public function getAfterDateTime(): ?int;

    // maximum hot session lifetime (in minutes)
    // if null - no limit (do not close sessions by lifetime)
    public function getLimitLifeTime(): ?int;

    // main action (page refresh, clicks, status check) return true if success, otherwise false
    public function run(): bool;

}