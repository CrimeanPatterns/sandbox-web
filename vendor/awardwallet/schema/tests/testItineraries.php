<?php
if (file_exists($file  = "vendor/autoload.php"))
    /** @var \Composer\Autoload\ClassLoader $loader */
    $loader = require_once $file;
else {
    echo "Run this test from project root directory\n";
    exit(1);
}

$console = new \Symfony\Component\Console\Logger\ConsoleLogger(new \Symfony\Component\Console\Output\ConsoleOutput(256));

$tests = [
    ['EMAIL', null, null],
    // empty email
    ['empty', '{"itineraries":[]}', 'empty data'],
    // empty junk
    ['junk', '{"isJunk": true}', null],
    // not empty junk
    ['junkWithData', '{"itineraries":[{"segments":[{"depName":"depname","depDate":1893456000,"arrName":"arrname","arrDate":1893542400}],"confirmationNumbers":[["ABCD",null]],"type":"bus"}],"isJunk":true}', 'email with info'],
    // email dup conf no
    ['dupOtaConfNo', '{"travelAgency":{"confirmationNumbers":[["UUU",null],["UUU",null]]},"itineraries":[{"noConfirmationNumber":true,"segments":[{"depName":"depname","depDate":1893456000,"arrName":"arrname","arrDate":1893542400}],"type":"bus"}]}', 'duplicate elements in `confirmationNumbers`'],
    //
    ['BUS', null, null],
    //bus, OK
    ['valid', '{"itineraries":[{"segments":[{"depName":"depname","depDate":1893456000,"arrName":"arrname","arrDate":1893542400}],"confirmationNumbers":[["ABCD",null]],"type":"bus"}]}', null],
    // bus, empty segment
    ['emptySegment', '{"itineraries":[{"segments":[{"duration": "1h"}],"confirmationNumbers":[["ABCD",null]],"type":"bus"}]}', 'empty segment'],
    // bus, no segments
    ['noSegments', '{"itineraries":[{"confirmationNumbers":[["ABCD",null]],"type":"bus"}]}', 'missing segments'],
    // bus, cancelled ok
    ['cancelled', '{"itineraries":[{"confirmationNumbers":[["ABCD",null]],"cancelled":true,"type":"bus"}]}', null],
    // bus, cancelled empty
    ['cancelledEmpty', '{"itineraries":[{"confirmationNumbers":[],"cancelled":true,"type":"bus"}]}', 'missing confirmation number'],
    // bus, cancelled + ota
    ['cancelledOta', '{"itineraries":[{"cancelled":true,"travelAgency":{"confirmationNumbers":[["ABCD",null]]},"type":"bus"}]}', null],
    // bus, cancelled + upper ota
    ['cancelledUpperOta', '{"travelAgency":{"confirmationNumbers":[["ABCD",null]]},"itineraries":[{"cancelled":true,"type":"bus"}]}', null],
    // bus, cancelled empty segment
    ['emptySegmentCancelled', '{"itineraries":[{"segments":[{}],"confirmationNumbers":[["ABCD",null]],"cancelled":true,"type":"bus"}]}', 'empty segment'],
    // bus, ota conf no
    ['otaConfNo', '{"itineraries":[{"noConfirmationNumber":true,"segments":[{"depName":"depname","depDate":1893456000,"arrName":"arrname","arrDate":1893542400}],"type":"bus","travelAgency":{"confirmationNumbers":[["ABCD",null]]}}]}', null],
    // bus, upper conf no
    ['upperConfNo', '{"travelAgency":{"confirmationNumbers":[["ABCD",null]]},"itineraries":[{"noConfirmationNumber":true,"segments":[{"depName":"depname","depDate":1893456000,"arrName":"arrname","arrDate":1893542400}],"type":"bus"}]}', null],
    // bus, no conf no
    ['noConfNo', '{"itineraries":[{"segments":[{"depName":"depname","depDate":1893456000,"arrName":"arrname","arrDate":1893542400}],"type":"bus"}]}', 'missing confirmation number'],
    // bus, dup conf no
    ['dupConfNo', '{"itineraries":[{"segments":[{"depName":"depname","depDate":1893456000,"arrName":"arrname","arrDate":1893542400}],"confirmationNumbers":[["ABCD",null],["ABCD",null]],"type":"bus"}]}', 'duplicate elements in `confirmationNumbers`'],
    // bus, dup ota conf no
    ['dupOtaConfNo', '{"itineraries":[{"noConfirmationNumber":true,"segments":[{"depName":"depname","depDate":1893456000,"arrName":"arrname","arrDate":1893542400}],"type":"bus","travelAgency":{"confirmationNumbers":[["ABCD",null],["ABCD",null]]}}]}', 'duplicate elements in `confirmationNumbers`'],
    // bus, identical locations
    ['identicalLocations', '{"itineraries":[{"segments":[{"depName":"depname","depDate":1893456000,"arrName":"depname","arrDate":1893542400}],"confirmationNumbers":[["ABCD",null]],"type":"bus"}]}', 'locations are identical'],
    // bus, identical codes
    ['identicalCodes', '{"itineraries":[{"segments":[{"depCode":"AAA","depDate":1893456000,"arrCode":"AAA","arrDate":1893542400}],"confirmationNumbers":[["ABCD",null]],"type":"bus"}]}', 'codes are identical'],
    // bus, identical dates
    ['identicalDates', '{"itineraries":[{"segments":[{"depName":"depname","depDate":1893456000,"arrName":"arr","arrDate":1893456000}],"confirmationNumbers":[["ABCD",null]],"type":"bus"}]}', 'dates are identical'],
    // bus, invalid dates
    ['invalidDates', '{"itineraries":[{"segments":[{"depName":"depname","depDate":1893542400,"arrName":"arr","arrDate":1893456000}],"confirmationNumbers":[["ABCD",null]],"type":"bus"}]}', 'invalid dates'],
    // bus, dates far apart
    ['datesFarApart', '{"itineraries":[{"segments":[{"depName":"depname","depDate":1893542400,"arrName":"arr","arrDate":1894233600}],"confirmationNumbers":[["ABCD",null]],"type":"bus"}]}', 'dates are too far apart'],
    // bus, missing arrival location
    ['noArrName', '{"itineraries":[{"segments":[{"depName":"depname","depDate":1893542400,"arrDate":1893628800}],"confirmationNumbers":[["ABCD",null]],"type":"bus"}]}', 'missing arrival location'],
    // bus, missing departure location
    ['noDepName', '{"itineraries":[{"segments":[{"arrName":"arrname","depDate":1893542400,"arrDate":1893628800}],"confirmationNumbers":[["ABCD",null]],"type":"bus"}]}', 'missing departure location'],
    // bus, missing arr date
    ['noDepDate', '{"itineraries":[{"segments":[{"depName":"depname","arrName":"arr","depDate":1893628800}],"confirmationNumbers":[["ABCD",null]],"type":"bus"}]}', 'missing arrDate'],
    // bus, missing dep date
    ['noDepDate', '{"itineraries":[{"segments":[{"depName":"depname","arrName":"arr","arrDate":1893628800}],"confirmationNumbers":[["ABCD",null]],"type":"bus"}]}', 'missing depDate'],
    //
    ['TRAIN', null, null],
    // train, OK
    ['valid', '{"itineraries":[{"segments":[{"number":"A123","depName":"depname","depDate":1893456000,"arrName":"arrname","arrDate":1893542400}],"confirmationNumbers":[["ABCD",null]],"type":"train"}]}', null],
    // train, empty segment
    ['emptySegment', '{"itineraries":[{"segments":[{"duration": "1h"}],"confirmationNumbers":[["ABCD",null]],"type":"train"}]}', 'empty segment'],
    // train, no segments
    ['noSegments', '{"itineraries":[{"confirmationNumbers":[["ABCD",null]],"type":"train"}]}', 'missing segments'],
    // train, cancelled ok
    ['cancelled', '{"itineraries":[{"confirmationNumbers":[["ABCD",null]],"cancelled":true,"type":"train"}]}', null],
    // train, cancelled empty
    ['cancelledEmpty', '{"itineraries":[{"confirmationNumbers":[],"cancelled":true,"type":"train"}]}', 'missing confirmation number'],
    // train, cancelled + ota
    ['cancelledOta', '{"itineraries":[{"cancelled":true,"travelAgency":{"confirmationNumbers":[["ABCD",null]]},"type":"train"}]}', null],
    // train, cancelled + upper ota
    ['cancelledUpperOta', '{"travelAgency":{"confirmationNumbers":[["ABCD",null]]},"itineraries":[{"cancelled":true,"type":"train"}]}', null],
    // train, cancelled empty segment
    ['emptySegmentCancelled', '{"itineraries":[{"segments":[{}],"confirmationNumbers":[["ABCD",null]],"cancelled":true,"type":"train"}]}', 'empty segment'],
    // train, ota conf no
    ['otaConfNo', '{"itineraries":[{"noConfirmationNumber":true,"segments":[{"number":"A123","depName":"depname","depDate":1893456000,"arrName":"arrname","arrDate":1893542400}],"type":"train","travelAgency":{"confirmationNumbers":[["ABCD",null]]}}]}', null],
    // train, upper conf no
    ['upperConfNo', '{"travelAgency":{"confirmationNumbers":[["ABCD",null]]},"itineraries":[{"noConfirmationNumber":true,"segments":[{"number":"A123","depName":"depname","depDate":1893456000,"arrName":"arrname","arrDate":1893542400}],"type":"train"}]}', null],
    // train, no conf no
    ['noConfNo', '{"itineraries":[{"segments":[{"number":"A123","depName":"depname","depDate":1893456000,"arrName":"arrname","arrDate":1893542400}],"type":"train"}]}', 'missing confirmation number'],
    // train, dup conf no
    ['dupConfNo', '{"itineraries":[{"segments":[{"number":"A123","depName":"depname","depDate":1893456000,"arrName":"arrname","arrDate":1893542400}],"confirmationNumbers":[["ABCD",null],["ABCD",null]],"type":"train"}]}', 'duplicate elements in `confirmationNumbers`'],
    // train, dup ota conf no
    ['dupOtaConfNo', '{"itineraries":[{"noConfirmationNumber":true,"segments":[{"number":"A123","depName":"depname","depDate":1893456000,"arrName":"arrname","arrDate":1893542400}],"type":"train","travelAgency":{"confirmationNumbers":[["ABCD",null],["ABCD",null]]}}]}', 'duplicate elements in `confirmationNumbers`'],
    // train, identical locations
    ['identicalLocations', '{"itineraries":[{"segments":[{"number":"A123","depName":"depname","depDate":1893456000,"arrName":"depname","arrDate":1893542400}],"confirmationNumbers":[["ABCD",null]],"type":"train"}]}', 'locations are identical'],
    // train, identical codes
    ['identicalCodes', '{"itineraries":[{"segments":[{"number":"A123","depCode":"AAA","depDate":1893456000,"arrCode":"AAA","arrDate":1893542400}],"confirmationNumbers":[["ABCD",null]],"type":"train"}]}', 'codes are identical'],
    // train, identical dates
    ['identicalDates', '{"itineraries":[{"segments":[{"number":"A123","depName":"depname","depDate":1893456000,"arrName":"arr","arrDate":1893456000}],"confirmationNumbers":[["ABCD",null]],"type":"train"}]}', 'dates are identical'],
    // train, dates far apart
    ['datesFarApart', '{"itineraries":[{"segments":[{"number":"A123","depName":"depname","depDate":1893542400,"arrName":"arr","arrDate":1894406460}],"confirmationNumbers":[["ABCD",null]],"type":"train"}]}', 'dates are too far apart'],
    // train, missing arrival location
    ['noArrName', '{"itineraries":[{"segments":[{"number":"A123","depName":"depname","depDate":1893542400,"arrDate":1893628800}],"confirmationNumbers":[["ABCD",null]],"type":"train"}]}', 'missing arrival location'],
    // train, missing departure location
    ['noDepName', '{"itineraries":[{"segments":[{"number":"A123","arrName":"arrname","depDate":1893542400,"arrDate":1893628800}],"confirmationNumbers":[["ABCD",null]],"type":"train"}]}', 'missing departure location'],
    // train, missing arr date
    ['noDepDate', '{"itineraries":[{"segments":[{"number":"A123","depName":"depname","arrName":"arr","depDate":1893628800}],"confirmationNumbers":[["ABCD",null]],"type":"train"}]}', 'missing arrDate'],
    // train, missing dep date
    ['noDepDate', '{"itineraries":[{"segments":[{"number":"A123","depName":"depname","arrName":"arr","arrDate":1893628800}],"confirmationNumbers":[["ABCD",null]],"type":"train"}]}', 'missing depDate'],
    // train, missing number
    ['noNumber', '{"itineraries":[{"segments":[{"depName":"depname","depDate":1893456000,"arrName":"arrname","arrDate":1893542400}],"confirmationNumbers":[["ABCD",null]],"type":"train"}]}', 'missing number'],
    //
    ['TRANSFER', null, null],
    // transfer, OK
    ['valid', '{"itineraries":[{"segments":[{"depName":"depname","depDate":1893456000,"arrName":"arrname","arrDate":1893542400}],"confirmationNumbers":[["ABCD",null]],"type":"transfer"}]}', null],
    // transfer, empty segment
    ['emptySegment', '{"itineraries":[{"segments":[{"duration": "1h"}],"confirmationNumbers":[["ABCD",null]],"type":"transfer"}]}', 'empty segment'],
    // transfer, no segments
    ['noSegments', '{"itineraries":[{"confirmationNumbers":[["ABCD",null]],"type":"transfer"}]}', 'missing segments'],
    // transfer, cancelled ok
    ['cancelled', '{"itineraries":[{"confirmationNumbers":[["ABCD",null]],"cancelled":true,"type":"transfer"}]}', null],
    // transfer, cancelled empty
    ['cancelledEmpty', '{"itineraries":[{"confirmationNumbers":[],"cancelled":true,"type":"transfer"}]}', 'missing confirmation number'],
    // transfer, cancelled + ota
    ['cancelledOta', '{"itineraries":[{"cancelled":true,"travelAgency":{"confirmationNumbers":[["ABCD",null]]},"type":"transfer"}]}', null],
    // transfer, cancelled + upper ota
    ['cancelledUpperOta', '{"travelAgency":{"confirmationNumbers":[["ABCD",null]]},"itineraries":[{"cancelled":true,"type":"transfer"}]}', null],
    // transfer, cancelled empty segment
    ['emptySegmentCancelled', '{"itineraries":[{"segments":[{}],"confirmationNumbers":[["ABCD",null]],"cancelled":true,"type":"transfer"}]}', 'empty segment'],
    // transfer, ota conf no
    ['otaConfNo', '{"itineraries":[{"noConfirmationNumber":true,"segments":[{"depName":"depname","depDate":1893456000,"arrName":"arrname","arrDate":1893542400}],"type":"transfer","travelAgency":{"confirmationNumbers":[["ABCD",null]]}}]}', null],
    // transfer, upper conf no
    ['upperConfNo', '{"travelAgency":{"confirmationNumbers":[["ABCD",null]]},"itineraries":[{"noConfirmationNumber":true,"segments":[{"depName":"depname","depDate":1893456000,"arrName":"arrname","arrDate":1893542400}],"type":"transfer"}]}', null],
    // transfer, no conf no
    ['noConfNo', '{"itineraries":[{"segments":[{"depName":"depname","depDate":1893456000,"arrName":"arrname","arrDate":1893542400}],"type":"transfer"}]}', 'missing confirmation number'],
    // transfer, dup conf no
    ['dupConfNo', '{"itineraries":[{"segments":[{"depName":"depname","depDate":1893456000,"arrName":"arrname","arrDate":1893542400}],"confirmationNumbers":[["ABCD",null],["ABCD",null]],"type":"transfer"}]}', 'duplicate elements in `confirmationNumbers`'],
    // transfer, dup ota conf no
    ['dupOtaConfNo', '{"itineraries":[{"noConfirmationNumber":true,"segments":[{"depName":"depname","depDate":1893456000,"arrName":"arrname","arrDate":1893542400}],"type":"transfer","travelAgency":{"confirmationNumbers":[["ABCD",null],["ABCD",null]]}}]}', 'duplicate elements in `confirmationNumbers`'],
    // transfer, identical locations
    ['identicalLocations', '{"itineraries":[{"segments":[{"depName":"depname","depDate":1893456000,"arrName":"depname","arrDate":1893542400}],"confirmationNumbers":[["ABCD",null]],"type":"transfer"}]}', null],
    // transfer, identical codes
    ['identicalCodes', '{"itineraries":[{"segments":[{"depCode":"AAA","depDate":1893456000,"arrCode":"AAA","arrDate":1893542400}],"confirmationNumbers":[["ABCD",null]],"type":"transfer"}]}', null],
    // transfer, identical dates
    ['identicalDates', '{"itineraries":[{"segments":[{"depName":"depname","depDate":1893456000,"arrName":"arr","arrDate":1893456000}],"confirmationNumbers":[["ABCD",null]],"type":"transfer"}]}', 'dates are identical'],
    // transfer, invalid dates
    ['invalidDates', '{"itineraries":[{"segments":[{"depName":"depname","depDate":1893542400,"arrName":"arr","arrDate":1893456000}],"confirmationNumbers":[["ABCD",null]],"type":"transfer"}]}', 'invalid dates'],
    // transfer, invalid dates but tzCross allowed
    ['invalidDates', '{"itineraries":[{"allowTzCross":true,"segments":[{"depName":"depname","depDate":1893542400,"arrName":"arr","arrDate":1893456000}],"confirmationNumbers":[["ABCD",null]],"type":"transfer"}]}', null],
    // transfer, dates far apart
    ['datesFarApart', '{"itineraries":[{"segments":[{"depName":"depname","depDate":1893542400,"arrName":"arr","arrDate":1893715260}],"confirmationNumbers":[["ABCD",null]],"type":"transfer"}]}', 'dates are too far apart'],
    // transfer, missing arrival location
    ['noArrName', '{"itineraries":[{"segments":[{"depName":"depname","depDate":1893542400,"arrDate":1893628800}],"confirmationNumbers":[["ABCD",null]],"type":"transfer"}]}', 'missing arrival location'],
    // transfer, invalid arrival location
    ['invalidArrName', '{"itineraries":[{"segments":[{"depName":"depname","depDate":1893456000,"arrName":"dropoff location","arrDate":1893542400}],"confirmationNumbers":[["ABCD",null]],"type":"transfer"}]}', 'possibly invalid arrival location'],
    // transfer, missing departure location
    ['noDepName', '{"itineraries":[{"segments":[{"arrName":"arrname","depDate":1893542400,"arrDate":1893628800}],"confirmationNumbers":[["ABCD",null]],"type":"transfer"}]}', 'missing departure location'],
    // transfer, invalid departure location
    ['invalidArrName', '{"itineraries":[{"segments":[{"depName":"pickup location","depDate":1893456000,"arrName":"arrname location","arrDate":1893542400}],"confirmationNumbers":[["ABCD",null]],"type":"transfer"}]}', 'possibly invalid departure location'],
    // transfer, missing arr date
    ['noDepDate', '{"itineraries":[{"segments":[{"depName":"depname","arrName":"arr","depDate":1893628800}],"confirmationNumbers":[["ABCD",null]],"type":"transfer"}]}', 'missing arrDate'],
    // transfer, missing dep date
    ['noDepDate', '{"itineraries":[{"segments":[{"depName":"depname","arrName":"arr","arrDate":1893628800}],"confirmationNumbers":[["ABCD",null]],"type":"transfer"}]}', 'missing depDate'],
    //
    ['FERRY', null, null],
    // ferry, OK
    ['valid', '{"itineraries":[{"segments":[{"vehicles":[{"length":"1m","height":".3m","width":".5m"}],"trailers":[{"type":"type1","model":"model1"}],"depName":"dep port","depDate":1893591000,"arrName":"arr port","arrDate":1893598200}],"confirmationNumbers":[["ABCD",null]],"type":"ferry"}]}', null],
    // ferry, empty segment
    ['emptySegment', '{"itineraries":[{"segments":[{}],"confirmationNumbers":[["ABCD",null]],"type":"ferry"}]}', 'empty segment'],
    // ferry, no segments
    ['noSegments', '{"itineraries":[{"segments":[],"confirmationNumbers":[["ABCD",null]],"type":"ferry"}]}', 'missing segments'],
    // ferry, cancelled ok
    ['cancelled', '{"itineraries":[{"confirmationNumbers":[["ABCD",null]],"cancelled":true,"type":"ferry"}]}', null],
    // ferry, cancelled empty
    ['cancelledEmpty', '{"itineraries":[{"confirmationNumbers":[],"cancelled":true,"type":"ferry"}]}', 'missing confirmation number'],
    // ferry, cancelled + ota
    ['cancelledOta', '{"itineraries":[{"cancelled":true,"travelAgency":{"confirmationNumbers":[["ABCD",null]]},"type":"ferry"}]}', null],
    // ferry, cancelled + upper ota
    ['cancelledUpperOta', '{"travelAgency":{"confirmationNumbers":[["ABCD",null]]},"itineraries":[{"cancelled":true,"type":"ferry"}]}', null],
    // ferry, cancelled empty segment
    ['emptySegmentCancelled', '{"itineraries":[{"segments":[{}],"confirmationNumbers":[["ABCD",null]],"cancelled":true,"type":"ferry"}]}', 'empty segment'],
    // ferry, ota conf no
    ['otaConfNo', '{"itineraries":[{"noConfirmationNumber":true,"segments":[{"depName":"depname","depDate":1893456000,"arrName":"arrname","arrDate":1893542400}],"type":"ferry","travelAgency":{"confirmationNumbers":[["ABCD",null]]}}]}', null],
    // ferry, upper conf no
    ['upperConfNo', '{"travelAgency":{"confirmationNumbers":[["ABCD",null]]},"itineraries":[{"noConfirmationNumber":true,"segments":[{"depName":"depname","depDate":1893456000,"arrName":"arrname","arrDate":1893542400}],"type":"ferry"}]}', null],
    // ferry, no conf no
    ['noConfNo', '{"itineraries":[{"segments":[{"depName":"depname","depDate":1893456000,"arrName":"arrname","arrDate":1893542400}],"type":"ferry"}]}', 'missing confirmation number'],
    // ferry, dup conf no
    ['dupConfNo', '{"itineraries":[{"segments":[{"depName":"depname","depDate":1893456000,"arrName":"arrname","arrDate":1893542400}],"confirmationNumbers":[["ABCD",null],["ABCD",null]],"type":"ferry"}]}', 'duplicate elements in `confirmationNumbers`'],
    // ferry, dup ota conf no
    ['dupOtaConfNo', '{"itineraries":[{"noConfirmationNumber":true,"segments":[{"depName":"depname","depDate":1893456000,"arrName":"arrname","arrDate":1893542400}],"type":"ferry","travelAgency":{"confirmationNumbers":[["ABCD",null],["ABCD",null]]}}]}', 'duplicate elements in `confirmationNumbers`'],
    // ferry, identical locations
    ['identicalLocations', '{"itineraries":[{"segments":[{"depName":"depname","depDate":1893456000,"arrName":"depname","arrDate":1893542400}],"confirmationNumbers":[["ABCD",null]],"type":"ferry"}]}', 'locations are identical'],
    // ferry, identical dates
    ['identicalDates', '{"itineraries":[{"segments":[{"depName":"depname","depDate":1893456000,"arrName":"arr","arrDate":1893456000}],"confirmationNumbers":[["ABCD",null]],"type":"ferry"}]}', 'dates are identical'],
    // ferry, invalid dates
    ['invalidDates', '{"itineraries":[{"segments":[{"depName":"depname","depDate":1893542400,"arrName":"arr","arrDate":1893456000}],"confirmationNumbers":[["ABCD",null]],"type":"ferry"}]}', 'invalid dates'],
    // ferry, invalid dates but allow cross tz
    ['invalidDates', '{"itineraries":[{"allowTzCross":true,"segments":[{"depName":"depname","depDate":1893542400,"arrName":"arr","arrDate":1893456000}],"confirmationNumbers":[["ABCD",null]],"type":"ferry"}]}', null],
    // ferry, dates far apart
    ['datesFarApart', '{"itineraries":[{"segments":[{"depName":"depname","depDate":1893542400,"arrName":"arr","arrDate":1893715260}],"confirmationNumbers":[["ABCD",null]],"type":"ferry"}]}', 'dates are too far apart'],
    // ferry, missing arrival location
    ['noArrName', '{"itineraries":[{"segments":[{"depName":"depname","depDate":1893542400,"arrDate":1893628800}],"confirmationNumbers":[["ABCD",null]],"type":"ferry"}]}', 'missing arrival location'],
    // ferry, missing departure location
    ['noDepName', '{"itineraries":[{"segments":[{"arrName":"arrname","depDate":1893542400,"arrDate":1893628800}],"confirmationNumbers":[["ABCD",null]],"type":"ferry"}]}', 'missing departure location'],
    // ferry, missing arr date
    ['noDepDate', '{"itineraries":[{"segments":[{"depName":"depname","arrName":"arr","depDate":1893628800}],"confirmationNumbers":[["ABCD",null]],"type":"ferry"}]}', 'missing arrDate'],
    // ferry, missing dep date
    ['noDepDate', '{"itineraries":[{"segments":[{"depName":"depname","arrName":"arr","arrDate":1893628800}],"confirmationNumbers":[["ABCD",null]],"type":"ferry"}]}', 'missing depDate'],
    // ferry, empty vehicle
    ['emptyVehicle', '{"itineraries":[{"segments":[{"vehicles":[{}],"trailers":[{"type":"type1","model":"model1"}],"depName":"dep port","depDate":1893591000,"arrName":"arr port","arrDate":1893598200}],"confirmationNumbers":[["ABCD",null]],"type":"ferry"}]}', 'empty vehicle'],
    //
    ['CRUISE', null, null],
    // cruise, OK
    ['valid', '{"itineraries":[{"segments":[{"name":"port1","aboard":1893504600},{"name":"port2","ashore":1893578400,"aboard":1893582000},{"name":"port3","ashore":1893679200}],"confirmationNumbers":[["ABCD",null]],"type":"cruise"}]}', null],
    // cruise, empty segment
    ['emptySegment', '{"itineraries":[{"segments":[{"name":"port1","aboard":1893504600},{},{"name":"port3","ashore":1893679200}],"confirmationNumbers":[["ABCD",null]],"type":"cruise"}]}', 'empty segment'],
    // cruise, no segments
    ['noSegments', '{"itineraries":[{"segments":[],"confirmationNumbers":[["ABCD",null]],"type":"cruise"}]}', 'missing segments'],
    // cruise, cancelled ok
    ['cancelled', '{"itineraries":[{"confirmationNumbers":[["ABCD",null]],"cancelled":true,"type":"cruise"}]}', null],
    // cruise, cancelled empty
    ['cancelledEmpty', '{"itineraries":[{"confirmationNumbers":[],"cancelled":true,"type":"cruise"}]}', 'missing confirmation number'],
    // cruise, cancelled + ota
    ['cancelledOta', '{"itineraries":[{"cancelled":true,"travelAgency":{"confirmationNumbers":[["ABCD",null]]},"type":"cruise"}]}', null],
    // cruise, cancelled + upper ota
    ['cancelledUpperOta', '{"travelAgency":{"confirmationNumbers":[["ABCD",null]]},"itineraries":[{"cancelled":true,"type":"cruise"}]}', null],
    // cruise, cancelled empty segment
    ['emptySegmentCancelled', '{"itineraries":[{"segments":[{}],"confirmationNumbers":[["ABCD",null]],"cancelled":true,"type":"cruise"}]}', 'empty segment'],
    // cruise, ota conf no
    ['otaConfNo', '{"itineraries":[{"noConfirmationNumber":true,"segments":[{"name":"port1","aboard":1893504600},{"name":"port2","ashore":1893578400,"aboard":1893582000},{"name":"port3","ashore":1893679200}],"type":"cruise","travelAgency":{"confirmationNumbers":[["ABCD",null]]}}]}', null],
    // cruise, upper conf no
    ['upperConfNo', '{"travelAgency":{"confirmationNumbers":[["ABCD",null]]},"itineraries":[{"noConfirmationNumber":true,"segments":[{"name":"port1","aboard":1893504600},{"name":"port2","ashore":1893578400,"aboard":1893582000},{"name":"port3","ashore":1893679200}],"type":"cruise"}]}', null],
    // cruise, no conf no
    ['noConfNo', '{"itineraries":[{"segments":[{"name":"port1","aboard":1893504600},{"name":"port2","ashore":1893578400,"aboard":1893582000},{"name":"port3","ashore":1893679200}],"type":"cruise"}]}', 'missing confirmation number'],
    // cruise, dup conf no
    ['dupConfNo', '{"itineraries":[{"segments":[{"name":"port1","aboard":1893504600},{"name":"port2","ashore":1893578400,"aboard":1893582000},{"name":"port3","ashore":1893679200}],"confirmationNumbers":[["ABCD",null],["ABCD",null]],"type":"cruise"}]}', 'duplicate elements in `confirmationNumbers`'],
    // cruise, dup ota conf no
    ['dupOtaConfNo', '{"itineraries":[{"noConfirmationNumber":true,"segments":[{"name":"port1","aboard":1893504600},{"name":"port2","ashore":1893578400,"aboard":1893582000},{"name":"port3","ashore":1893679200}],"type":"cruise","travelAgency":{"confirmationNumbers":[["ABCD",null],["ABCD",null]]}}]}', 'duplicate elements in `confirmationNumbers`'],
    // cruise, empty port
    ['emptyPort', '{"itineraries":[{"segments":[{"name":"port1","aboard":1893504600},{"ashore":1893578400,"aboard":1893582000},{"name":"port3","ashore":1893679200}],"confirmationNumbers":[["ABCD",null]],"type":"cruise"}]}', 'empty port'],
    // cruise, invalid dates
    ['invalidDates', '{"itineraries":[{"segments":[{"name":"port1","aboard":1893504600},{"name":"port2","ashore":1893585600,"aboard":1893582000},{"name":"port3","ashore":1893679200}],"confirmationNumbers":[["ABCD",null]],"type":"cruise"}]}', 'invalid dates'],
    // cruise, dates too far apart
    ['datesFarApart', '{"itineraries":[{"segments":[{"name":"port1","aboard":1893504600},{"name":"port2","ashore":1893582000,"aboard":1895313600},{"name":"port3","ashore":1895320800}],"confirmationNumbers":[["ABCD",null]],"type":"cruise"}]}', 'dates are too far apart'],
    // cruise, missing dates
    ['missingDate', '{"itineraries":[{"segments":[{"name":"port1","aboard":1893504600},{"name":"port2","ashore":1893582000,"aboard":1893585600},{"name":"port3"}],"confirmationNumbers":[["ABCD",null]],"type":"cruise"}]}', 'aboard/ashore dates are required'],
    //
    ['EVENT', null, null],
    // event, OK
    ['valid', '{"itineraries":[{"address":"event address","name":"name","eventType":2,"startDate":1893504600,"noEndDate":true,"confirmationNumbers":[["ABCD",null]],"type":"event"}]}', null],
    // event, cancelled confNo
    ['cancelledConfNo', '{"itineraries":[{"confirmationNumbers":[["ABCD",null]],"cancelled":true,"type":"event"}]}', null],
    // event, cancelled empty
    ['cancelledEmpty', '{"itineraries":[{"confirmationNumbers":[],"name":"name","cancelled":true,"type":"event"}]}', 'not enough info for cancelled'],
    // event, cancelled + ota
    ['cancelledOta', '{"itineraries":[{"cancelled":true,"travelAgency":{"confirmationNumbers":[["ABCD",null]]},"type":"event"}]}', null],
    // event, cancelled + upper ota
    ['cancelledUpperOta', '{"travelAgency":{"confirmationNumbers":[["ABCD",null]]},"itineraries":[{"cancelled":true,"type":"event"}]}', null],
    // event, cancelled extra info
    ['cancelledExtra', '{"itineraries":[{"name":"name","startDate":1893504600,"confirmationNumbers":[],"cancelled":true,"type":"event"}]}', null],
    // event, ota conf no
    ['otaConfNo', '{"itineraries":[{"noConfirmationNumber":true,"address":"event address","name":"name","eventType":2,"startDate":1893504600,"noEndDate":true,"type":"event","travelAgency":{"confirmationNumbers":[["ABCD",null]]}}]}', null],
    // event, upper conf no
    ['upperConfNo', '{"travelAgency":{"confirmationNumbers":[["ABCD",null]]},"itineraries":[{"noConfirmationNumber":true,"address":"event address","name":"name","eventType":2,"startDate":1893504600,"noEndDate":true,"type":"event"}]}', null],
    // event, no conf no
    ['noConfNo', '{"itineraries":[{"address":"event address","name":"name","eventType":2,"startDate":1893504600,"noEndDate":true,"type":"event"}]}', 'missing confirmation number'],
    // event, dup conf no
    ['dupConfNo', '{"itineraries":[{"address":"event address","name":"name","eventType":2,"startDate":1893504600,"noEndDate":true,"confirmationNumbers":[["ABCD",null],["ABCD",null]],"type":"event"}]}', 'duplicate elements in `confirmationNumbers`'],
    // event, dup ota conf no
    ['dupOtaConfNo', '{"itineraries":[{"noConfirmationNumber":true,"address":"event address","name":"name","eventType":2,"startDate":1893504600,"noEndDate":true,"type":"event","travelAgency":{"confirmationNumbers":[["ABCD",null],["ABCD",null]]}}]}', 'duplicate elements in `confirmationNumbers`'],
    // event, no name
    ['noName', '{"itineraries":[{"address":"event address","eventType":2,"startDate":1893504600,"noEndDate":true,"confirmationNumbers":[["ABCD",null]],"type":"event"}]}', 'missing name'],
    // event, no address
    ['noAddress', '{"itineraries":[{"name":"name","eventType":2,"startDate":1893504600,"noEndDate":true,"confirmationNumbers":[["ABCD",null]],"type":"event"}]}', 'missing address'],
    // event, no startDate
    ['noStartDate', '{"itineraries":[{"address":"event address","name":"name","eventType":2,"noEndDate":true,"confirmationNumbers":[["ABCD",null]],"type":"event"}]}', 'missing startDate'],
    // event, no endDate
    ['noEndDate', '{"itineraries":[{"address":"event address","name":"name","eventType":2,"startDate":1893504600,"confirmationNumbers":[["ABCD",null]],"type":"event"}]}', 'missing endDate'],
    // event, no type
    ['noType', '{"itineraries":[{"address":"event address","name":"name","startDate":1893504600,"noEndDate":true,"confirmationNumbers":[["ABCD",null]],"type":"event"}]}', 'missing type'],
    //
    ['HOTEL', null, null],
    // hotel, OK
    ['valid', '{"itineraries":[{"travellers":[["John Doe", null],["John Doe", null]],"hotelName":"name","address":"hotel address","checkInDate":1893504600,"checkOutDate":1893850200,"rooms":[{"type":"room","description":"description"}],"confirmationNumbers":[["ABCD",null]],"type":"hotel"}]}', null],
    // hotel, cancelled confNo
    ['cancelledConfNo', '{"itineraries":[{"confirmationNumbers":[["ABCD",null]],"cancelled":true,"type":"hotel"}]}', null],
    // hotel, cancelled empty
    ['cancelledEmpty', '{"itineraries":[{"confirmationNumbers":[],"hotelName":"name","cancelled":true,"type":"hotel"}]}', 'not enough info for cancelled'],
    // hotel, cancelled + ota
    ['cancelledOta', '{"itineraries":[{"cancelled":true,"travelAgency":{"confirmationNumbers":[["ABCD",null]]},"type":"hotel"}]}', null],
    // hotel, cancelled + upper ota
    ['cancelledUpperOta', '{"travelAgency":{"confirmationNumbers":[["ABCD",null]]},"itineraries":[{"cancelled":true,"type":"hotel"}]}', null],
    // hotel, cancelled extra info
    ['cancelledExtra', '{"itineraries":[{"hotelName":"name","checkInDate":1893504600,"checkOutDate":1893850200,"confirmationNumbers":[],"cancelled":true,"type":"hotel"}]}', null],
    // hotel, ota conf no
    ['otaConfNo', '{"itineraries":[{"noConfirmationNumber":true,"hotelName":"name","address":"hotel address","checkInDate":1893504600,"checkOutDate":1893850200,"type":"hotel","travelAgency":{"confirmationNumbers":[["ABCD",null]]}}]}', null],
    // hotel, upper conf no
    ['upperConfNo', '{"travelAgency":{"confirmationNumbers":[["ABCD",null]]},"itineraries":[{"noConfirmationNumber":true,"hotelName":"name","address":"hotel address","checkInDate":1893504600,"checkOutDate":1893850200,"type":"hotel"}]}', null],
    // hotel, no conf no
    ['noConfNo', '{"itineraries":[{"hotelName":"name","address":"hotel address","checkInDate":1893504600,"checkOutDate":1893850200,"type":"hotel"}]}', 'missing confirmation number'],
    // hotel, dup conf no
    ['dupConfNo', '{"itineraries":[{"hotelName":"name","address":"hotel address","checkInDate":1893504600,"checkOutDate":1893850200,"confirmationNumbers":[["ABCD",null],["ABCD",null]],"type":"hotel"}]}', 'duplicate elements in `confirmationNumbers`'],
    // hotel, dup ota conf no
    ['dupOtaConfNo', '{"itineraries":[{"noConfirmationNumber":true,"hotelName":"name","address":"hotel address","checkInDate":1893504600,"checkOutDate":1893850200,"type":"hotel","travelAgency":{"confirmationNumbers":[["ABCD",null],["ABCD",null]]}}]}', 'duplicate elements in `confirmationNumbers`'],
    // hotel, no name
    ['noName', '{"itineraries":[{"address":"hotel address","checkInDate":1893504600,"checkOutDate":1893850200,"confirmationNumbers":[["ABCD",null]],"type":"hotel"}]}', 'missing hotel name'],
    // hotel, no address
    ['noAddress', '{"itineraries":[{"hotelName":"name","checkInDate":1893504600,"checkOutDate":1893850200,"confirmationNumbers":[["ABCD",null]],"type":"hotel"}]}', 'missing or invalid hotel address'],
    // hotel, invalid address
    ['invalidAddress', '{"itineraries":[{"hotelName":"name","address":"name","checkInDate":1893504600,"checkOutDate":1893850200,"confirmationNumbers":[["ABCD",null]],"type":"hotel"}]}', 'missing or invalid hotel address'],
    // hotel, no checkInDate
    ['noCheckInDate', '{"itineraries":[{"hotelName":"name","address":"hotel address","checkOutDate":1893850200,"confirmationNumbers":[["ABCD",null]],"type":"hotel"}]}', 'missing check-in date'],
    // hotel, no checkOutDate
    ['noCheckOutDate', '{"itineraries":[{"hotelName":"name","address":"hotel address","checkInDate":1893504600,"confirmationNumbers":[["ABCD",null]],"type":"hotel"}]}', 'missing check-out date'],
    // hotel, invalid dates
    ['invalidDates', '{"itineraries":[{"hotelName":"name","address":"hotel address","checkInDate":1893850200,"checkOutDate":1893504600,"confirmationNumbers":[["ABCD",null]],"type":"hotel"}]}', 'invalid dates'],
    // hotel, empty room
    ['emptyRoom', '{"itineraries":[{"hotelName":"name","address":"hotel address","checkInDate":1893504600,"checkOutDate":1893850200,"rooms":[{}],"confirmationNumbers":[["ABCD",null]],"type":"hotel"}]}', 'empty room'],
    // 4 identical travellers
    ['identicalPax', '{"itineraries":[{"travellers":[["John Doe", null],["John Doe", null],["John Doe", null],["John Doe", null]],"hotelName":"name","address":"hotel address","checkInDate":1893504600,"checkOutDate":1893850200,"rooms":[{"type":"room","description":"description"}],"confirmationNumbers":[["ABCD",null]],"type":"hotel"}]}', 'too many duplicate travellers'],
    //
    ['RENTAL', null, null],
    // rental, OK
    ['valid', '{"itineraries":[{"travellers":[["John Doe", null],["John Doe", null]],"pickUpLocation":"p location","pickUpDateTime":1893504600,"dropOffLocation":"d location","dropOffDateTime":1894282200,"confirmationNumbers":[["ABCD",null]],"type":"rental"}]}', null],
    // rental, cancelled confNo
    ['cancelledConfNo', '{"itineraries":[{"confirmationNumbers":[["ABCD",null]],"cancelled":true,"type":"rental"}]}', null],
    // rental, cancelled empty
    ['cancelledEmpty', '{"itineraries":[{"confirmationNumbers":[],"pickUpLocation":"p location","pickUpDateTime":1893504600,"dropOffLocation":"d location","dropOffDateTime":1894282200,"cancelled":true,"type":"rental"}]}', 'missing confirmation number'],
    // rental, cancelled + ota
    ['cancelledOta', '{"itineraries":[{"cancelled":true,"travelAgency":{"confirmationNumbers":[["ABCD",null]]},"type":"rental"}]}', null],
    // rental, cancelled + upper ota
    ['cancelledUpperOta', '{"travelAgency":{"confirmationNumbers":[["ABCD",null]]},"itineraries":[{"cancelled":true,"type":"rental"}]}', null],
    // rental, ota conf no
    ['otaConfNo', '{"itineraries":[{"noConfirmationNumber":true,"pickUpLocation":"p location","pickUpDateTime":1893504600,"dropOffLocation":"d location","dropOffDateTime":1894282200,"type":"rental","travelAgency":{"confirmationNumbers":[["ABCD",null]]}}]}', null],
    // rental, upper conf no
    ['upperConfNo', '{"travelAgency":{"confirmationNumbers":[["ABCD",null]]},"itineraries":[{"noConfirmationNumber":true,"pickUpLocation":"p location","pickUpDateTime":1893504600,"dropOffLocation":"d location","dropOffDateTime":1894282200,"type":"rental"}]}', null],
    // rental, no conf no
    ['noConfNo', '{"itineraries":[{"pickUpLocation":"p location","pickUpDateTime":1893504600,"dropOffLocation":"d location","dropOffDateTime":1894282200,"type":"rental"}]}', 'missing confirmation number'],
    // rental, dup conf no
    ['dupConfNo', '{"itineraries":[{"pickUpLocation":"p location","pickUpDateTime":1893504600,"dropOffLocation":"d location","dropOffDateTime":1894282200,"confirmationNumbers":[["ABCD",null],["ABCD",null]],"type":"rental"}]}', 'duplicate elements in `confirmationNumbers`'],
    // rental, dup ota conf no
    ['dupOtaConfNo', '{"itineraries":[{"noConfirmationNumber":true,"pickUpLocation":"p location","pickUpDateTime":1893504600,"dropOffLocation":"d location","dropOffDateTime":1894282200,"type":"rental","travelAgency":{"confirmationNumbers":[["ABCD",null],["ABCD",null]]}}]}', 'duplicate elements in `confirmationNumbers`'],
    // rental, no p location
    ['noPLocation', '{"itineraries":[{"pickUpDateTime":1893504600,"dropOffLocation":"d location","dropOffDateTime":1894282200,"confirmationNumbers":[["ABCD",null]],"type":"rental"}]}', 'missing pickup location'],
    // rental, no d location
    ['noDLocation', '{"itineraries":[{"pickUpLocation":"p location","pickUpDateTime":1893504600,"dropOffDateTime":1894282200,"confirmationNumbers":[["ABCD",null]],"type":"rental"}]}', 'missing dropoff location'],
    // rental, no p date
    ['noPDate', '{"itineraries":[{"pickUpLocation":"p location","dropOffLocation":"d location","dropOffDateTime":1894282200,"confirmationNumbers":[["ABCD",null]],"type":"rental"}]}', 'missing pickup date'],
    // rental, no d date
    ['noDDate', '{"itineraries":[{"pickUpLocation":"p location","pickUpDateTime":1893504600,"dropOffLocation":"d location","confirmationNumbers":[["ABCD",null]],"type":"rental"}]}', 'missing dropoff date'],
    // rental, same dates
    ['sameDates', '{"itineraries":[{"pickUpLocation":"p location","pickUpDateTime":1893504600,"dropOffLocation":"d location","dropOffDateTime":1893504600,"confirmationNumbers":[["ABCD",null]],"type":"rental"}]}', 'invalid dates'],
    // rental, invalid dates
    ['invalidDates', '{"itineraries":[{"pickUpLocation":"p location","pickUpDateTime":1894282200,"dropOffLocation":"d location","dropOffDateTime":1893504600,"confirmationNumbers":[["ABCD",null]],"type":"rental"}]}', 'invalid dates'],
    // rental, invalid p location
    //['invalidPLocation', '{"itineraries":[{"pickUpLocation":"pickup location","pickUpDateTime":1893504600,"dropOffLocation":"d location","dropOffDateTime":1894282200,"confirmationNumbers":[["ABCD",null]],"type":"rental"}]}', 'possibly invalid pickup location'],
    // rental, invalid d location
    //['invalidDLocation', '{"itineraries":[{"pickUpLocation":"p location","pickUpDateTime":1893504600,"dropOffLocation":"dropoff location","dropOffDateTime":1894282200,"confirmationNumbers":[["ABCD",null]],"type":"rental"}]}', 'possibly invalid dropoff location'],
    // rental, identical travellers
    ['identicalPax', '{"itineraries":[{"travellers":[["John Doe", null],["John Doe", null],["John Doe", null],["John Doe", null]],"pickUpLocation":"p location","pickUpDateTime":1893504600,"dropOffLocation":"d location","dropOffDateTime":1894282200,"confirmationNumbers":[["ABCD",null]],"type":"rental"}]}', 'too many duplicate travellers'],
    //
    ['FLIGHT', null, null],
    // flight, OK
    ['valid', '{"itineraries":[{"travellers":[["John Doe", null],["John Doe", null]],"segments":[{"airlineName":"AA","flightNumber":"11","depCode":"AAA","depDate":1893504600,"arrCode":"BBB","arrDate":1893511800}],"confirmationNumbers":[["ABCD",null]],"type":"flight"}]}', null],
    // flight, cancelled confNo
    ['cancelledConfNo', '{"itineraries":[{"confirmationNumbers":[["ABCD",null]],"segments":[],"cancelled":true,"type":"flight"}]}', null],
    // flight, cancelled empty
    ['cancelledEmpty', '{"itineraries":[{"confirmationNumbers":[],"segments":[{"airlineName":"AA","flightNumber":"11","depCode":"AAA"}],"cancelled":true,"type":"flight"}]}', 'not enough info for cancelled'],
    // flight, cancelled + ota
    ['cancelledOta', '{"itineraries":[{"cancelled":true,"travelAgency":{"confirmationNumbers":[["ABCD",null]]},"type":"flight"}]}', null],
    // flight, cancelled + upper ota
    ['cancelledUpperOta', '{"travelAgency":{"confirmationNumbers":[["ABCD",null]]},"itineraries":[{"cancelled":true,"type":"flight"}]}', null],
    // flight, cancelled segment
    ['cancelledSegment', '{"itineraries":[{"segments":[{"airlineName":"AA","flightNumber":"11","depCode":"AAA","depDate":1893504600}],"cancelled":true,"type":"flight"}]}', null],
    // flight, cancelled segment location
    ['cancelledSegmentLocator', '{"itineraries":[{"segments":[{"airlineName":"AA","depCode":"AAA","confirmation":"ABCDEF"}],"cancelled":true,"type":"flight"}]}', null],
    // flight, ota conf no
    ['otaConfNo', '{"itineraries":[{"noConfirmationNumber":true,"segments":[{"airlineName":"AA","flightNumber":"11","depCode":"AAA","depDate":1893504600,"arrCode":"BBB","arrDate":1893511800}],"type":"flight","travelAgency":{"confirmationNumbers":[["ABCD",null]]}}]}', null],
    // flight, upper conf no
    ['upperConfNo', '{"travelAgency":{"confirmationNumbers":[["ABCD",null]]},"itineraries":[{"noConfirmationNumber":true,"segments":[{"airlineName":"AA","flightNumber":"11","depCode":"AAA","depDate":1893504600,"arrCode":"BBB","arrDate":1893511800}],"type":"flight"}]}', null],
    // flight, no conf no
    ['noConfNo', '{"itineraries":[{"segments":[{"airlineName":"AA","flightNumber":"11","depCode":"AAA","depDate":1893504600,"arrCode":"BBB","arrDate":1893511800}],"type":"flight"}]}', 'missing confirmation number'],
    // flight, dup conf no
    ['dupConfNo', '{"itineraries":[{"segments":[{"airlineName":"AA","flightNumber":"11","depCode":"AAA","depDate":1893504600,"arrCode":"BBB","arrDate":1893511800}],"confirmationNumbers":[["ABCD",null],["ABCD",null]],"type":"flight"}]}', 'duplicate elements in `confirmationNumbers`'],
    // flight, dup ota conf no
    ['dupOtaConfNo', '{"itineraries":[{"noConfirmationNumber":true,"segments":[{"airlineName":"AA","flightNumber":"11","depCode":"AAA","depDate":1893504600,"arrCode":"BBB","arrDate":1893511800}],"type":"flight","travelAgency":{"confirmationNumbers":[["ABCD",null],["ABCD",null]]}}]}', 'duplicate elements in `confirmationNumbers`'],
    // flight, identical codes
    ['sameCodes', '{"itineraries":[{"segments":[{"airlineName":"AA","flightNumber":"11","depCode":"AAA","depDate":1893504600,"arrCode":"AAA","arrDate":1893511800}],"confirmationNumbers":[["ABCD",null]],"type":"flight"}]}', 'codes are identical'],
    // flight, identical names
    ['sameNames', '{"itineraries":[{"segments":[{"airlineName":"AA","flightNumber":"11","depName":"name1","depDate":1893504600,"arrName":"name1","noDepCode":true,"noArrCode":true,"arrDate":1893511800}],"confirmationNumbers":[["ABCD",null]],"type":"flight"}]}', 'locations are identical'],
    // flight, empty segment
    ['emptySegment', '{"itineraries":[{"segments":[{"duration":"123"}],"cancelled":true,"confirmationNumbers":[["ABCD",null]],"type":"flight"}]}', 'empty segment'],
    // flight, depDay conflict
    ['depDayConflict', '{"itineraries":[{"segments":[{"airlineName":"AA","flightNumber":"11","depCode":"AAA","depDate":1893504600,"depDay":1893456000,"arrCode":"BBB","arrDate":1893511800}],"confirmationNumbers":[["ABCD",null]],"type":"flight"}]}', 'wrong parsing: depDate XOR depDay'],
    // flight, arrDay conflict
    ['arrDayConflict', '{"itineraries":[{"segments":[{"airlineName":"AA","flightNumber":"11","depCode":"AAA","depDate":1893504600,"arrDay":1893456000,"arrCode":"BBB","arrDate":1893511800}],"confirmationNumbers":[["ABCD",null]],"type":"flight"}]}', 'wrong parsing: arrDate XOR arrDay'],
    // flight, invalid depDay
    ['invalidDepDay', '{"itineraries":[{"segments":[{"airlineName":"AA","flightNumber":"11","depCode":"AAA","noDepDate":true,"depDay":1893502800,"arrCode":"BBB","arrDate":1893511800}],"confirmationNumbers":[["ABCD",null]],"type":"flight"}]}', 'wrong parsing: depDay with time'],
    // flight, invalid arrDay
    ['invalidArrDay', '{"itineraries":[{"segments":[{"airlineName":"AA","flightNumber":"11","depCode":"AAA","depDate":1893504600,"arrDay":1893456000,"arrCode":"BBB","arrDay":1893502800,"noArrDate":true}],"confirmationNumbers":[["ABCD",null]],"type":"flight"}]}', 'wrong parsing: arrDay with time'],
    // flight, dates are too far apart
    ['datesAreFarApart', '{"itineraries":[{"segments":[{"airlineName":"AA","flightNumber":"11","depCode":"AAA","depDate":1893504600,"arrCode":"BBB","arrDate":1894021200}],"confirmationNumbers":[["ABCD",null]],"type":"flight"}]}', 'dates are too far apart'],
    // flight, missing dep location
    ['missingDepLocation', '{"itineraries":[{"segments":[{"airlineName":"AA","noFlightNumber":true,"noDepCode":true,"depDate":1893504600,"arrCode":"BBB","arrDate":1893511800}],"confirmationNumbers":[["ABCD",null]],"type":"flight"}]}', 'missing departure location'],
    // flight, missing arr location
    ['missingArrLocation', '{"itineraries":[{"segments":[{"airlineName":"AA","noFlightNumber":true,"depCode":"AAA","depDate":1893504600,"noArrCode":true,"arrDate":1893511800}],"confirmationNumbers":[["ABCD",null]],"type":"flight"}]}', 'missing arrival location'],
    // flight, identical travellers
    ['identicalPax', '{"itineraries":[{"travellers":[["John Doe", null],["John Doe", null],["John Doe", null],["John Doe", null]],"segments":[{"airlineName":"AA","flightNumber":"11","depCode":"AAA","depDate":1893504600,"arrCode":"BBB","arrDate":1893511800}],"confirmationNumbers":[["ABCD",null]],"type":"flight"}]}', 'too many duplicate travellers'],
    // flight, fake code 2 segments
    ['fakeCodesValid', '{"itineraries":[{"travellers":[["John Doe", null],["John Doe", null]],"segments":[{"airlineName":"AA","flightNumber":"11","depCode":"HDQ","depDate":1893504600,"arrCode":"BBB","arrDate":1893511800},{"airlineName":"AA","flightNumber":"11","depCode":"AAA","depDate":1893504600,"arrCode":"BBB","arrDate":1893511800}],"confirmationNumbers":[["ABCD",null]],"type":"flight"}]}', null],
    // flight, fake code 1 segment
    ['fakeCodesError', '{"itineraries":[{"travellers":[["John Doe", null],["John Doe", null]],"segments":[{"airlineName":"AA","flightNumber":"11","depCode":"HDQ","depDate":1893504600,"arrCode":"BBB","arrDate":1893511800}],"confirmationNumbers":[["ABCD",null]],"type":"flight"}]}', 'empty segments'],

];

$ok = $err = 0;
foreach($tests as list($name, $json, $error)) {
    if (is_null($json)) {
        $console->info('-----'.$name.'------');
        continue;
    }
    try {
        instance()->fromJson($json)->validate();
        if (!is_null($error)) {
            $console->error(sprintf('%s: expecting error `%s`, got none', $name, $error));
            $err++;
        }
        else {
            $console->info(sprintf('%s: OK', $name));
            $ok++;
        }
    }
    catch(\AwardWallet\Schema\Parser\Component\InvalidDataException $e) {
        if (is_null($error)) {
            $console->error(sprintf('%s: unexpected error `%s`', $name, $e->getMessage()));
            $err++;
        }
        elseif (strpos(preg_replace('/^[^:]+:\s*/', '', $e->getMessage()), $error) !== 0) {
            $console->error(sprintf('%s: expecting error `%s`, got `%s`', $name, $error, $e->getMessage()));
            $err++;
        }
        else {
            $console->info(sprintf('%s: OK', $name));
            $ok++;
        }
    }
}
$console->log($err > 0 ? \Psr\Log\LogLevel::ERROR : \Psr\Log\LogLevel::INFO, sprintf('%d success, %d errors', $ok, $err));

function instance(): \AwardWallet\Schema\Parser\Email\Email
{
    $options = new \AwardWallet\Schema\Parser\Component\Options();
    $options->logDebug = false;
    $options->throwOnInvalid = true;
    $e = new \AwardWallet\Schema\Parser\Email\Email('e', $options);
    return $e;
}