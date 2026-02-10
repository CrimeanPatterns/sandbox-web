<?php

trait PriceTools {

    /**
     * @deprecated
     *
     * @param $value
     * @return float|null
     */
    // cost("THB 2,400") return 2.4

	function cost($value)
	{
		if (preg_match('#\d+\s*([,.])\s*\d+\s*[,.]\s*\d+#', $value, $m)) {
			$value = str_replace($m[1], '', $value);
		}

		$value = str_replace(',', '.', $value);
		$value = preg_replace("#[^\d\.]#", '', $value);

        // (float) <- potential bug
		return is_numeric($value)?(float)number_format($value, 2, '.', ''):null;
	}

    /**
     * only for web parsing
     *
     * @param $text
     * @return int|null|string
     */

	function currency($text)
	{
		static $sortedCurrencySymbols;
		static $symbolsSorted = false;

		if (!$symbolsSorted) {
			$sortedCurrencySymbols = $this->currencySymbols;
			// Sort array by length to work correctly with such cases when one symbol could be
			// used for several currencies (e.g. 'R$' for BRL and '$' for USD)
			uasort($sortedCurrencySymbols, function($a, $b){ return mb_strlen($b) - mb_strlen($a); });
			$symbolsSorted = true;
		}

		if (preg_match("#\\$"."C#", $text)) return 'CAD';
        if (preg_match("#CA" . "\\$#", $text)) return 'CAD';
		if (preg_match("#\bIN(|R)\b#i", $text)) return 'INR';
		if (preg_match('#US\s+dollars#i', $text)) return 'USD';
		if (preg_match("#SG\\$#i", $text)) return 'SGD';
		if (preg_match("#р$#i", $text)) return 'RUB';

		if (preg_match("#\b($this->currencyCodes)#i", $text, $m))
			return strtoupper($m[1]);

		if (preg_match("#\b(EUR)O\b#i", $text, $m))
			return strtoupper($m[1]);

		foreach ($sortedCurrencySymbols as $key => $value) {
			if (mb_stripos($text, $value) !== false)
				return $key;
		}

		return null;
	}

    /**
     * @deprecated
     *
     * @param        $costAndCurrency
     * @param string $totalLabel
     * @return array
     */

	function total($costAndCurrency, $totalLabel = 'TotalCharge')
	{
		return [
			$totalLabel => $this->cost($costAndCurrency),
			'Currency' => $this->currency($costAndCurrency)
		];
	}

	// Currency symbols from http://www.xe.com/symbols.php
	// Duplicates and ambiguous symbols are commented (maybe they could be used in future)
	var $currencySymbols = [
		'ALL' => 'Lek',
		'AFN' => '؋',
// 		'ARS' => '$',
//		'AWG' => 'ƒ',
// 		'AUD' => '$',
// 		'AZN' => 'ман',
// 		'BSD' => '$',
// 		'BBD' => '$',
// 		'BYR' => 'p.',
		'BZD' => 'BZ$',
// 		'BMD' => '$',
		'BOB' => '$b',
// 		'BAM' => 'KM',
// 		'BWP' => 'P',
// 		'BGN' => 'лв',
		'BRL' => 'R$',
// 		'BND' => '$',
		'KHR' => '៛',
// 		'CAD' => '$',
// 		'KYD' => '$',
// 		'CLP' => '$',
// 		'CNY' => '¥',
// 		'COP' => '$',
		'CRC' => '₡',
// 		'HRK' => 'kn',
// 		'CUP' => '₱',
		'CZK' => 'Kč',
		'DKK' => 'Dkr',
		'DOP' => 'RD$',
// 		'XCD' => '$',
// 		'EGP' => '£',
// 		'SVC' => '$',
// 		'EEK' => 'kr',
		'EUR' => '€',
// 		'FKP' => '£',
// 		'FJD' => '$',
		'GHC' => '¢',
// 		'GIP' => '£',
		'GTQ' => 'Q',
// 		'GGP' => '£',
// 		'GYD' => '$',
		'HNL' => 'L',
 		'HKD' => 'HK$',
		'HUF' => 'Ft',
// 		'ISK' => 'kr',
		'INR' => 'Rs.',
		'IDR' => 'Rp',
// 		'IRR' => '﷼',
// 		'IMP' => '£',
		'ILS' => '₪',
		'JMD' => 'J$',
// 		'JPY' => '¥',
 		'JP¥' => 'JP¥',
// 		'JEP' => '£',
// 		'KZT' => 'лв',
// 		'KPW' => '₩',
// 		'KRW' => '₩',
// 		'KGS' => 'лв',
		'LAK' => '₭',
// 		'LVL' => 'Ls',
// 		'LBP' => '£',
// 		'LRD' => '$',
// 		'LTL' => 'Lt',
// 		'MKD' => 'ден',
// 		'MYR' => 'RM',
// 		'MUR' => '₨',
// 		'MXN' => '$',
		'MNT' => '₮',
// 		'MZN' => 'MT',
// 		'NAD' => '$',
// 		'NPR' => '₨',
// 		'ANG' => 'ƒ',
// 		'NZD' => '$',
		'NIO' => 'C$',
		'NGN' => '₦',
// 		'KPW' => '₩',
		'NOK' => 'kr',
// 		'OMR' => '﷼',
// 		'PKR' => '₨',
		'PAB' => 'B/.',
// 		'PYG' => 'Gs',
		'PEN' => 'S/.',
// 		'PHP' => '₱',
		'PLN' => 'zł',
// 		'QAR' => '﷼',
// 		'RON' => 'lei',
		'RUB' => 'руб',
// 		'SHP' => '£',
// 		'SAR' => '﷼',
		'RSD' => 'Дин.',
// 		'SCR' => '₨',
// 		'SGD' => '$',
// 		'SBD' => '$',
// 		'SOS' => 'S',
// 		'ZAR' => 'R',
// 		'KRW' => '₩',
// 		'LKR' => '₨',
// 		'SEK' => 'kr',
		'CHF' => 'CHF',
// 		'SRD' => '$',
// 		'SYP' => '£',
		'TWD' => 'NT$',
		'THB' => '฿',
		'TTD' => 'TT$',
// 		'TRY' => '',
		'TRL' => '₤',
// 		'TVD' => '$',
		'UAH' => '₴',
		'GBP' => '£',
		'USD' => '$',
		'UYU' => '$U',
// 		'UZS' => 'лв',
// 		'VEF' => 'Bs',
		'VND' => '₫',
// 		'YER' => '﷼',
		'ZWD' => 'Z$'
	];

	var $currencyCodes = "AED|AFN|ALL|AMD|ANG|AOA|ARS|AUD|AWG|AZN|BAM|BBD|BDT|BGN|BHD|BIF|BMD|BND|BOB|BOV|BRL|BSD|BTN|BWP|BYR|BZD|CAD|CDF|CHE|CHF|CHW|CLF|CLP|CNY|COP|COU|CRC|CUC|CUP|CVE|CZK|DJF|DKK|DOP|DZD|EGP|ERN|ETB|EUR|FJD|FKP|GBP|GEL|GHS|GIP|GMD|GNF|GTQ|GYD|HKD|HNL|HRK|HTG|HUF|IDR|ILS|INR|IQD|IRR|ISK|JMD|JOD|JPY|KES|KGS|KHR|KMF|KPW|KRW|KWD|KYD|KZT|LAK|LBP|LKR|LRD|LSL|LTL|LYD|MAD|MDL|MGA|MKD|MMK|MNT|MOP|MRO|MUR|MVR|MWK|MXN|MXV|MYR|MZN|NAD|NGN|NIO|NOK|NPR|NZD|OMR|PAB|PEN|PGK|PHP|PKR|PLN|PYG|QAR|RON|RSD|RUB|RWF|SAR|SBD|SCR|SDG|SEK|SGD|SHP|SLL|SOS|SRD|SSP|STD|SYP|SZL|THB|TJS|TMT|TND|TOP|TRY|TTD|TWD|TZS|UAH|UGX|USD|USN|USS|UYI|UYU|UZS|VEF|VND|VUV|WST|XAF|XAG|XAU|XBA|XBB|XBC|XBD|XCD|XDR|XFU|XOF|XPD|XPF|XPT|XTS|XXX|YER|ZAR|ZMW|ZWL";

}
