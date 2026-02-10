<?php


namespace AwardWallet\Common\Parsing\Solver\Itinerary;


use AwardWallet\Common\CurrencyUtils;
use AwardWallet\Common\Parsing\Solver\Exception;
use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Common\Parsing\Solver\Extra\SolvedCurrency;
use AwardWallet\Common\Parsing\Solver\Helper\DataHelper;
use AwardWallet\Common\Parsing\Solver\Helper\ExtraHelper;
use AwardWallet\Schema\Parser\Common\Itinerary;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class ItinerarySolver {

	protected $defaultContext = ['component' => 'Solver'];

	/** @var ExtraHelper  */
	protected $eh;
	/** @var DataHelper  */
	protected $dh;
	/** @var LoggerInterface */
	protected $logger;

	public function __construct(ExtraHelper $eh, DataHelper $dh)
    {
		$this->eh = $eh;
		$this->dh = $dh;
		$this->logger = new NullLogger();
		$this->defaultContext['class'] = (new \ReflectionClass($this))->getShortName();
	}

	public final function solve(Itinerary $it, Extra $extra)
    {
		$posted = false;
		if ($it->getTravelAgency()) {
			if ($it->getTravelAgency()->getProviderCode() || $it->getTravelAgency()->getProviderKeyword())
				$it->getTravelAgency()->setProviderCode($this->eh->solveProvider($it->getTravelAgency()->getProviderKeyword(), $it->getTravelAgency()->getProviderCode(), $extra)->code);
			else {
				$it->getTravelAgency()->setProviderCode($extra->provider->code);
				$posted = true;
			}
			if ($it->getTravelAgency()->getProviderCode()) {
			    if ($it->getTravelAgency()->getProviderCode() === $extra->provider->code)
			        $posted = true;
			    if (!$extra->data->existsProvider($it->getTravelAgency()->getProviderCode()))
				    $this->eh->solveProvider(null, $it->getTravelAgency()->getProviderCode(), $extra);
				if (count($it->getTravelAgency()->getProviderPhones()) === 0 && $phone = $this->eh->getProviderPhone($it->getTravelAgency()->getProviderCode(), null))
					$it->getTravelAgency()->addProviderPhone($phone);
			}
		}
		if ($it->getProviderCode() || $it->getProviderKeyword())
			$it->setProviderCode($this->eh->solveProvider($it->getProviderKeyword(), $it->getProviderCode(), $extra)->code);
		elseif (!$posted && !empty($extra->provider->code)) {
			$it->setProviderCode($extra->provider->code);
		}
		if ($it->getProviderCode()) {
		    if (!$extra->data->existsProvider($it->getProviderCode()))
			    $this->eh->solveProvider(null, $it->getProviderCode(), $extra);
			if (count($it->getProviderPhones()) === 0 && $phone = $this->eh->getProviderPhone($it->getProviderCode(), null))
				$it->addProviderPhone($phone);
		}

        if ($price = $it->getPrice()) {
            $code = $set = null;
            if ($price->getCurrencyCode())
                $code = new SolvedCurrency($price->getCurrencyCode(), true);
            elseif ($price->getCurrencySign()) {
                $code = $this->eh->solveCurrency($price->getCurrencySign());
                $set = true;
            }
            if (isset($code)) {
                foreach([$price->getTotal(), $price->getCost()] as $val)
                    if ($val && ($est = CurrencyUtils::estimate($val, $code->code)) && $est > $this->eh->getMaxPriceEstimate($it->getType()))
                        if ($code->unique)
                            throw Exception::suspiciousValue('price', $val);
                        else
                            $set = false;
                if ($set)
                    $price->setCurrencyCode($code->code);
            }
        }
        if (!empty($it->getStatus()) && $this->isStatusCancelled($it->getStatus()) && null === $it->getCancelled()) {
            $it->setCancelled(true);
        }
		$this->solveItinerary($it, $extra);
	}

	protected abstract function solveItinerary(Itinerary $it, Extra $extra);

	protected function warning($message, $context = [])
    {
        $this->logger->warning($message, array_merge($this->defaultContext, $context));
    }

    private function isStatusCancelled(string $status): bool
    {
        return in_array(strtolower($status), [
            'afbestilt',
            'annul',
            'annulering',
            'annullata',
            'annulliert',
            'annulé',
            'annulée',
            'anulada',
            'anulado',
            'anulată',
            'anulowana',
            'anulowane',
            'atcelts',
            'avbestillingen',
            'avbokad',
            'avbokades',
            'avbokat',
            'avbokats',
            'avbooket',
            'cancelaciones',
            'cancelada',
            'cancelado',
            'cancelar',
            'cancellata',
            'cancellato',
            'cancelled',
            'canceled',
            'canceló',
            'geannuleerd',
            'kansellert',
            'storniert',
            'ακυρωθεί',
        ]);
    }

}