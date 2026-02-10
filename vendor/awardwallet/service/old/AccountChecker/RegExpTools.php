<?php

trait RegExpTools {
	
	// TODO: Extract here more regexp related functions from tools.php (only if they are really needed!!!)

	/**
	 * Perform a regular expression match
	 * Similar to preg_match, flags and options the same, only returning result differs - return match(es) instead of
	 * saving them in passed by reference parameter
	 * @param string $pattern The pattern to search for, as a string.
	 * @param string $subject The input string.
	 * @param int $flags [optional]
	 * @param int $offset [optional]
	 * @return array $matches if regexp contains several groups, value $match if regexp contains one or no groups, null if no match and false on error
	 */
	public function re($pattern, $subject, $flags = 0, $offset = 0) {
		$matchingResult = preg_match($pattern, $subject, $matches, $flags, $offset);
		if ($matchingResult) {
			// Match succeeded, checking different success situations
			if (count($matches) == 1)
				return $matches[0];
			elseif (count($matches) == 2)
				return $matches[1];
			elseif (count($matches) > 2)
				return $matches;
			else
				// Added for 'if' statement completeness, actually it should never happen
				return false;
		} else {
			// Match failed, checking different failure situations
			if ($matchingResult === false)
				return false;
			elseif ($matchingResult === 0)
				return null;
			else
				// Added for 'if' statement completeness, actually it should never happen
				return false;
		}

	}

}

// TODO: Modify to be unit test and extract to separate file
//class RegExpTraitTester {
//
//	use RegExpTools;
//
//	function foo() {
//		$s = 'testing regexp matching function';
//		$testCases = [
//			// TODO: Add expected values
//			[
//				'Pattern' => '#ma.ch#i',
//				'Subject' => $s,
//				'Flags' => 0,
//				'Offset' => 0,
//			],
//			[
//				'Pattern' => '#(match)#i',
//				'Subject' => $s,
//				'Flags' => 0,
//				'Offset' => 0,
//			],
//			[
//				'Pattern' => '#(match).*(func)#i',
//				'Subject' => $s,
//				'Flags' => 0,
//				'Offset' => 0,
//			],
//			[
//				'Pattern' => '#blabla#i',
//				'Subject' => $s,
//				'Flags' => 0,
//				'Offset' => 0,
//			],
//			[
//				'Pattern' => '#blabla',
//				'Subject' => $s,
//				'Flags' => 0,
//				'Offset' => 0,
//			],
//			[
//				'Pattern' => '#ma.ch#i',
//				'Subject' => $s,
//				'Flags' => PREG_OFFSET_CAPTURE,
//				'Offset' => 0,
//			],
//		];
//		$i = 1;
//		foreach ($testCases as $tc) {
//			echo "Testing test case #$i\n";
//			var_dump($this->re($tc['Pattern'], $tc['Subject'], $tc['Flags'], $tc['Offset']));
//			// TODO: Check returned value with expected
//			echo "\n";
//			$i++;
//		}
//	}
//
//}
//
//$o = new RegExpTraitTester();
//$o->foo();