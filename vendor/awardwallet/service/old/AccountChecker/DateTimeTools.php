<?php

trait DateTimeTools {

	// TODO: Extract here more datetime related functions from tools.php (only if they are really needed!!!)

	var $dateTimeToolsMonthNames = array(
		'en' => ['January','February','March','April','May','June','July','August','September','October','November','December'],
		'ru' => ['январь','февраль','март','апрель','май','июнь','июль','август','сентябрь','октябрь','ноябрь','декабрь'],
		'ru2' => ['января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'],
		'ru3' => ['янв','фев','мар','апр','мая','июн','июл','авг','сен','окт','ноя','дек'],
		'de' => ['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'],
		'de3'=> ['jan', 'feb', 'mrz', 'apr', 'mai', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dez'],
		'deA'=> ['jan', 'feb', 'mae', 'apr', 'mai', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dez'], // "mae" different
		'deB'=> ['Januar','Februar','Maerz','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'], // "maerz" different
		'nl' => ['januari','februari','maart','april','mei','juni','juli','augustus','september','oktober','november','december'],
		'nlShort' => ['jan','feb','mrt','apr','mei','jun','jul','aug','sep','okt','nov','dec'],// hertz
		'fr' => ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'],
		'fr1' => ['janv','févr','mars','avr','mai','juin','juil','août','sept','oct','nov','déc'],
		'fr2' => ['janvier','fevrier','mars','avril','mai','juin','juillet','aout','septembre','octobre','novembre','decembre'],
		'es' => ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'],
		'es1' => ['Enero','Feb','Marzo','Abr','Mayo','Jun','Jul','Agosto','Sept','Oct','Nov','Dic'],
		'es2' => ['enero','feb','marzo','abr','mayo','jun','jul','agosto','sept','oct','nov','dic'],
        // https://lts.library.cornell.edu/lts/pp/spp/mosabbr#portuguese
		'pt' => ['janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'],
		'pt1' => ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','non','dez'],
		'it' => ['gennaio','febbraio','marzo','aprile','maggio','giugno','luglio','agosto','settembre','ottobre','novembre','dicembre'],
		'fi' => ['tammikuuta','helmikuuta','maaliskuuta','huhtikuuta','toukokuuta','kesäkuuta','heinäkuuta','elokuuta','syyskuuta','lokakuuta','marraskuuta','joulukuuta'],
		'da' => ["Januar", "Februar", "Marts", "April", "Maj", "Juni", "juli", "August", "September" , "Oktober", "November", "December"],
		'tr' => ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'],
		'pl' => ['styczeń', 'luty', 'marzec', 'kwiecień', 'maj', 'czerwiec', 'lipiec', 'sierpień', 'wrzesień', 'październik', 'listopad', 'grudzień'],
		'pl2' => ['styczen', 'luty', 'marzec', 'kwiecien', 'maj', 'czerwiec', 'lipiec', 'sierpien', 'wrzesien', 'pazdziernik', 'listopad', 'grudzien'],
		'zh' => ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'],
		'hu' => ["január", "február", "március", "április", "május", "június", "július", "augusztus", "szeptember", "október", "november", "december"],
		'sv' => ['januari', 'februari', 'mars', 'april', 'maj', 'juni', 'juli', 'augusti', 'september', 'oktober', 'november', 'december'],
        'cs' => ['ledna', 'únor', 'březen', 'dubna', 'květen', 'června', 'července', 'vznešený', 'září', 'říjen', 'listopadu', 'prosince'],
		'no' => ['jan','febr','mars','april','mai','juni','juli','aug','sept','okt','nov','des'],
		'no2' => ['januar','februar','mars','april','mai','juni','juli','august','september','oktober','november','desember'],
		"ca" => ["gener","febrer","març","abril","maig","juny","juliol","agost","setembre","octubre","novembre","desembre"],
		"el" => ["nottrans","nottrans","nottrans","απρ","μαϊ","ιουνιουν","ιουλ","αυγ","nottrans","οκτ","nottrans","nottrans"], // need to correct ryan
		'th' => ['______', '______', '______', '______', '______', '______', '______', '______', 'ก.ย.', '______', '______', '______'],
		'ro' => ['ian', 'feb', 'mar', 'apr', 'mai', 'iun', 'iul', 'aug', 'sep', 'oct', 'noi', 'dec'],
	);

	var $dateTimeToolsMonths = [
		"en" => [
			"january" => 0,
			"february" => 1,
			"march" => 2,
			"april" => 3,
			"may" => 4,
			"june" => 5,
			"july" => 6,
			"august" => 7,
			"september" => 8,
			"october" => 9,
			"november" => 10,
			"december" => 11,
		],
		"ru" => [
			"январь" => 0, "янв" => 0, "января" => 0,
			"февраля" => 1, "фев" => 1, "февраль" => 1,
			"марта" => 2, "мар" => 2, "март" => 2,
			"апреля" => 3, "апр" => 3, "апрель" => 3,
			"мая" => 4, "май" => 4,
			"июн" => 5, "июня" => 5, "июнь" => 5,
			"июля" => 6, "июль" => 6, "июл" => 6,
			"августа" => 7, "авг" => 7, "август" => 7,
			"сен" => 8, "сентябрь" => 8, "сентября" => 8,
			"окт" => 9, "октября" => 9, "октябрь" => 9,
			"ноя" => 10, "ноября" => 10, "ноябрь" => 10,
			"дек" => 11, "декабрь" => 11, "декабря" => 11,
		],
		"de" => [
			"januar" => 0, "jan" => 0,
			"februar" => 1, "feb" => 1,
			"mae" => 2, "maerz" => 2, "märz" => 2, "mrz" => 2,
			"apr" => 3, "april" => 3,
			"mai" => 4,
			"juni" => 5, "jun" => 5,
			"jul" => 6, "juli" => 6,
			"august" => 7, "aug" => 7,
			"september" => 8, "sep" => 8,
			"oktober" => 9, "okt" => 9,
			"nov" => 10, "november" => 10,
			"dez" => 11, "dezember" => 11,
		],
		"nl" => [
			"januari" => 0,
			"februari" => 1,
			"mrt" => 2, "maart" => 2,
			"april" => 3,
			"mei" => 4,
			"juni" => 5,
			"juli" => 6,
			"augustus" => 7,
			"september" => 8,
			"oktober" => 9,
			"november" => 10,
			"december" => 11,
		],
		"no" => [
			"januar" => 0, "jan" => 0,
			"febr" => 1, "februar" => 1,
			"mars" => 2,
			"april" => 3,
			"mai" => 4, "kan" => 4,
			"juni" => 5,
			"juli" => 6,
			"august" => 7, "aug" => 7,
			"september" => 8, "sept" => 8,
			"okt" => 9, "oktober" => 9,
			"nov" => 10, "november" => 10,
			"des" => 11, "desember" => 11,
		],
		"fr" => [
			"janv" => 0, "janvier" => 0,
			"févr" => 1, "fevrier" => 1, "février" => 1,
			"mars" => 2,
			"avril" => 3, "avr" => 3,
			"mai" => 4,
			"juin" => 5,
			"juillet" => 6, "juil" => 6,
			"août" => 7, "aout" => 7,
			"sept" => 8, "septembre" => 8,
			"oct" => 9, "octobre" => 9,
			"novembre" => 10, "nov" => 10,
			"decembre" => 11, "décembre" => 11, "déc" => 11,
		],
		"es" => [
			"enero" => 0,
			"feb" => 1, "febrero" => 1,
			"marzo" => 2,
			"abr" => 3, "abril" => 3,
			"mayo" => 4,
			"jun" => 5, "junio" => 5,
			"julio" => 6, "jul" => 6,
			"agosto" => 7,
			"sept" => 8, "septiembre" => 8,
			"oct" => 9, "octubre" => 9,
			"nov" => 10, "noviembre" => 10,
			"dic" => 11, "diciembre" => 11,
		],
		"pt" => [
			"jan" => 0, "janeiro" => 0,
			"fev" => 1, "fevereiro" => 1,
			"março" => 2, "mar" => 2,
			"abr" => 3, "abril" => 3,
			"maio" => 4, "mai" => 4,
			"jun" => 5, "junho" => 5,
			"julho" => 6, "jul" => 6,
			"ago" => 7, "agosto" => 7,
			"setembro" => 8, "set" => 8,
			"out" => 9, "outubro" => 9,
			"novembro" => 10, "non" => 10,
			"dez" => 11, "dezembro" => 11,
		],
		"it" => [
			"gen" => 0, "gennaio" => 0,
			"feb" => 1, "febbraio" => 1,
			"marzo" => 2, "mar" => 2,
			"apr" => 3, "aprile" => 3,
			"maggio" => 4, "mag" => 4,
			"giu" => 5, "giugno" => 5,
			"luglio" => 6, "lug" => 6,
			"ago" => 7, "agosto" => 7,
			"settembre" => 8, "set" => 8,
			"ott" => 9, "ottobre" => 9,
			"novembre" => 10, "nov" => 10,
			"dic" => 11, "dicembre" => 11,
		],
		"fi" => [
			"tammikuuta" => 0,
			"helmikuuta" => 1,
			"maaliskuuta" => 2,
			"huhtikuuta" => 3,
			"toukokuuta" => 4,
			"kesäkuuta" => 5,
			"heinäkuuta" => 6,
			"elokuuta" => 7,
			"syyskuuta" => 8,
			"lokakuuta" => 9,
			"marraskuuta" => 10,
			"joulukuuta" => 11,
		],
		"da" => [
			"januar" => 0,
			"februar" => 1,
			"marts" => 2,
			"april" => 3,
			"maj" => 4,
			"juni" => 5,
			"juli" => 6,
			"august" => 7,
			"september" => 8,
			"oktober" => 9,
			"november" => 10,
			"december" => 11,
		],
		"tr" => [
			"ocak" => 0,
			"şubat" => 1,
			"mart" => 2,
			"nisan" => 3,
			"mayıs" => 4,
			"haziran" => 5,
			"temmuz" => 6,
			"ağustos" => 7,
			"eylül" => 8,
			"ekim" => 9,
			"kasım" => 10,
			"aralık" => 11,
		],
		"pl" => [
			"styczeń" => 0, "styczen" => 0,
			"luty" => 1,
			"marzec" => 2,
			"kwiecień" => 3, "kwiecien" => 3,
			"maj" => 4,
			"czerwiec" => 5,
			"lipiec" => 6, "lipca" => 6,
			"sierpien" => 7, "sierpień" => 7,
			"wrzesien" => 8, "wrzesień" => 8,
			"pazdziernik" => 9, "październik" => 9, "października" => 9,
			"listopad" => 10,
			"grudzien" => 11, "grudzień" => 11,
		],
		"zh" => [
			"一月" => 0,
			"二月" => 1,
			"三月" => 2,
			"四月" => 3,
			"五月" => 4,
			"六月" => 5,
			"七月" => 6,
			"八月" => 7,
			"九月" => 8,
			"十月" => 9,
			"十一月" => 10,
			"十二月" => 11,
		],
		"hu" => [
			"január" => 0,
			"február" => 1,
			"március" => 2,
			"április" => 3,
			"május" => 4,
			"június" => 5,
			"július" => 6,
			"augusztus" => 7,
			"szeptember" => 8,
			"október" => 9,
			"november" => 10,
			"december" => 11,
		],
		"sv" => [
			"januari" => 0,
			"februari" => 1,
			"mars" => 2,
			"april" => 3,
			"maj" => 4,
			"juni" => 5,
			"juli" => 6,
			"augusti" => 7,
			"september" => 8,
			"oktober" => 9,
			"november" => 10,
			"december" => 11,
		],
		"cs" => [
			"ledna" => 0,
			"únor" => 1,
			"březen" => 2,
			"dubna" => 3,
			"květen" => 4,
			"června" => 5,
			"července" => 6,
			"vznešený" => 7,
			"září" => 8,
			"říjen" => 9,
			"listopadu" => 10,
			"prosince" => 11,
		],
		"th" => [
			"ก.ย." => 8,
		],
		"ro" => [
			"ian" => 0,
			"feb" => 1,
			"mar" => 2,
			"apr" => 3,
			"mai" => 4,
			"iun" => 5,
			"iul" => 6,
			"aug" => 7,
			"sep" => 8,
			"oct" => 9,
			"noi" => 10,
			"dec" => 11,
		],
		"ca" => [
			"gener" => 0,
			"febrer" => 1,
			"març" => 2,
			"abril" => 3,
			"maig" => 4,
			"juny" => 5,
			"juliol" => 6,
			"agost" => 7,
			"setembre" => 8,
			"octubre" => 9,
			"novembre" => 10,
			"desembre" => 11,
		],
		"el" => [
			"ιαν" => 0,
			"φεβ" => 1,
			"μαρ" => 2,
			"απρ" => 3,
			"μαϊ" => 4,
			"ιουνιουν" => 5,
			"ιουλ" => 6,
			"αυγ" => 7,
			"σεπ" => 8,
			"οκτ" => 9,
			"νοε" => 10,
			"δεκ" => 11
		],
	];

	var $dateTimeToolsMonthsOutMonths = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
	
	function translateMonth($month, $lang){
		$month = mb_strtolower(trim($month), 'UTF-8');
		if(isset($this->dateTimeToolsMonths[$lang]) && isset($this->dateTimeToolsMonths[$lang][$month]))
			return $this->dateTimeToolsMonthsOutMonths[$this->dateTimeToolsMonths[$lang][$month]];
		return false;
	}

	function monthNameToEnglish($monthNameOriginal, $languageTip = false) {
		$result = false;
		$list = $languageTip ?(is_array($languageTip) ? $languageTip : array($languageTip)) : array_keys($this->dateTimeToolsMonthNames);
		$monthNameOriginal = mb_strtolower($monthNameOriginal, 'UTF-8');
		foreach($list as $ln){
			if (isset($this->dateTimeToolsMonthNames[$ln])){
				$possible = preg_split("#[\s,\-/]+#", $monthNameOriginal);
				
				foreach($possible as $item){
					if (!trim($item,",\n\t ")) continue;

					$i = 0;
					foreach($this->dateTimeToolsMonthNames[$ln] as $mn){
						if (preg_match("#^$item#i", $mn)){
							$result = $this->dateTimeToolsMonthNames['en'][$i];
							break 2;
						}
						$i++;
					}
				}
			}
		}
		return $result;
	}

	function dateStringToEnglish($dateString, $languageTip = false, $returnSourceStringOnFailure = false) {
		if (preg_match('#[[:alpha:]]+#iu', $dateString, $m))
			$monthNameOriginal = $m[0];
		else
			return $returnSourceStringOnFailure ? $dateString : false;
		if ($translatedMonthName = $this->monthNameToEnglish($dateString, $languageTip)) {
			return preg_replace("#$monthNameOriginal#i", $translatedMonthName, $dateString);
		} else {
			return $returnSourceStringOnFailure ? $dateString : false;
		}
	}

}

//// TODO: Modify to be unit test and extract to separate file
//class DateTimeTraitTester {
//
//	use DateTimeTools;
//
//	var $breakOnFailure = false;
//
//	public function testMonthNameToEnglish() {
//		$languageIndex = 0;
//		foreach ($this->monthNames as $language => $languageSpecificMonthNames) {
//			echo "Testing language \"$language\"\n";
//			$monthNameIndex = 0;
//			foreach ($languageSpecificMonthNames as $monthName) {
//				echo "    Testing month name \"$monthName\", got ";
//				$result = $this->monthNameToEnglish($monthName);
//				echo var_export($result, true);
//				echo ': ';
//				if (strtolower($result) === strtolower($this->monthNames['en'][$monthNameIndex])) {
//					echo "OK\n";
//				} else {
//					echo "FAILED\n";
//					if ($this->breakOnFailure)
//						break 2;
//				}
//				$monthNameIndex++;
//			}
//			$languageIndex++;
//		}
//	}
//
//	public function testDateStringToEnglish() {
//		$languageIndex = 0;
//		foreach ($this->monthNames as $language => $languageSpecificMonthNames) {
//			echo "Testing language \"$language\"\n";
//			$monthNameIndex = 0;
//			foreach ($languageSpecificMonthNames as $monthName) {
//				$day = rand(1, 28);
//				$year = rand(1970, 1990);
//				$dateOrig = $day.' '.$monthName.' '.$year;
//				$dateExpected = $day.' '.$this->monthNames['en'][$monthNameIndex].' '.$year;
//				echo "    Testing date \"$dateOrig\", got ";
//				$result = $this->dateStringToEnglish($dateOrig);
//				echo var_export($result, true);
//				echo ': ';
//				if (strtolower($result) === strtolower($dateExpected)) {
//					echo "OK\n";
//				} else {
//					echo "FAILED\n";
//					if ($this->breakOnFailure)
//						break 2;
//				}
//				$monthNameIndex++;
//			}
//			$languageIndex++;
//		}
//	}
//
//}
//
//$t = new DateTimeTraitTester();
//$t->breakOnFailure = true;
//$t->testMonthNameToEnglish();
//$t->testDateStringToEnglish();
