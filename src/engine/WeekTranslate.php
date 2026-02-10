<?php

namespace AwardWallet\Engine;

class WeekTranslate
{
    protected static $WeekDayNames = [
        'en' => [
            'monday'    => 0, 'mon' => 0, 'mo' => 0,
            'tuesday'   => 1, 'tue' => 1, 'tu' => 1,
            'wednesday' => 2, 'wed' => 2, 'we' => 2,
            'thursday'  => 3, 'thu' => 3, 'th' => 3, 'thur' => 3,
            'friday'    => 4, 'fri' => 4, 'fr' => 4,
            'saturday'  => 5, 'sat' => 5, 'sa' => 5,
            'sunday'    => 6, 'sun' => 6, 'su' => 6,
        ],
        'es' => [
            'lunes'     => 0, 'lun'  => 0,
            'martes'    => 1, 'mar'  => 1,
            'miércoles' => 2, 'miér' => 2, 'mie' => 2, 'mié' => 2,
            'jueves'    => 3, 'jue'  => 3,
            'viernes'   => 4, 'vie'  => 4,
            'sábado'    => 5, 'sáb'  => 5, 'sab'  => 5,
            'domingo'   => 6, 'dom'  => 6,
        ],
        'de' => [
            'montag'     => 0, 'mo' => 0,
            'dienstag'   => 1, 'di' => 1,
            'mittwoch'   => 2, 'mi' => 2,
            'donnerstag' => 3, 'do' => 3,
            'freitag'    => 4, 'fr' => 4,
            'samstag'    => 5, 'sa' => 5,
            'sonntag'    => 6, 'so' => 6,
        ],
        "it" => [
            'lunedì'    => 0, 'lun' => 0,
            'martedì'   => 1, 'mar' => 1,
            'mercoledì' => 2, 'mer' => 2,
            'giovedì'   => 3, 'gio' => 3,
            'venerdì'   => 4, 'ven' => 4,
            'sabato'    => 5, 'sab' => 5,
            'domenica'  => 6, 'dom' => 6,
        ],
        "nl" => [
            'maandag'   => 0, 'maa' => 0, 'ma' => 0,
            'dinsdag'   => 1, 'din' => 1, 'di' => 1,
            'woensdag'  => 2, 'woe' => 2, 'wo' => 2,
            'donderdag' => 3, 'don' => 3, 'do' => 3,
            'vrijdag'   => 4, 'vri' => 4, 'vr' => 4, 'vrij' => 4,
            'zaterdag'  => 5, 'zat' => 5, 'za' => 5,
            'zondag'    => 6, 'zon' => 6, 'zo' => 6,
        ],
        "fr" => [
            'lundi'		  => 0, 'lun' => 0,
            'mardi'		  => 1, 'mar' => 1,
            'mercredi'	=> 2, 'mer' => 2,
            'jeudi'		  => 3, 'jeu' => 3,
            'vendredi'	=> 4, 'ven' => 4,
            'samedi'	  => 5, 'sam' => 5,
            'dimanche'	=> 6, 'dim' => 6,
        ],
        "pt" => [
            'segunda-feira'	=> 0, 'seg' => 0,
            'terça-feira'	  => 1, 'ter' => 1,
            'quarta-feira'	 => 2, 'qua' => 2,
            'quinta-feira'	 => 3, 'qui' => 3,
            'sexta-feira'	  => 4, 'sex' => 4,
            'sábado'		      => 5, 'sáb' => 5, 'sab' => 5,
            'domingo'		     => 6, 'dom' => 6,
        ],
        "ru" => [
            'понедельник'	=> 0, 'пн' => 0,
            'вторник'		   => 1, 'вт' => 1,
            'среда'			    => 2, 'ср' => 2,
            'четверг'		   => 3, 'чт' => 3,
            'пятница'		   => 4, 'пт' => 4,
            'суббота'		   => 5, 'сб' => 5,
            'воскресенье'	=> 6, 'вс' => 6,
        ],
        "sv" => [
            'måndag'  => 0, 'må' => 0, 'mån' => 0,
            'tisdag'  => 1, 'ti' => 1, 'tis' => 1,
            'onsdag'  => 2, 'on' => 2, 'ons' => 2,
            'torsdag' => 3, 'to' => 3, 'tor' => 3, 'tors' => 3,
            'fredag'  => 4, 'fr' => 4, 'fre' => 4,
            'lördag'  => 5, 'lö' => 5, 'lör' => 5,
            'söndag'  => 6, 'sö' => 6, 'sön' => 6,
        ],
        'el' => [
            'δευτέρα'       => 0, 'δευ' => 0,
            'τριτη'         => 1, 'τρι' => 1, 'τρίτη' => 1,
            'τετάρτη'       => 2, 'τετ' => 2,
            'πέμπτη'        => 3, 'πεμ' => 3,
            'παρασκευή'     => 4, 'παρ' => 4,
            'σάββατο'       => 5, 'σαβ' => 5,
            'κυριακή'       => 6, 'κυρ' => 6,
        ],
        'da' => [
            'mandag'        => 0, 'ma' => 0, 'man' => 0,
            'tirsdag'       => 1, 'ti' => 1, 'tir' => 1,
            'onsdag'        => 2, 'on' => 2, 'ons' => 2,
            'torsdag'       => 3, 'to' => 3, 'tor' => 3,
            'fredag'        => 4, 'fr' => 4, 'fre' => 4,
            'lørdag'        => 5, 'lø' => 5, 'lør' => 5,
            'søndag'        => 6, 'sø' => 6, 'søn' => 6,
        ],
        'pl' => [
            'poniedziałek'  => 0, 'pn' => 0, 'pon' => 0,
            'wtorek'        => 1, 'wt' => 1, 'wto' => 1,
            'środa'         => 2, 'śr' => 2, 'śro' => 2,
            'czwartek'      => 3, 'cz' => 3, 'czw' => 3,
            'piątek'        => 4, 'pt' => 4, 'pią' => 4,
            'sobota'        => 5, 'so' => 5, 'sob' => 5,
            'niedziela'     => 6, 'n'  => 6, 'nie' => 6,
        ],
        'ca' => [
            'dilluns'       => 0, 'dl' => 0,
            'dimarts'       => 1, 'dt' => 1,
            'dimecres'      => 2, 'dc' => 2,
            'dijous'        => 3, 'dj' => 3,
            'divendres'     => 4, 'dv' => 4,
            'dissabte'      => 5, 'ds' => 5,
            'diumenge'      => 6, 'dg' => 6,
        ],
        'zh' => [
            '星期一' => 0, '周一' => '0', '一' => 0,
            '星期二' => 1, '周二' => 1, '二' => 1,
            '星期三' => 2, '周三' => 2, '三' => 2,
            '星期四' => 3, '周四' => 3, '四' => 3,
            '星期五' => 4, '周五' => 4, '五' => 4,
            '星期六' => 5, '周六' => 5, '六' => 5,
            '星期天' => 6, '星期日' => 6, '週日'=> '6', '周日' => 6,
        ],
        'no' => [
            'mandag'	 => 0, 'man' => 0, 'ma' => 0,
            'tirsdag'	=> 1, 'tir' => 1, 'ti' => 1,
            'onsdag'	 => 2, 'ons' => 2, 'on' => 2,
            'torsdag'	=> 3, 'tor' => 3, 'to' => 3,
            'fredag'	 => 4, 'fre' => 4, 'fr' => 4,
            'lørdag'	 => 5, 'lør' => 5, 'lø' => 5,
            'søndag'	 => 6, 'søn' => 6, 'sø' => 6,
        ],
        'ja' => [
            '月' => 0,
            '火' => 1,
            '水' => 2,
            '木' => 3,
            '金' => 4,
            '土' => 5,
            '日' => 6,
        ],
        'cs' => [
            'pondělí'	=> 0, 'po' => 0,
            'úterý'		 => 1, 'út' => 1,
            'středa'	 => 2, 'st' => 2,
            'čtvrtek'	=> 3, 'čt' => 3,
            'pátek'		 => 4, 'pá' => 4,
            'sobota'	 => 5, 'so' => 5,
            'neděle'	 => 6, 'ne' => 6,
        ],
        'hu' => [
            'hétfő'	    => 0, 'hét'	=> 0, 'h' => 0,
            'kedd'		    => 1, 'ked'	=> 1, 'k' => 1,
            'szerda'	   => 2, 'sze' => 2,
            'csütörtök'	=> 3, 'csü'	=> 3, 'cs' => 3,
            'péntek'	   => 4, 'pén'	=> 4, 'p' => 4,
            'szombat'	  => 5, 'szo' => 5,
            'vasárnap'	 => 6, 'vas'	=> 6, 'v' => 6,
        ],
        'fi' => [
            'maanantai'   => 0, 'ma' => 0,
            'tiistai'     => 1, 'ti' => 1,
            'keskiviikko' => 2, 'ke' => 2,
            'torstai'     => 3, 'to' => 3,
            'perjantai'   => 4, 'pe' => 4,
            'lauantai'    => 5, 'la' => 5,
            'sunnuntai'   => 6, 'su' => 6,
        ],
        'tr' => [
            'pazartesi' => 0, 'pzt' => 0,
            'salı'      => 1, 'sal' => 1,
            'çarşamba'  => 2, 'çar' => 2,
            'perşembe'  => 3, 'per' => 3,
            'cuma'      => 4, 'cum' => 4,
            'cumartesi' => 5, 'cmt' => 5,
            'pazar'     => 6, 'paz' => 6,
        ],
        'th' => [
            'จ.'  => 0,
            'อ.'  => 1,
            'พ.'  => 2,
            'พฤ.' => 3,
            'ศ.'  => 4,
            'ส.'  => 5,
            'อา.' => 6,
        ],
        'id' => [
            'senin'     => 0, 'sen' => 0,
            'selasa'    => 1, 'sel' => 1,
            'rabu'      => 2, 'rab' => 2,
            'kamis'     => 3, 'kam' => 3,
            'jumat'     => 4, 'jum' => 4,
            'sabtu'     => 5, 'sab' => 5,
            'minggu'    => 6, 'min' => 6,
        ],
        'ar' => [
            'الاثنين'   => 0, 'ن' => 0,
            'الثلاثاء'  => 1, 'ث' => 1,
            'الأربعاء'  => 2, 'ر' => 2,
            'الخميس'    => 3, 'خ' => 3,
            'الجمعة'    => 4, 'ج' => 4,
            'السبت'     => 5, 'س' => 5,
            'الأحد'     => 6, 'ح' => 6,
        ],
        'ko' => [
            '월요일'      => 0, '월' => 0,
            '화요일'      => 1, '화' => 1,
            '수요일'      => 2, '수' => 2,
            '목요일'      => 3, '목' => 3,
            '금요일'      => 4, '금' => 4,
            '토요일'      => 5, '토' => 5,
            '일요일'      => 6, '일' => 6,
        ],
        "br" => [
            'Lun'       => 0,
            'Meurzh'    => 1, 'Meu' => 1,
            'Mercʼher'  => 2, 'Mer' => 2,
            'Yaou'      => 3,
            'Gwener'    => 4, 'Gwe'    => 4,
            'Sadorn'    => 5, 'Sad' => 5,
            'Sul'       => 6,
        ],
        "ms" => [
            'Isnin'     => 0, 'Isn' => 0,
            'Selasa'    => 1, 'Sel' => 1,
            'Rabu'      => 2, 'Rab' => 2,
            'Khamis'    => 3, 'Kha' => 3,
            'Jumaat'    => 4, 'Jum' => 4,
            'Sabtu'     => 5, 'Sab' => 5,
            'Ahad'      => 6, 'Ahd' => 6,
        ],
        "sk" => [
            'pondelok'     => 0, 'pon' => 0,
            'utorok'       => 1, 'uto' => 1,
            'streda'       => 2, 'str' => 2,
            'štvrtok'      => 3, 'štv' => 3,
            'piatok'       => 4, 'pia' => 4,
            'sobota'       => 5, 'sob' => 5,
            'nedeľa'       => 6, 'Ned' => 6,
        ],
        "he" => [
            'יום א’'       => 0, '׳ יום ב׳' => 0, 'יום ב׳' => 0,
            "יום ג׳"       => 1,
            'יום ג’'       => 2,
            'יום ד’'       => 3, 'יום ה׳' => 3,
            'יום ה’'       => 4,
            'יום ו’'       => 5,
            'יום ש’'       => 6,
        ],
        "ro" => [
            'luni'      => 0, 'lun' => 0,
            'marți'     => 1, 'mar' => 1,
            'miercuri'  => 2, 'mie' => 2,
            'joi'       => 3, 'joi' => 3,
            'vineri'    => 4, 'vin' => 4,
            'sâmbătă'   => 5, 'sâm' => 5,
            'duminică'  => 6, 'dum' => 6,
        ],
        "to" => [
            'mōnite'       => 0, 'mōn' => 0,
            'tūsite'       => 1, 'tūs' => 1,
            'pulelulu'     => 2, 'pul' => 2,
            'tuʻapulelulu' => 3, 'tuʻa' => 3,
            'falaite'      => 4, 'fal' => 4,
            'tokonaki'     => 5, 'tok' => 5,
            'sāpate'       => 6, 'sāp' => 6,
        ],
    ];

    protected static $OutWeekDayNames = [
        ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
        ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    ];

    public static function translate($day, $lang = null, $short = false)
    {
        $number = self::number0($day, $lang);

        if ($number !== null) {
            return $short ? self::$OutWeekDayNames[1][$number] : self::$OutWeekDayNames[0][$number];
        }

        return null;
    }

    public static function number0($day, $lang = null)
    {
        $day = mb_strtolower(trim($day), 'UTF-8');

        if (!empty($lang)) {
            if (isset(self::$WeekDayNames[$lang])) {
                $haystack = [self::$WeekDayNames[$lang]];
            } else {
                throw new \Exception(sprintf('invalid language `%s` in %s', $lang, __CLASS__));
            }
        } else {
            $haystack = self::$WeekDayNames;
        }

        foreach ($haystack as $item) {
            if (array_key_exists($day, $item)) {
                return $item[$day];
            }
        }

        return null;
    }

    public static function number1($day, $lang = null)
    {
        $number = self::number0($day, $lang);

        if ($number !== null) {
            $number++;
        }

        return $number;
    }
}
