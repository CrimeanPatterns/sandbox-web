<?php

namespace AwardWallet\Schema\Parser\ParserTraits;

use Psr\Log\LoggerInterface;

/**
 * @property LoggerInterface $logger
 */
trait TextTrait
{

    /**
     * returns:
     *   - first group if match and there are groups
     *   - entire match if match and no groups
     *   - null if no match
     */
    private function findPreg($pattern, $subject) : ?string
    {
        if (preg_match($pattern, $subject, $matches)) {
            return $matches[1] ?? $matches[0];
        }

        $this->logger->info("regexp not found: $pattern");

        return null;
    }

}