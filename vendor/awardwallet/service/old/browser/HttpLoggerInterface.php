<?php

interface HttpLoggerInterface
{
	public function Log($message, $level = null, $htmlEncode = true);
}