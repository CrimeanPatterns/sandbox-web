<?php

namespace AwardWallet\Common\OneTimeCode;

class ProviderQuestionAnalyzer
{
    /**
     * @param string $providerCode
     * @param string $question
     * @return bool true if $question is the security question asking for email otc
     */
    public static function isQuestionOtc(string $providerCode, string $question): bool
    {
        return self::isProviderOtc($providerCode)
            && forward_static_call([self::getAnalyzerClass($providerCode), 'isOtcQuestion'], $question);
    }

    /**
     * @return bool true if we support email otc for this provider
     */
    public static function isProviderOtc(string $providerCode): bool
    {
        return class_exists(self::getAnalyzerClass($providerCode))
                && is_a(self::getAnalyzerClass($providerCode), EmailQuestionAnalyzerInterface::class, true);
    }

    /**
     * @return bool true is selenium parser should hold session for when otc is processed
     */
    public static function getHoldsSession(string $providerCode): bool
    {
        return self::isProviderOtc($providerCode)
            && forward_static_call([self::getAnalyzerClass($providerCode), 'getHoldsSession']);
    }

    private static function getAnalyzerClass(string $providerCode): string
    {
        return sprintf('AwardWallet\\Engine\\%s\\QuestionAnalyzer', $providerCode);
    }
}
