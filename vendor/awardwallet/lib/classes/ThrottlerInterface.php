<?php


interface ThrottlerInterface
{
    public function getDelay(string $key, bool $readOnly = false, int $increment = 1) : int;
    public function increment(string $key, int $increment = 1) : void;
    public function getThrottledRequestsCount(string $key) : int;
    public function clear(string $key) : void;
}
