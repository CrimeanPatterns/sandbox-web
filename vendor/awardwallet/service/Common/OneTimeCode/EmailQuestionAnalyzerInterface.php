<?php


namespace AwardWallet\Common\OneTimeCode;

/**
 * Interface EmailQuestionAnalyzerInterface
 * implemented by \AwardWallet\Engine\<provider>\QuestionAnalyzer
 */
interface EmailQuestionAnalyzerInterface
{

    /**
     * @param string $question
     * @return bool true if $question is a question triggered by email 2fa
     */
    public static function isOtcQuestion(string $question): bool;

    /**
     * @return bool true if selenium instance should hold the session to wait for the otc
     */
    public static function getHoldsSession(): bool;

}
