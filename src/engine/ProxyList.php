<?php

namespace AwardWallet\Engine;

use AccountCheckerLogger;
use AwardWallet\Common\Parsing\ProxyChecker;
use AwardWallet\Common\Strings;
use CurlDriver;
use Exception;
use HttpBrowser;
use HttpDriverRequest;
use Memcached;
use StatLogger;
use Throttler;

/**
 * Class ProxyList.
 *
 * @property HttpBrowser $http
 * @property array $AccountFields
 * @property array $State
 * @property int $attempt
 * @property AccountCheckerLogger $logger
 */
trait ProxyList
{
    public $listCountriesGeoSurf = ["af", "al", "dz", "ao", "ai", "ag", "ar", "am", "aw", "au", "at", "az", "bs", "bh", "bd", "bb", "by", "be", "bz", "bj", "bo", "ba", "bw", "br", "bn", "bg", "kh", "cm", "ca", "cv", "ky", "cl", "cn", "co", "km", "cg", "cr", "hr", "cy", "cz", "dk", "do", "ec", "eg", "sv", "ee", "et", "fi", "fr", "ge", "de", "gh", "gr", "gp", "gt", "gy", "ht", "hn", "hk", "hu", "is", "in", "id", "ir", "iq", "ie", "il", "it", "ci", "jm", "jp", "jo", "kz", "ke", "kr", "kw", "kg", "la", "lv", "lb", "ly", "lt", "lu", "mo", "mk", "mg", "my", "mv", "ml", "mt", "mq", "mu", "mx", "md", "mn", "me", "ma", "mz", "mm", "na", "np", "nl", "nc", "nz", "ni", "ng", "no", "om", "pk", "ps", "pa", "py", "pe", "ph", "pl", "pt", "pr", "qa", "re", "ro", "ru", "lc", "vc", "sa", "sn", "rs", "sc", "sg", "sk", "si", "za", "es", "lk", "sd", "sr", "se", "ch", "sy", "tw", "tz", "th", "tt", "tn", "tr", "ug", "ua", "ae", "gb", "us", "uy", "uz", "ve", "vn", "ye", "zm", "zw"];
    private $accessMarkerUrl = 'https://awardwallet-public.s3.amazonaws.com/access_marker.txt';
    private $accessMarkerContent = 'access_ok';

    private $jsonFromGeoSurf = '{"ae":{"domain":"ae-10m.geosurf.io","port1":26000,"port2":26999},"af":{"domain":"af-10m.geosurf.io","port1":12000,"port2":12499},"ag":{"domain":"ag-10m.geosurf.io","port1":14500,"port2":14999},"ai":{"domain":"ai-10m.geosurf.io","port1":14000,"port2":14499},"al":{"domain":"al-10m.geosurf.io","port1":12500,"port2":12999},"am":{"domain":"am-10m.geosurf.io","port1":15500,"port2":15999},"ao":{"domain":"ao-10m.geosurf.io","port1":13500,"port2":13999},"ar":{"domain":"ar-10m.geosurf.io","port1":15000,"port2":15499},"at":{"domain":"at-10m.geosurf.io","port1":16500,"port2":16999},"au":{"domain":"au-10m.geosurf.io","port1":14000,"port2":14999},"aw":{"domain":"aw-10m.geosurf.io","port1":16000,"port2":16499},"az":{"domain":"az-10m.geosurf.io","port1":17000,"port2":17499},"ba":{"domain":"ba-10m.geosurf.io","port1":22000,"port2":22499},"bb":{"domain":"bb-10m.geosurf.io","port1":19000,"port2":19499},"bd":{"domain":"bd-10m.geosurf.io","port1":18500,"port2":18999},"be":{"domain":"be-10m.geosurf.io","port1":20000,"port2":20499},"bg":{"domain":"bg-10m.geosurf.io","port1":23000,"port2":23499},"bh":{"domain":"bh-10m.geosurf.io","port1":18000,"port2":18499},"bj":{"domain":"bj-10m.geosurf.io","port1":21000,"port2":21499},"bn":{"domain":"ctr-2-10m.geosurf.io","port1":30000,"port2":30019},"bo":{"domain":"bo-10m.geosurf.io","port1":21500,"port2":21999},"br":{"domain":"br-10m.geosurf.io","port1":18000,"port2":18999},"bs":{"domain":"bs-10m.geosurf.io","port1":17500,"port2":17999},"bw":{"domain":"bw-10m.geosurf.io","port1":22500,"port2":22999},"by":{"domain":"by-10m.geosurf.io","port1":19500,"port2":19999},"bz":{"domain":"bz-10m.geosurf.io","port1":20500,"port2":20999},"ca":{"domain":"ca-10m.geosurf.io","port1":9200,"port2":9999},"cg":{"domain":"cg-10m.geosurf.io","port1":27500,"port2":27999},"ch":{"domain":"ctr-2-10m.geosurf.io","port1":30020,"port2":30039},"ci":{"domain":"ctr-2-10m.geosurf.io","port1":30040,"port2":30059},"cl":{"domain":"cl-10m.geosurf.io","port1":25500,"port2":25999},"cm":{"domain":"cm-10m.geosurf.io","port1":24000,"port2":24499},"cn":{"domain":"cn-10m.geosurf.io","port1":26000,"port2":26499},"co":{"domain":"co-10m.geosurf.io","port1":26500,"port2":26999},"cr":{"domain":"cr-10m.geosurf.io","port1":28000,"port2":28499},"cv":{"domain":"cv-10m.geosurf.io","port1":24500,"port2":24999},"cy":{"domain":"cy-10m.geosurf.io","port1":29000,"port2":29499},"cz":{"domain":"cz-10m.geosurf.io","port1":29500,"port2":29999},"de":{"domain":"de-10m.geosurf.io","port1":11000,"port2":11999},"dk":{"domain":"ctr-2-10m.geosurf.io","port1":30060,"port2":30079},"do":{"domain":"ctr-2-10m.geosurf.io","port1":30080,"port2":30099},"dz":{"domain":"dz-10m.geosurf.io","port1":13000,"port2":13499},"ec":{"domain":"ctr-2-10m.geosurf.io","port1":30100,"port2":30119},"ee":{"domain":"ctr-2-10m.geosurf.io","port1":30120,"port2":30139},"eg":{"domain":"ctr-2-10m.geosurf.io","port1":30140,"port2":30159},"es":{"domain":"es-10m.geosurf.io","port1":20000,"port2":20999},"et":{"domain":"ctr-2-10m.geosurf.io","port1":30160,"port2":30179},"fi":{"domain":"ctr-2-10m.geosurf.io","port1":30180,"port2":30199},"fr":{"domain":"fr-10m.geosurf.io","port1":19000,"port2":19999},"gb":{"domain":"gb-10m.geosurf.io","port1":10000,"port2":10999},"ge":{"domain":"ctr-2-10m.geosurf.io","port1":30200,"port2":30219},"gh":{"domain":"ctr-2-10m.geosurf.io","port1":30220,"port2":30239},"gp":{"domain":"ctr-2-10m.geosurf.io","port1":30240,"port2":30259},"gr":{"domain":"ctr-2-10m.geosurf.io","port1":30260,"port2":30279},"gt":{"domain":"ctr-2-10m.geosurf.io","port1":30280,"port2":30299},"gy":{"domain":"ctr-2-10m.geosurf.io","port1":30300,"port2":30319},"hk":{"domain":"hk-10m.geosurf.io","port1":23000,"port2":23999},"hn":{"domain":"ctr-2-10m.geosurf.io","port1":30320,"port2":30339},"hr":{"domain":"hr-10m.geosurf.io","port1":28500,"port2":28999},"ht":{"domain":"ctr-2-10m.geosurf.io","port1":30340,"port2":30359},"hu":{"domain":"ctr-2-10m.geosurf.io","port1":30360,"port2":30379},"id":{"domain":"id-10m.geosurf.io","port1":25000,"port2":25999},"ie":{"domain":"ctr-2-10m.geosurf.io","port1":30380,"port2":30399},"il":{"domain":"ctr-2-10m.geosurf.io","port1":30400,"port2":30419},"in":{"domain":"in-10m.geosurf.io","port1":15000,"port2":15999},"iq":{"domain":"ctr-2-10m.geosurf.io","port1":30420,"port2":30439},"ir":{"domain":"ctr-2-10m.geosurf.io","port1":30440,"port2":30459},"is":{"domain":"ctr-2-10m.geosurf.io","port1":30460,"port2":30479},"it":{"domain":"it-10m.geosurf.io","port1":21000,"port2":21999},"jm":{"domain":"ctr-2-10m.geosurf.io","port1":30480,"port2":30499},"jo":{"domain":"ctr-2-10m.geosurf.io","port1":30500,"port2":30519},"jp":{"domain":"jp-10m.geosurf.io","port1":13000,"port2":13999},"ke":{"domain":"ctr-2-10m.geosurf.io","port1":30520,"port2":30539},"kg":{"domain":"ctr-2-10m.geosurf.io","port1":30540,"port2":30559},"kh":{"domain":"kh-10m.geosurf.io","port1":23500,"port2":23999},"km":{"domain":"km-10m.geosurf.io","port1":27000,"port2":27499},"kr":{"domain":"kr-10m.geosurf.io","port1":29000,"port2":29999},"kw":{"domain":"ctr-2-10m.geosurf.io","port1":30560,"port2":30579},"ky":{"domain":"ky-10m.geosurf.io","port1":25000,"port2":25499},"kz":{"domain":"ctr-2-10m.geosurf.io","port1":30580,"port2":30599},"la":{"domain":"ctr-2-10m.geosurf.io","port1":30600,"port2":30619},"lb":{"domain":"ctr-2-10m.geosurf.io","port1":30620,"port2":30639},"lc":{"domain":"ctr-2-10m.geosurf.io","port1":30640,"port2":30659},"lk":{"domain":"ctr-2-10m.geosurf.io","port1":30660,"port2":30679},"lt":{"domain":"ctr-2-10m.geosurf.io","port1":30680,"port2":30699},"lu":{"domain":"ctr-2-10m.geosurf.io","port1":30700,"port2":30719},"lv":{"domain":"ctr-2-10m.geosurf.io","port1":30720,"port2":30739},"ly":{"domain":"ctr-2-10m.geosurf.io","port1":30740,"port2":30759},"ma":{"domain":"ctr-2-10m.geosurf.io","port1":30760,"port2":30779},"md":{"domain":"ctr-2-10m.geosurf.io","port1":30780,"port2":30799},"me":{"domain":"ctr-2-10m.geosurf.io","port1":30800,"port2":30819},"mg":{"domain":"ctr-2-10m.geosurf.io","port1":30820,"port2":30839},"mk":{"domain":"ctr-2-10m.geosurf.io","port1":30840,"port2":30859},"ml":{"domain":"ctr-2-10m.geosurf.io","port1":30860,"port2":30879},"mm":{"domain":"ctr-2-10m.geosurf.io","port1":30880,"port2":30899},"mn":{"domain":"ctr-2-10m.geosurf.io","port1":30900,"port2":30919},"mo":{"domain":"ctr-2-10m.geosurf.io","port1":30920,"port2":30939},"mq":{"domain":"ctr-2-10m.geosurf.io","port1":30940,"port2":30959},"mt":{"domain":"ctr-2-10m.geosurf.io","port1":30960,"port2":30979},"mu":{"domain":"ctr-2-10m.geosurf.io","port1":30980,"port2":30999},"mv":{"domain":"ctr-2-10m.geosurf.io","port1":31000,"port2":31019},"mx":{"domain":"mx-10m.geosurf.io","port1":17000,"port2":17999},"my":{"domain":"my-10m.geosurf.io","port1":28000,"port2":28999},"mz":{"domain":"ctr-2-10m.geosurf.io","port1":31020,"port2":31039},"na":{"domain":"ctr-2-10m.geosurf.io","port1":31040,"port2":31059},"nc":{"domain":"ctr-2-10m.geosurf.io","port1":31060,"port2":31079},"ng":{"domain":"ctr-2-10m.geosurf.io","port1":31080,"port2":31099},"ni":{"domain":"ctr-2-10m.geosurf.io","port1":31100,"port2":31119},"nl":{"domain":"ctr-2-10m.geosurf.io","port1":31120,"port2":31139},"no":{"domain":"ctr-2-10m.geosurf.io","port1":31140,"port2":31159},"np":{"domain":"ctr-2-10m.geosurf.io","port1":31160,"port2":31179},"nz":{"domain":"ctr-2-10m.geosurf.io","port1":31180,"port2":31199},"om":{"domain":"ctr-2-10m.geosurf.io","port1":31200,"port2":31219},"pa":{"domain":"ctr-2-10m.geosurf.io","port1":31220,"port2":31239},"pe":{"domain":"ctr-2-10m.geosurf.io","port1":31240,"port2":31259},"ph":{"domain":"ph-10m.geosurf.io","port1":11000,"port2":11499},"pk":{"domain":"ctr-2-10m.geosurf.io","port1":31260,"port2":31279},"pl":{"domain":"pl-10m.geosurf.io","port1":22000,"port2":22999},"pr":{"domain":"ctr-2-10m.geosurf.io","port1":31280,"port2":31299},"ps":{"domain":"ctr-2-10m.geosurf.io","port1":31300,"port2":31319},"pt":{"domain":"ctr-2-10m.geosurf.io","port1":31320,"port2":31339},"py":{"domain":"ctr-2-10m.geosurf.io","port1":31340,"port2":31359},"qa":{"domain":"ctr-2-10m.geosurf.io","port1":31360,"port2":31379},"re":{"domain":"ctr-2-10m.geosurf.io","port1":31380,"port2":31399},"ro":{"domain":"ctr-2-10m.geosurf.io","port1":31400,"port2":31419},"rs":{"domain":"ctr-2-10m.geosurf.io","port1":31420,"port2":31439},"ru":{"domain":"ru-10m.geosurf.io","port1":24000,"port2":24999},"sa":{"domain":"sa-10m.geosurf.io","port1":10500,"port2":10999},"sc":{"domain":"ctr-2-10m.geosurf.io","port1":31440,"port2":31459},"sd":{"domain":"ctr-2-10m.geosurf.io","port1":31460,"port2":31479},"se":{"domain":"ctr-2-10m.geosurf.io","port1":31480,"port2":31499},"sg":{"domain":"ctr-2-10m.geosurf.io","port1":31500,"port2":31519},"si":{"domain":"ctr-2-10m.geosurf.io","port1":31520,"port2":31539},"sk":{"domain":"ctr-2-10m.geosurf.io","port1":31540,"port2":31559},"sn":{"domain":"ctr-2-10m.geosurf.io","port1":31560,"port2":31579},"sr":{"domain":"ctr-2-10m.geosurf.io","port1":31580,"port2":31599},"sv":{"domain":"ctr-2-10m.geosurf.io","port1":31600,"port2":31619},"sy":{"domain":"ctr-2-10m.geosurf.io","port1":31620,"port2":31639},"th":{"domain":"th-10m.geosurf.io","port1":16000,"port2":16999},"tn":{"domain":"ctr-2-10m.geosurf.io","port1":31640,"port2":31659},"tr":{"domain":"tr-10m.geosurf.io","port1":27000,"port2":27999},"tt":{"domain":"ctr-2-10m.geosurf.io","port1":31660,"port2":31679},"tw":{"domain":"tw-10m.geosurf.io","port1":10000,"port2":10499},"tz":{"domain":"ctr-2-10m.geosurf.io","port1":31680,"port2":31699},"ua":{"domain":"ctr-2-10m.geosurf.io","port1":31700,"port2":31719},"ug":{"domain":"ctr-2-10m.geosurf.io","port1":31720,"port2":31739},"us":{"domain":"us-10m.geosurf.io","port1":10000,"port2":19999},"uy":{"domain":"ctr-2-10m.geosurf.io","port1":31740,"port2":31759},"uz":{"domain":"ctr-2-10m.geosurf.io","port1":31760,"port2":31779},"vc":{"domain":"ctr-2-10m.geosurf.io","port1":31780,"port2":31799},"ve":{"domain":"ctr-2-10m.geosurf.io","port1":31800,"port2":31819},"vn":{"domain":"vn-10m.geosurf.io","port1":11500,"port2":11999},"xk":{"domain":"ctr-2-10m.geosurf.io","port1":31820,"port2":31839},"ye":{"domain":"ctr-2-10m.geosurf.io","port1":31840,"port2":31859},"za":{"domain":"ctr-2-10m.geosurf.io","port1":31860,"port2":31879},"zm":{"domain":"ctr-2-10m.geosurf.io","port1":31880,"port2":31899},"zw":{"domain":"ctr-2-10m.geosurf.io","port1":31900,"port2":31919}}';

    /**
     * DO servers with Dynamic IP
     * dataCenters - in what datacenters would you like to get proxy from, leave empty to select any datacenter.
     *
     * possible datacenters:
     *      'nyc1', 'nyc2', 'nyc3' - new york
     *      sfo1 - san francisco
     *      tor1 - toronto
     *      lon1 - london
     *      fra1 - frankfurt
     *      sgp1 - singapore
     *
     * You could use predefined sets:
     * $this->http->SetProxy($this->proxyDOP(\AwardWallet\Engine\Settings::DATACENTERS_EU));
     *
     * if there are no available proxies in specified datacenters, method will return any free proxy
     *
     * nyc1: #4 # New York 1
     * ams1: #1 # Amsterdam 1
     * sfo1: 4 # San Francisco 2
     * nyc2: # New York 2
     * ams2: #1 # Amsterdam 2
     * sgp1: #1 # Singapore 1
     * lon1: 2 # London 1
     * nyc3: 7 # New York 3
     * ams3: # 3 # Amsterdam 3
     * fra1: 6 # Frankfurt 1
     * tor1: 1 # Toronto 1
     */
    protected function proxyDOP($dataCenters = [], ?bool $answerWithDataCenter = false)
    {
        $this->http->Log("selecting DO proxy, preferred datacenters: " . implode(", ", $dataCenters));
        // internal domain, you should connet vpn to get it
        $address = 'dop.aws.awardwallet.com:3128';

        if (ConfigValue(CONFIG_TRAVEL_PLANS)) {
            $this->http->Log("debug mode, skip DO proxy selection");

            return $address;
        }

        $proxies = $this->getDoProxies($dataCenters, $answerWithDataCenter);

        if (!empty($proxies)) {
            if ($answerWithDataCenter) {
                $proxiesIn = $proxies;
                $proxies = $proxiesExt = [];

                foreach ($proxiesIn as $proxy) {
                    $parts = explode(':', $proxy);
                    $pAddress = $parts[0] . ':' . $parts[1];
                    $proxies[] = $pAddress;
                    $proxiesExt[$pAddress] = $parts[2] ?? '';
                }
            }
            $options = new FindProxyOptions();
            $options->timeout = 3;
            $options->isValid = function (?string $content, ?int $httpCode, ?int $curlErrno): bool {
                return $content === $this->accessMarkerContent;
            };
            $validAddresses = $this->findLiveProxies($proxies, $this->accessMarkerUrl, $options);

            if (count($validAddresses) === 0) {
                $this->http->Log("failed to find live proxy, will select first one");
                $address = $proxies[0];
            } else {
                $address = $validAddresses[0];
            }

            if ($answerWithDataCenter) {
                $address = $address . ':' . $proxiesExt[$address];
            }
        } elseif (ConfigValue(CONFIG_SITE_STATE) != SITE_STATE_DEBUG) {
            DieTrace("failed to get DOP proxy list", false);
        }

        return $this->proxy('DOP', $address);
    }

    protected function setProxyDOP($dataCenters = [])
    {
        $row = $this->proxyDOP($dataCenters, true);
        $address = explode(':', $row);
        $region = $address[2] ?? '';
        $address = "{$address[0]}:{$address[1]}";
        $this->http->SetProxy($address, true, 'dop', $region);
        $browser = new HttpBrowser("none", new CurlDriver());
        $browser->RetryCount = 0;
        $browser->SetProxy($address);

        $response = $browser->GetURL('https://ipinfo.io/ip', [], 20);

        if (!$response) {
            $this->logger->warning("failed to get https://ipinfo.io/ip, response code: {$browser->Response['code']}");
            $this->logger->info("set proxy BrightData static");
            $this->setProxyBrightData();

            return null;
        }
        $this->logger->info("live proxy found, response code: {$browser->Response['code']}");
        $proxyIp = trim($browser->Response['body']);
        $this->logger->info("proxy ip: " . $proxyIp);
        $this->State["proxy-ip"] = $proxyIp;
    }

    /**
     * @return array - ['ip1:port1', 'ip2:port2', ... ] or ['ip1:port1:dataCenter1', 'ip2:port2:dataCenter2', ... ]
     */
    protected function getDoProxies($dataCenters = [], ?bool $withDataCenter = false)
    {
        $cache = $this->getProxyMemcached();

        for ($n = 0; $n < 2; $n++) {
            $proxies = $cache->get('awardwallet_proxy_list_2'); // created by python script that manages digital ocean proxies

            if (!empty($proxies)) {
                break;
            }
            usleep(100000);
        }

        if (empty($proxies)) {
            $this->http->Log("No proxy in cache were found", LOG_LEVEL_ERROR);
            $this->sendNotification("empty dop proxy list in cache");

            return [];
        }

        $proxies = json_decode($proxies, true);
        // prevent traffic hit to one proxy
        shuffle($proxies);

        // sort proxy list by requests per minute, marked as invalid will go to the bottom, because of high rpm
        $throttler = $this->getThrottler($cache);
        $proxies = array_map(
            function ($proxy) use ($throttler) {
                return array_merge(
                    $proxy,
                    [
                        'rpm'     => $throttler->getThrottledRequestsCount($this->AccountFields['ProviderCode'] . '_' . $proxy['ip']),
                        'success' => $throttler->getThrottledRequestsCount($this->AccountFields['ProviderCode'] . '_success_' . $proxy['ip']),
                    ]
                );
            },
            $proxies
        );

        usort($proxies, function ($a, $b) use ($dataCenters) {
            $byDataCenter = intval(in_array($b['datacenter'], $dataCenters)) - intval(in_array($a['datacenter'],
                    $dataCenters));

            if ($byDataCenter != 0) {
                return $byDataCenter;
            } else {
                return $a['rpm'] - $b['rpm'];
            }
        });

        $successful = array_filter($proxies, function (array $proxy) { return $proxy['success'] > 0; });

        // insert successful proxies after first two records
        // we want to check two new proxy, then hit known successful ones
        // to prevent successul proxies overload
        if (count($successful) > 0 && count($proxies) > (count($successful) + 2)) {
            $proxies = array_filter($proxies, function (array $proxy) { return $proxy['success'] === 0; });
            array_splice($proxies, 2, 0, $successful);
        }

        if (!empty($dataCenters)) {
            $this->http->Log("sorted dop proxy list: " . json_encode($proxies) . ", datacenters: " . json_encode($dataCenters));
        }

        $proxies = array_map(function (array $proxy) use ($withDataCenter) {
            if ($withDataCenter) {
                return $proxy['ip'] . ':' . $proxy['port'] . ':' . $proxy['datacenter'];
            }

            return $proxy['ip'] . ':' . $proxy['port'];
        }, $proxies);

        return $proxies;
    }

    protected function getProxyFromList(array $proxies, int $ttl = 600): ?ProxyListResult
    {
        if (empty($proxies)) {
            return null;
        }

        $throttler = new Throttler($this->getProxyMemcached(), 60, ceil($ttl / 60), 1);
        $prefix = "proxy_score_" . $this->AccountFields["ProviderCode"] . "_";

        $this->logger->info("selecting proxy from list, prefix: " . $prefix);

        $bestProxy = null;
        $bestScore = null;
        $scores = [];

        foreach ($proxies as $proxy) {
            $key = $prefix . $proxy;
            $score = $throttler->getThrottledRequestsCount($key);
            $scores[$proxy] = $score;
            $this->logger->info("$proxy score: $score");

            if ($bestProxy === null || $score > $bestScore) {
                $this->logger->info("$proxy selected as best");
                $bestProxy = $proxy;
                $bestScore = $score;
            }
        }

        $matches = [];

        foreach ($proxies as $proxy) {
            if ($scores[$proxy] === $bestScore) {
                $matches[] = $proxy;
            }
        }
        $bestProxy = $matches[array_rand($matches)];
        $this->logger->info("$proxy selected from " . count($matches) . " proxies with equal rating");

        $this->logger->info("using $proxy from list, current score: " . $throttler->getThrottledRequestsCount($key));

        return new ProxyListResult($throttler, $key, $bestProxy, $this->logger);
    }

    protected function getRecaptchaProxies(): array
    {
        // dns itp.awardwallet.com in the past
        return
            $this->getBandwagonhostProxies() // bandwagonhost (old it7)
            + $this->getVultrProxies(); // vultr
    }

    /**
     * several servers for providers with ReCaptcha v.2 (US)
     * vultr.
     */
    protected function getVultrProxies()
    {
        return [
            '149.28.67.44:3128',
            '45.76.16.29:3128',
            '45.77.221.218:3128',
            '144.202.74.104:3128',
        ];
    }

    /**
     * several servers for providers with ReCaptcha v.2 (US)
     * bandwagonhost (old it7).
     *
     * check proxy status
     * https://bwhstatus.com/
     */
    protected function getBandwagonhostProxies()
    {
        return [
            "104.160.36.49:3128",
            "144.34.228.225:3128",
            "144.34.245.185:3128",
            "23.105.194.147:3128",
        ];
    }

    /**
     * This method will try to load specified url through all of our proxies, and return first successful proxy,
     * which returns response with not empty body, and http code < 400.
     *
     * @return array - ['1.2.3.4:3128', '4.5.6.7:3128', ..] - array of live proxies
     **/
    protected function findLiveProxies(array $proxies, string $url, FindProxyOptions $options = null): array
    {
        if (count($proxies) === 0) {
            return [];
        }

        $this->logger->info("findLiveProxy within list of " . count($proxies) . ", url: $url");

        if ($options === null) {
            $options = new FindProxyOptions();
        }

        $handlers = [];
        $multiHandler = curl_multi_init();

        $result = [];

        $headers = [];

        foreach ($this->http->getDefaultHeaders() as $key => $value) {
            $headers[] = $key . ": " . $value;
        }

        foreach ($proxies as $proxy) {
            if (empty($proxy)) {
                continue;
            }

            $handler = curl_init($url);
            curl_setopt($handler, CURLOPT_CONNECTTIMEOUT, $options->timeout);
            curl_setopt($handler, CURLOPT_TIMEOUT, $options->timeout);
            curl_setopt($handler, CURLOPT_HEADER, false);
            curl_setopt($handler, CURLOPT_FAILONERROR, true);
            curl_setopt($handler, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
            curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handler, CURLOPT_PROXY, str_replace("http:", "", $proxy));
            curl_setopt($handler, CURLOPT_HTTPHEADER, $headers);
            curl_multi_add_handle($multiHandler, $handler);
            $handlers[] = $handler;
        }

        $running = 0;

        do {
            $status = curl_multi_exec($multiHandler, $running);

            if ($running) {
                curl_multi_select($multiHandler);
            }
        } while ($running > 0 && $status == CURLM_OK);

        foreach ($handlers as $index => $handler) {
            $content = curl_multi_getcontent($handler);
            $code = curl_getinfo($handler, CURLINFO_HTTP_CODE);
            $curlErrno = curl_errno($handler);

            $valid = $content and !$curlErrno;

            if ($options->isValid !== null) {
                $valid = $valid && call_user_func($options->isValid, $content, $code, $curlErrno);
            } else {
                $valid = $valid && $code >= 200 && $code < 400;
            }

            if ($valid) {
                $proxy = str_replace("http:", "", $proxies[$index]);
                $result[] = $proxy;
            }
        }

        foreach ($handlers as $h) {
            curl_multi_remove_handle($multiHandler, $h);
        }

        curl_multi_close($multiHandler);

        foreach ($handlers as $h) {
            curl_close($h);
        }

        $this->logger->info("found " . count($result) . " live proxies");

        return $result;
    }

    protected function markProxyAsInvalid(): void
    {
        $address = $this->getCurrentProxyAddress();

        if (!empty($address)) {
            StatLogger::getInstance()->info("markProxyAsInvalid", ["proxy" => $address, "provider" => $this->AccountFields['ProviderCode']]);
            $this->getThrottler($this->getProxyMemcached())->getDelay($this->AccountFields['ProviderCode'] . '_' . $address, false, 500);
            $this->getThrottler($this->getProxyMemcached())->clear($this->AccountFields['ProviderCode'] . '_success_' . $address);
        }
    }

    protected function markProxySuccessful(): void
    {
        $address = $this->getCurrentProxyAddress();

        if (!empty($address)) {
            StatLogger::getInstance()->info("markProxySuccessful", ["proxy" => $address, "provider" => $this->AccountFields['ProviderCode']]);
            $this->getThrottler($this->getProxyMemcached())->getDelay($this->AccountFields['ProviderCode'] . '_success_' . $address);
            $this->getThrottler($this->getProxyMemcached())->clear($this->AccountFields['ProviderCode'] . '_' . $address);
        }
    }

    protected function isProxyInvalid(string $address): bool
    {
        return $this->getThrottler($this->getProxyMemcached())->getDelay($this->AccountFields['ProviderCode'] . '_' . $address, true) > 0;
    }

    /**
     * server in Australia (Amazon).
     */
    protected function proxyAustralia()
    {
        return $this->proxy('Australia', '13.211.226.68:3128');
    }

    /**
     * DO server with Static IP (US, New York).
     */
    protected function proxyStaticIpDOP()
    {
        return $this->proxy('Static IP DOP', 'us.dop.awardwallet.com:3128');
    }

    /**
     * several servers for providers with ReCaptcha v.2 (US).
     *
     * https://www.vultr.com/
     */
    protected function proxyReCaptcha()
    {
        $ips = $this->getRecaptchaProxies();
        shuffle($ips);

        try {
            $liveProxyIp = $this->services->get(ProxyChecker::class)->getLiveProxy($ips);
        } catch (\EngineError $e) {
            $this->logger->error($e->getMessage());
            $liveProxyIp = null;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            if (ConfigValue(CONFIG_SITE_STATE) === SITE_STATE_PRODUCTION) {
                throw $e;
            }

            $liveProxyIp = $ips[array_rand($ips)];
        }

        return $this->proxy('ReCaptcha', $liveProxyIp);
    }

    /**
     * several servers for providers with ReCaptcha v.2 (US)
     * bandwagonhost (old it7).
     */
    protected function proxyReCaptchaIt7()
    {
        $ips = $this->getBandwagonhostProxies();

        return $this->proxy('proxyReCaptchaIt7', $ips[array_rand($ips)]);
    }

    protected function proxyReCaptchaVultr()
    {
        $ips = $this->getVultrProxies();

        return $this->proxy('proxyReCaptchaVultr', $ips[array_rand($ips)]);
    }

    /**
     * 1 servers in UK (London).
     *
     * https://www.vultr.com/
     */
    protected function proxyUK()
    {
        return $this->proxy('UK', '45.32.181.213:3128');
    }

    /*
     * White list of our servers (carlson)
     */
    protected function proxyWhite()
    {
        return $this->proxy('WhiteProxy', 'whiteproxy.infra.awardwallet.com:3128');
    }

    /*
     * Use this proxy only for testing purchases on local machine (Canada, Toronto)
     */
    protected function proxyPurchase()
    {
        if (ConfigValue(CONFIG_SITE_STATE) === SITE_STATE_PRODUCTION) {
            $this->http->Log(">>> You shouldn't use proxyPurchase in production <<<", LOG_LEVEL_ERROR);

            return null;
        } else {
            return $this->proxy('Purchase', 'purchase.us.dop.awardwallet.com:3128');
        }
    }

    /**
     * @param string|callable $targeting
     *
     * @throws Exception
     *
     * Ps from all around the world
     *
     * @see https://brightdata.com/locations
     */
    protected function setProxyBrightData($newSession = null, $zone = "static", $targeting = "us", $useCache = false)
    {
        $this->http->Log("using luminati proxy");
        $allowedZones = [
            "static", // us, gb, fr, de, au, il, kr, fi, es
            "us_residential",
            "rotating_residential",
            "dc_ips_ru",
            "shared_data_center",
            // for reward availability
            Settings::RA_ZONE_STATIC,
            Settings::RA_ZONE_RESIDENTIAL,
        ];

        if (!in_array($zone, $allowedZones)) {
            throw new Exception("Invalid BrightData zone: $zone, allowed only: " . implode(', ', $allowedZones));
        }

        $selector = "";

        if ($zone !== "static" || is_string($targeting)) {
            if (is_string($targeting)) {
                $selector .= "-country-{$targeting}";
            }

            if ($newSession === null) {
                $newSession = $this->attempt > 0;
            }

            if ($newSession) {
                $sessionId = uniqid();
            } else {
                if (!empty($this->State["illuminati-session"])) {
                    $sessionId = $this->State["illuminati-session"];
                } else {
                    $accountId = ArrayVal($this->AccountFields, 'RequestAccountID', ArrayVal($this->AccountFields, 'AccountID'));

                    if (!empty($accountId)) {
                        $sessionId = sha1(ArrayVal($this->AccountFields, 'Partner') . $accountId);
                    } else {
                        // rewards availability, start new session
                        $sessionId = uniqid();
                    }
                }
            }

            $this->State["illuminati-session"] = $sessionId;
            $selector .= "-session-$sessionId";
        } else {
            if ($this->attempt > 0) {
                unset($this->State['illuminati-ip']);
            }

            if (isset($this->State['illuminati-ip']) && $this->isLiveBrightDataIp($this->getHttpDriver(), $zone, $this->State['illuminati-ip'])) {
                $ip = $this->State['illuminati-ip'];
                $this->logger->info("restored BrightData ip from state: {$ip}");
            } else {
                $ip = $this->selectBrightDataIpBy($targeting);
            }

            if ($ip !== null) {
                $selector .= "-ip-{$ip}";
                $this->State['illuminati-ip'] = $ip;
            }
        }

        $lpmHost = "lpm.awardwallet.com";

        if (defined('LPM_HOST')) {
            $lpmHost = LPM_HOST;
        }

        if ($useCache) {
            if ($zone === "static") {
                $address = "{$lpmHost}:24002";
            } else {
                $address = "{$lpmHost}:24000";
            }
        } else {
            $address = "zproxy.luminati.io:22225";
        }
        $this->http->SetProxy($address, true, 'luminati' . '-' . $zone, $targeting);

        $login = "lum-customer-" . ILLUMINATI_CUSTOMER . "-zone-" . $zone . "-dns-remote" . $selector;
        $pass = ILLUMINATI_PASS;
        $this->http->setProxyAuth($login, $pass);

        $siteURL = "http://lumtest.com/myip.json";
        $browser = new HttpBrowser("none", new CurlDriver());
        $browser->RetryCount = 0;
        $browser->SetProxy($address);
        $browser->setProxyAuth($login, $pass);
        $response = $browser->GetURL($siteURL, [], 20);

        if (!$response) {
            $this->logger->warning("failed to get $siteURL, response code: {$browser->Response['code']}");
        } else {
            $info = $browser->JsonLog(null, 0, true);

            if (is_array($info) && isset($info['ip'])) {
                $this->State['illuminati-ip'] = $info['ip'];
                $this->State['proxy-ip'] = $info['ip'];
                $this->http->Log("proxy-ip: " . $info['ip']);
            }
        }
    }

    protected function getCaptchaProxy()
    {
        $proxyExtAddress = "18.232.27.10";
        $proxyIntAddress = "192.168.2.146";
//        $proxyExtAddress = "52.15.72.79";
//        $proxyIntAddress = "host.docker.internal";

        /*
        $proxyLogin = $this->http->getProxyLogin();
        $proxyPort = rand(3128, 3200);

        if (strpos($proxyLogin, "lum-customer-" . ILLUMINATI_CUSTOMER) === 0) {
            $login = str_replace("lum-customer-" . ILLUMINATI_CUSTOMER, "antigate-l1", $proxyLogin);
            $this->http->Log("set proxy auth: " . $login . ":" . str_repeat('*', strlen(ANTIGATE_PROXY_PASSWORD)) . ", at {$proxyExtAddress}:{$proxyPort}");

            return [
                "proxyLogin"    => $login,
                "proxyPassword" => ANTIGATE_PROXY_PASSWORD,
                "proxyType"     => "http",
                "proxyAddress"  => $proxyExtAddress,
                "proxyPort"     => $proxyPort, // we simulate 100 proxies, to force antigate calc success rate for each proxy
            ];
        } else*/
        if (!empty($this->http->getProxyAddress())) {
            $mc = new Memcached();
            $mc->addServer($proxyIntAddress, 11211);
            $key = bin2hex(openssl_random_pseudo_bytes(20));
            $proxyParams = $this->http->getProxyParams();
            $mc->set($key, "http://" . $proxyParams["proxyAddress"] . ":" . $proxyParams["proxyPort"], 3600);
            $mc->set("proxy_params_" . $key, json_encode($proxyParams), 3600);
            $this->http->Log("set proxy auth: antigate-ip-" . $key);

            return [
                "proxyLogin"       => "antigate-ip-" . $key,
                "proxyPassword"    => ANTIGATE_PROXY_PASSWORD,
                "proxyType"        => "http",
                "proxyAddress"     => $proxyExtAddress,
                "proxyPort"        => rand(3201, 3228),
            ];
        } else {
            $this->http->Log("Unsupported captcha proxy, only BrightData is supported", LOG_LEVEL_USER);
        }
//            throw new \CheckException("Unsupported captcha proxy, only BrightData is supported");
    }

    /**
     * @param null   $newSession
     * @param string $country
     * @param null   $siteURL
     * @param array  $headers
     *
     * @return array - ["proxyIp" => "1.2.3.4", "browser" => HttpBrowser with configured proxy]
     *
     * @throws Exception
     *
     * @see https://l.netnut.io/countries
     * @see https://redmine.awardwallet.com/issues/16294#note-15
     */
    protected function setProxyNetNut($newSession = null, $country = "us", $siteURL = null, $headers = []): ?array
    {
        $ipInfoURL = "https://api.netnut.io/myIP.aspx";
//        $ipInfoURL = "http://ip-api.com/json/";

        if ($siteURL === null) {
            $siteURL = $ipInfoURL;
        }

//        if (
//            !property_exists($this, 'isRewardAvailability')
//            || (isset($this->isRewardAvailability) && !$this->isRewardAvailability)
//        ) {
//            $this->logger->info("set proxy Go Proxies");
//            $this->setProxyGoProxies($newSession, $country);
//
//            return null;
//        }

        $browser = new HttpBrowser("none", new CurlDriver());
        $browser->RetryCount = 0;
//        $domain = 'gw-g.ntnt.io'; // TODO: get back gw.ntnt.io, now netnut has multiple unresponsive proxies behind this dns name, May 2022
        if (false && property_exists($this, 'isRewardAvailability')
            && (isset($this->isRewardAvailability) && $this->isRewardAvailability)
        ) {
            $defaultDomain = 'gw.ntnt.io';
            $regions = [
                // If the requests are for Europe
                //                'gw-eu.am.ntnt.io' => [
                'gw-eu.ntnt.io' => [
                    'uk', 'no', 'fi', 'ee', 'dk', 'cz', 'fr', 'gr', 'it', 'es', 'de', 'pt', 'be', 'bl',
                ],
                // If the requests are for Asia
                'gw-as.ntnt.io' => [
                    'au', 'jp', 'cn', 'kr', 'hk', 'id',
                ],
                // If the requests are for North America
                'gw-am.ntnt.io' => [
                    'us', 'ca',
                ],
            ];
            $proxyURL = null;

            foreach ($regions as $url => $list) {
                if (in_array($country, $list)) {
                    $proxyURL = $url;

                    break;
                }
            }

            if (!isset($proxyURL)) {
                $domain = $defaultDomain;
            } else {
                $domain = $proxyURL;
            }
        } else {
            $domain = 'gw.ntnt.io';
        }

        $browser->SetProxy("{$domain}:5959");

        $n = 0;

        do {
            $stickyId = random_int(1, 99999999);

            if (
                !$newSession
                && !empty($this->State["netnut-sticky-id"])
                && $n === 0
                && $this->attempt === 0
            ) {
                $stickyId = $this->State["netnut-sticky-id"];
                $this->logger->info("restored netnut sticky id from state: $stickyId");
            }

            $userName = NETNUT_USERNAME . "-res-" . $country . "-sid-" . $stickyId;
            $this->logger->info("using netnut proxy: {$userName}");
            $browser->setProxyAuth($userName, NETNUT_PASSWORD);

            $response = $browser->GetURL($siteURL, $headers, 20);

            $context = [
                'country'      => $country,
                'sid'          => $stickyId,
                'domain'       => $domain,
                'siteUrl'      => $siteURL,
                'responseCode' => $browser->Response['code'],
                'success'      => null,
                'numTry'       => $n,
            ];

            if (!$response) {
                $this->logger->warning("failed to get $siteURL, response code: {$browser->Response['code']}");
                $context['success'] = false;
            } else {
                $context['success'] = true;
            }
            StatLogger::getInstance()->info("NetNut statistic", $context);

            $n++;
        } while ($n < 7 && !$response);

        if ($response) {
            $context['success'] = true;
            StatLogger::getInstance()->info("NetNut-live statistic", $context);

            $this->logger->info("live proxy found, response code: {$browser->Response['code']}");
            $this->State["netnut-sticky-id"] = $stickyId;

            $this->http->SetProxy("{$domain}:5959", true, 'netnut', $country);
            $this->http->setProxyAuth($userName, NETNUT_PASSWORD);

            if ($siteURL !== $ipInfoURL) {
                $browser->GetURL($ipInfoURL, [], 10);
            }
            $proxyIp = trim($browser->Response['body']);
            $this->logger->info("proxy ip: " . $proxyIp);
            $this->State["proxy-ip"] = $proxyIp;

            return ["proxyIp" => $proxyIp, "browser" => $browser];
        }
        $context['success'] = false;
        StatLogger::getInstance()->info("NetNut-live statistic", $context);

        $this->logger->warning("no live NetNut proxy found");

        $this->logger->info("set proxy BrightData static");
        $this->setProxyBrightData(null, 'static', $country);

        return null;
    }

    /**
     * @param null   $newSession
     * @param string $country
     * @param string $city
     * @param string $state
     *
     * @throws Exception
     *
     * @see https://developers.oxylabs.io/residential-proxies/#quick-start
     * @see https://developers.oxylabs.io/resources/us_states.txt
     */
    protected function setProxyOxylabs($newSession = null, $country = 'us', $city = null, $state = null)
    {
        if (!$this->isRewardAvailability) {
            $this->logger->emergency("no have Oxylabs.");
            $this->logger->info("set proxy BrightData static");
            $this->setProxyBrightData(null, Settings::RA_ZONE_STATIC, $country);

            return;
        }

        $this->http->Log("using oxylabs proxy");

        $selector = "";

        $selector .= "-cc-" . strtoupper($country);

        if (isset($city)) {
            $selector .= "-city-" . $city;
        }

        if ($country === 'us' && !empty($state)) {
            if (strpos($state, 'us_') === 0) {
                $selector .= "-st-" . $state;
            } else {
                $this->http->Log("check settings value 'state'. not used", LOG_LEVEL_ERROR);
            }
        }

        if ($newSession === null) {
            $newSession = $this->attempt > 0;
        }

        if ($newSession) {
            $sessionId = uniqid();
        } else {
            if (!empty($this->State["oxylabs-session"])) {
                $sessionId = $this->State["oxylabs-session"];
            } else {
                $sessionId = sha1(ArrayVal($this->AccountFields, 'Partner') . ArrayVal($this->AccountFields,
                        'RequestAccountID', ArrayVal($this->AccountFields, 'AccountID')));
            }
        }

        $this->State["oxylabs-session"] = $sessionId;
        $selector .= "-sessid-" . $sessionId;

        $address = "pr.oxylabs.io:7777";
        $login = "customer-" . OXYLABS_USERNAME . $selector;
        $pass = OXYLABS_PASSWORD;
        $this->http->SetProxy($address, true, 'oxylabs', $country);
        $this->http->setProxyAuth($login, $pass);

        $browser = new HttpBrowser("none", new CurlDriver());
        $browser->RetryCount = 0;
        $browser->SetProxy($address);
        $browser->setProxyAuth($login, $pass);

        $response = $browser->GetURL('https://ipinfo.io/ip', [], 20);

        if (!$response) {
            $this->logger->warning("failed to get https://ipinfo.io/ip, response code: {$browser->Response['code']}");

            throw new \CheckException("Unsupported oxylabs proxy");
            $this->logger->info("set proxy BrightData static");
            $this->setProxyBrightData(null, 'static', $country);

            return null;
        }
        $this->logger->info("live proxy found, response code: {$browser->Response['code']}");
        $proxyIp = trim($browser->Response['body']);
        $this->logger->info("proxy ip: " . $proxyIp);
        $this->State["proxy-ip"] = $proxyIp;
    }

    /**
     * @param null   $newSession
     * @param string $country
     * @param string $city
     * @param string $state
     * @param null   $siteURL
     * @param array  $headers
     *
     * @throws Exception
     *
     * @see refs #22074
     */
    protected function setProxyGoProxies($newSession = null, $country = 'us', $city = null, $state = null, $siteURL = null, $headers = [])
    {
        if ($this->isRewardAvailability) {
            $this->logger->emergency("RA no have GoProxies.");
            $this->logger->info("set proxy BrightData static");
            $this->setProxyBrightData($newSession, Settings::RA_ZONE_STATIC, $country);

            return;
        }

//        $this->setProxyNetNut($newSession, $country, $siteURL, $headers);
//
//        return;

        $ipInfoURL = 'https://ip.goproxies.com';
        $siteURL = $siteURL ?? $ipInfoURL;

        // get alpha-2 code for country
        // https://en.wikipedia.org/wiki/List_of_ISO_3166_country_codes
        $regions = [
            // Europe countries
            'proxy-europe.goproxies.com' => [
                'gb', 'no', 'fi', 'ee', 'dk', 'cz', 'fr', 'gr', 'it', 'es', 'de', 'pt', 'be', 'bl',
            ],
            // Asia and Oceania countries
            'proxy-asia.goproxies.com' => [
                'au', 'jp', 'cn', 'kr', 'hk', 'id',
            ],
            // North America and South America countries
            'proxy-america.goproxies.com' => [
                'us', 'ca', 'br', 'cl', 'mx',
            ],
        ];
        $proxyURL = null;

        foreach ($regions as $url => $list) {
            if (in_array($country, $list)) {
                $proxyURL = $url;

                break;
            }
        }

        if ($proxyURL === null) {
            $this->http->Log("can't determine the region to use the GoProxies proxy. Check and expand data with regions");
            /*
            $this->logger->info("set proxy BrightData static");
            $this->setProxyBrightData(null, 'static', $country);
            */

            return null;
        }

        // NB: if someday we need https => see manual, proxy has port 10001 for that refs #22074
        $proxyPort = '10000';

        $this->http->Log("using GoProxies proxy");

        $selector = "";

        if (isset($city)) {
            // FE: us_los_angeles
            $list = explode(' ', strtolower(str_replace('-', ' ', $city)));
            array_unshift($list, $country);
            $selector .= "-city-" . implode('_', $list);
        } elseif (isset($state) && $country === 'us') {
            // FE: us_california
            if (strpos($state, 'us_') === 0) {
                $selector .= "-state-" . $state;
            } else {
                $this->http->Log("check settings value 'state'. not used", LOG_LEVEL_ERROR);
            }
        }

        if (empty($selector)) {
            $selector .= "-country-" . strtolower($country);
        }

        $address = $proxyURL . ":" . $proxyPort;

        $browser = new HttpBrowser("none", new CurlDriver());
        $browser->RetryCount = 0;
        $browser->SetProxy($address);

        $selectorMain = $selector;
        $n = 0;

        do {
            $sessionId = random_int(1, 99999999); // uniqid();

            if (
                !$newSession
                && !empty($this->State["goproxies-session"])
                && $n === 0
                && $this->attempt === 0
            ) {
                $sessionId = $this->State["goproxies-session"];
                $this->logger->info("restored goproxies sessionId from state: $sessionId");
            }

            $selector = $selectorMain . "-sessionid-" . $sessionId;

            $login = "customer-" . GOPROXIES_USERNAME . $selector;

            $browser->setProxyAuth($login, GOPROXIES_PASSWORD);

            $response = $browser->GetURL($siteURL, $headers, 20);

            $context = [
                'country'      => $country,
                'sid'          => $sessionId,
                'domain'       => $address,
                'siteUrl'      => $siteURL,
                'responseCode' => $browser->Response['code'],
                'success'      => null,
                'numTry'       => $n,
            ];

            if (!$response) {
                $this->logger->warning("failed to get $siteURL, response code: {$browser->Response['code']}");
                $context['success'] = false;
            } else {
                $context['success'] = true;
            }
            StatLogger::getInstance()->info("GoProxies statistic", $context);

            $n++;
        } while ($n < 7 && !$response);

        if (!$response) {
            $context['success'] = false;
            StatLogger::getInstance()->info("GoProxies-live statistic", $context);
            $this->logger->warning("no live GoProxies proxy found");

            /*
            $this->logger->info("set proxy BrightData static");
            $this->setProxyBrightData(null, 'static', $country);
            */

            return null;
        }

        $context['success'] = true;
        StatLogger::getInstance()->info("GoProxies-live statistic", $context);

        $this->logger->info("live proxy found, response code: {$browser->Response['code']}");
        $this->State["goproxies-session"] = $sessionId;

        $this->http->SetProxy($address, true, 'goproxies', $country);
        $this->http->setProxyAuth($login, GOPROXIES_PASSWORD);

        if ($siteURL !== $ipInfoURL) {
            $browser->GetURL($ipInfoURL, [], 10);
        }
        $proxyIp = trim($browser->Response['body']);
        $this->logger->info("proxy ip: " . $proxyIp);
        $this->State["proxy-ip"] = $proxyIp;
    }

    /**
     * @param string $country
     * @param null   $siteURL
     * @param array  $headers
     *
     * @throws Exception
     *
     * @see refs #22232
     */
    protected function setProxyGeoSurf($country = 'us', $siteURL = null, $headers = [])
    {
        if ($this->isRewardAvailability) {
            $this->logger->emergency("RA no have GeoSurf.");
            $this->logger->info("set proxy BrightData static");
            $this->setProxyBrightData(null, Settings::RA_ZONE_STATIC, $country);

            return;
        }

        if ($country === 'uk') {
            $country = 'gb';
        }

        if (!in_array($country, $this->listCountriesGeoSurf)) {
            throw new \CheckException('check supported countries by GeoSurf', ACCOUNT_ENGINE_ERROR);
        }

        $ipInfoURL = 'https://geo.geosurf.io';
        $siteURL = $siteURL ?? $ipInfoURL;

        $id = GEOSURF_ID;
        $pwd = GEOSURF_PASSWORD;

        $listGeoSerf = json_decode($this->jsonFromGeoSurf, true);
        // if sticky use ports
        /*if (isset($listGeoSerf[$country]['port2'])) {
            $port = random_int($listGeoSerf[$country]['port1'], $listGeoSerf[$country]['port2']);
        } else {
            $port = $listGeoSerf[$country]['port1'];
        }*/
        $port = 8000;
        $domain = $listGeoSerf[$country]['domain'];
        $proxyURL = $domain . ':' . $port;

        $this->http->Log("using GeoSurf proxy");

        $browser = new HttpBrowser("none", new CurlDriver());
        $browser->RetryCount = 0;
        $browser->SetProxy($proxyURL);

        $n = 0;

        do {
            $sessionId = str_pad((string) random_int(1, 999999), 6, "0", STR_PAD_LEFT); // uniqid();

            $login = sprintf('%s+%s+%s-%s', $id, $country, $id, $sessionId);
            $browser->setProxyAuth($login, $pwd);

            $response = $browser->GetURL($siteURL, $headers, 20);

            $context = [
                'country'      => $country,
                'sid'          => $sessionId,
                'domain'       => $proxyURL,
                'siteUrl'      => $siteURL,
                'responseCode' => $browser->Response['code'],
                'success'      => null,
                'numTry'       => $n,
            ];

            if (!$response) {
                if ($browser->Response['code'] == 503 && strpos($browser->Response['body'],
                        'No server is available to handle this request.') !== false) {
                    $this->logger->warning("failed to get $siteURL, response code: {$browser->Response['code']}");
                    $this->logger->debug($browser->Response['body'], ['pre'=>true]);
                    $context['success'] = false;
                    StatLogger::getInstance()->info("GeoSurf-live statistic", $context);

                    throw new \CheckException('check GeoSurf url for connection', ACCOUNT_ENGINE_ERROR);
                }
                $this->logger->warning("failed to get $siteURL, response code: {$browser->Response['code']}");
                $context['success'] = false;
            } else {
                $context['success'] = true;
            }
            StatLogger::getInstance()->info("GeoSurf statistic", $context);

            $n++;
        } while ($n < 7 && !$response);

        if (!$response) {
            $context['success'] = false;
            StatLogger::getInstance()->info("GeoSurf-live statistic", $context);
            $this->logger->warning("no live GeoSurf proxy found");

            throw new \CheckException('no live GeoSurf proxy found', ACCOUNT_ENGINE_ERROR);

            return null;
        }

        $context['success'] = true;
        StatLogger::getInstance()->info("GoProxies-live statistic", $context);

        $this->logger->info("live proxy found, response code: {$browser->Response['code']}");

        $this->http->SetProxy($proxyURL, true, 'geosurf', $country);
        $this->http->setProxyAuth($login, $pwd);

        if ($siteURL !== $ipInfoURL) {
            $browser->GetURL($ipInfoURL, [], 10);
        }
        $proxyIp = $this->http->JsonLog(trim($browser->Response['body']), 0, true);

        if (isset($proxyIp['ip'])) {
            $this->logger->info("proxy ip: " . $proxyIp['ip']);
            $this->State["proxy-ip"] = $proxyIp['ip'];
        }
    }

    protected function findBrightDataProxy(HttpDriverRequest $request, $tries, $timeout, callable $isValid)
    {
        $driver = new CurlDriver();
        $driver->start(null, null);
        curl_setopt($driver->curl, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($driver->curl, CURLOPT_TIMEOUT, $timeout);
        $request->proxyPort = 22225;
        $request->proxyPassword = ILLUMINATI_PASS;
        $result = false;

        if (empty($request->headers['User-Agent'])) {
            $request->headers['User-Agent'] = $this->http->userAgent;
        }

        for ($n = 0; $n < $tries; $n++) {
            $request->proxyLogin = "lum-customer-" . ILLUMINATI_CUSTOMER . "-zone-static-country-us-dns-remote-session-" . uniqid();
            $request->proxyAddress = gethostbyname("zproxy.luminati.io");
            $this->http->Log("looking for live BrightData proxy, try $n, super proxy: " . $request->proxyAddress . ", login: {$request->proxyLogin}");
            $ipRequest = new HttpDriverRequest("http://lumtest.com/myip.json");
            $ipRequest->proxyAddress = $request->proxyAddress;
            $ipRequest->proxyPort = $request->proxyPort;
            $ipRequest->proxyLogin = $request->proxyLogin;
            $ipRequest->proxyPassword = $request->proxyPassword;
            $response = $driver->request($ipRequest);
            $this->http->Log("ip: " . $response->body);
            $ipInfo = json_decode($response->body, true);

            if (empty($ipInfo['ip'])) {
                $this->http->Log("failed to decode ip info");

                continue;
            }
            $ip = $ipInfo['ip'];
            $response = $driver->request($request);

            if (call_user_func($isValid, $response)) {
                $result = true;

                if (property_exists($this, 'proxyAddressOnInit')) {
                    $this->http->SetProxy($request->proxyAddress . ":" . $request->proxyPort, true, 'luminati', 'us');
                } else {
                    $this->http->SetProxy($request->proxyAddress . ":" . $request->proxyPort);
                }
                $this->http->setProxyAuth("lum-customer-" . ILLUMINATI_CUSTOMER . "-zone-static-country-us-ip-" . $ip, ILLUMINATI_PASS);
                $this->http->Log("live proxy found", LOG_LEVEL_USER);

                break;
            }
        }

        if (!$result) {
            $this->http->Log("no live proxy found", LOG_LEVEL_ERROR);
        }

        return $result;
    }

    protected function checkCurrentProxy(int $timeout = 10): bool
    {
        $request = new \HttpDriverRequest('http://lumtest.com/myip.json');
        $request->proxyAddress = $this->http->getProxyAddress();
        $request->proxyPort = $this->http->getProxyPort();
        $request->proxyLogin = $this->http->getProxyLogin();
        $request->proxyPassword = $this->http->getProxyPassword();
        $request->timeout = $timeout;

        $driver = $this->getHttpDriver();

        $response = $driver->request($request);
        $info = @json_decode($response->body, true);

        if (!is_array($info) || !isset($info['ip'])) {
            $this->logger->warning("failed to check proxy {$request->proxyAddress} / {$request->proxyLogin} - " . Strings::cutInMiddle($response->body, 250));

            return false;
        }

        $this->logger->info("successfully tested proxy, exit node: " . json_encode($info));

        return true;
    }

    protected function selectBrightDataIpBy(callable $ipMatcher): ?string
    {
        $cache = $this->getProxyMemcached();
        $driver = $this->getHttpDriver();

        $ips = $this->getBrightDataIpList($driver, $cache, 'static');
        $ips = $this->getIpInfos($driver, $cache, $ips);
        $filtered = array_filter($ips, $ipMatcher);

        $try = 0;
        $this->logger->warning("selecting BrightData ip by your criteria, total ips: " . count($ips) . ", filtered: " . count($filtered));

        while (count($filtered) > 0 && $try < 3) {
            $selected = array_rand($filtered);
            $info = $filtered[$selected];
            unset($filtered[$selected]);

            if ($this->isLiveBrightDataIp($driver, 'static', $info['ip'])) {
                $this->logger->info("selected BrightData ip: " . json_encode($info));

                return $info['ip'];
            }

            $try++;
        }

        $this->logger->warning("failed to select BrightData ip by your criteria");

        return null;
    }

    private function getCurrentProxyAddress(): ?string
    {
        $address = $this->http->getProxyAddress();
        $auth = $this->http->getProxyLogin();

        if (preg_match('#^lum.+ip-(\d+\.\d+\.\d+\.\d+)#ims', $auth, $matches)) {
            StatLogger::getInstance()->info("extracted BrightData ip from auth");
            $address = $matches[1];
        }

        return $address;
    }

    private function getThrottler(Memcached $cache)
    {
        return new Throttler($cache, 6, 10, empty($this->AccountFields['RequestsPerMinute']) ? 1000000 : $this->AccountFields['RequestsPerMinute']);
    }

    private function getProxyMemcached()
    {
        $cache = new Memcached('proxylist_bin_' . MEMCACHED_HOST . getmypid());

        if (count($cache->getServerList()) == 0) {
            $cache->addServer(MEMCACHED_HOST, 11211);
            $cache->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
            $cache->setOption(Memcached::OPT_RECV_TIMEOUT, 500);
            $cache->setOption(Memcached::OPT_SEND_TIMEOUT, 500);
            $cache->setOption(Memcached::OPT_CONNECT_TIMEOUT, 500);
            $cache->setOption(Memcached::OPT_TCP_NODELAY, true);
        }

        return $cache;
    }

    private function proxy($id, $proxy)
    {
        if ($this->http instanceof HttpBrowser) {
            $this->http->Log(">>> set {$id} proxy => {$proxy} <<<");
        }

        return $proxy;
    }

    private function getHttpDriver(): \HttpDriverInterface
    {
        $driver = new CurlDriver();
        $driver->start(null, null);

        return $driver;
    }

    private function isLiveBrightDataIp(\HttpDriverInterface $driver, string $zone, string $ip): bool
    {
        $request = new \HttpDriverRequest('http://lumtest.com/myip.json');
        $request->proxyAddress = 'zproxy.luminati.io:22225';
        $request->proxyLogin = "lum-customer-" . ILLUMINATI_CUSTOMER . "-zone-" . $zone . "-ip-" . $ip;
        $request->proxyPassword = ILLUMINATI_PASS;
        $response = $driver->request($request);
        $info = @json_decode($response->body, true);

        if (!is_array($info) || !isset($info['ip'])) {
            $this->logger->warning("failed luminati ip: {$ip} - " . Strings::cutInMiddle($response->body, 250));

            return false;
        }
        $this->logger->info("successfully tested BrightData ip: " . json_encode($info));

        return true;
    }

    private function getBrightDataIpList(\HttpDriverInterface $driver, Memcached $cache, string $zone): array
    {
        $cacheKey = "lum_ip_list2_" . $zone;
        $ips = $cache->get($cacheKey);

        if (is_array($ips) && count($ips) > 0) {
            return $ips;
        }

        $this->logger->info("fetching BrightData ips for zone $zone");
        $response = $driver->request(new \HttpDriverRequest('https://api.brightdata.com/zone/ips?zone=' . urlencode($zone), 'GET', null,
            ['Authorization' => 'Bearer ' . ILLUMINATI_API_TOKEN], 10));
        $ips = @json_decode($response->body, true);

        if (!is_array($ips) || !isset($ips['ips'])) {
            $ips = ["ips" => []];
        }

        $ips = array_map(function (array $ip) { return $ip['ip']; }, $ips['ips']);

        $cache->set($cacheKey, $ips, 86400);

        return $ips;
    }

    private function getIpInfos(\HttpDriverInterface $driver, Memcached $cache, array $ips): array
    {
        $cacheKey = "px_ip_infos_" . sha1(implode(",", $ips));
        $result = $cache->get($cacheKey);

        if (is_array($result)) {
            return $result;
        }

        $result = array_map(function (string $ip) use ($driver, $cache) {
            return $this->getIpInfo($driver, $cache, $ip);
        }, $ips);

        $cache->set($cacheKey, $result, 86400);

        return $result;
    }

    private function getIpInfo(\HttpDriverInterface $driver, Memcached $cache, string $ip): ?array
    {
        $cacheKey = 'px_ip_info_' . $ip;
        $info = $cache->get($cacheKey);

        if (!is_array($info)) {
            $response = $driver->request(new \HttpDriverRequest('http://ipinfo.io/' . $ip));
            $info = @json_decode($response->body, true);

            if (!is_array($info) || !isset($info['country'])) {
                return null;
            }
            $info = array_map('strtolower', $info);
            $cache->set($cacheKey, $info, 86400 * 7);
        }

        return $info;
    }
}
