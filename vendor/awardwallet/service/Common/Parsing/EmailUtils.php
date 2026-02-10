<?php

namespace AwardWallet\Common\Parsing;


class EmailUtils
{

    public function hasBoardingPass($emailSource)
    {
        $parser = new \PlancakeEmailParser($emailSource);

        $subject = $parser->getSubject();

        $dictionary = $this->getDictionaryBoardingPass();
        foreach ($dictionary as $word) {
            if (mb_stripos($subject, $word) !== false) {
                return true;
            }
        }
        $pattern = '.*(?:'
            . implode('|', array_map(function ($s) {
                return preg_replace("/\s+/", '\s*', preg_quote($s, '/'));
            }, $dictionary))
            . ').*';

        $attachs = $parser->getAttachments();
        foreach ($attachs as $attach) {
            if (!empty($attach['headers']) && is_array($attach['headers'])) {
                foreach ($attach['headers'] as $header) {
                    if (!is_array($header)
                        && (preg_match("/name\*?=\s*(['\"]){$pattern}\\1/iu", $header)
                            || preg_match("/name\*?={$pattern}$/iu", $header)
                            || preg_match("/name\*?=\s*(['\"]).+\.pkpass\\1/iu", $header) // *.pkpass
                            || preg_match("/name\*?=.+\.pkpass$/iu", $header)
                            || preg_match("/name\*?=['\"]{0,1}e\-BP\b/u", $header) // e-BP
                        )
                    ) {
                        return true;
                    }
                }
            }
        }
        foreach ($dictionary as $word) {
            if (mb_stripos($parser->getHTMLBody(), $word) !== false || mb_stripos($parser->getPlainBody(), $word) !== false) {
                return true;
            }
        }
        return false;
    }

    private function getDictionaryBoardingPass(): array
    {
        return [
            'passbook',
            'boarding-ps',
            'boarding pass',
            'boarding-pass',
            'brottfarakortið', // is
            'bordkarte', // de
            'Cartão de embarque', // pt
            'Cartao de embarque', // pt
            'Cartaodeembarque', // pt
            'Tarjeta de embarque',// es
            'Carte d\'embarquement',//fr
            'Carta d\'imbarco', // it
            'Beszállókártya', // hu
            'Boardingpass', // no
            'kartę pokładową', // pl
        ];
    }
}