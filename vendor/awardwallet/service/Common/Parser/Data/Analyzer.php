<?php


namespace AwardWallet\Common\Parser\Data;


class Analyzer
{
    public function getArraySchema($data)
    {
        $schema = ['itineraries' => []];
        if (!$data['itineraries'])
            return $schema;
        foreach($data['itineraries'] as $i => $it) {
            $ssit = [];
            $this->keyPair($it, 'ticketNumbers', $ssit, 'warn', !isset($it['areTicketsMasked']) ? 'warn' : null);
            $this->keyPair($it, 'accountNumbers', $ssit, 'miss', !isset($it['areAccountMasked']) ? 'warn' : null);
            $this->keyPair($it, 'confirmationNumbers', $ssit, 'warn', 'miss');
            $this->keyPair($it, 'travellers', $ssit, 'warn', empty($it['areNamesFull']) ? 'warn' : null);
            if (!empty($it['confirmationNumbers'] && count($it['confirmationNumbers']) > 1 && empty($it['primaryConfirmationKey'])))
                $ssit['primaryConfirmationKey'] = 'warn';
            $this->arr($it, [
                'reservationDate',
                'status',
                'cancellation',
                'earnedAwards',
            ], $ssit, 'miss');
            foreach(['price', 'travelAgency'] as $k)
                if (empty($it[$k]) && empty($data[$k]))
                    $ssit[$k] = 'warn';
            if (!empty($it['price'])) {
                $ssit['price'] = [];
                $this->arr($it['price'], ['fees'], $ssit['price'], 'miss');
                $this->arr($it['price'], ['total', 'cost', 'currencyCode', 'spentAwards'], $ssit['price'], 'warn');
            }
            if (!empty($it['cancelled']))
                $this->cancelled($it, $data, $ssit);
            else
                switch($it['type']) {
                    case 'flight':
                        $ssit['segments'] = [];
                        $this->flight($it, $ssit);
                        break;
                    case 'hotel':
                        $this->hotel($it, $ssit);
                        break;
                    case 'rental':
                        $this->rental($it, $ssit);
                        break;
                    case 'event':
                        $this->event($it, $ssit);
                        break;
                    case 'transfer':
                        $ssit['segments'] = [];
                        $this->transfer($it, $ssit);
                        break;
                    case 'train':
                        $ssit['segments'] = [];
                        $this->train($it, $ssit);
                        break;
                    case 'bus':
                        $ssit['segments'] = [];
                        $this->bus($it, $ssit);
                        break;
                }
            $schema['itineraries'][$i] = $ssit;
        }
        return $schema;
    }

    private function cancelled($it, $data, &$schema)
    {
        if (empty($it['confirmationNumbers'])
        && (empty($it['travelAgency']) || empty($it['travelAgency']['confirmationNumbers']))
        && (empty($data['travelAgency']) || empty($data['travelAgency']['confirmationNumbers'])))
            $schema['confirmationNumbers'] = 'err';
    }

    private function flight($it, &$schema)
    {
        if (!isset($it['segments']))
            $it['segments'] = [];
        foreach($it['segments'] as $j => $seg) {
            $ss = [];
            $this->segment($seg, $ss, true);
            if (empty($seg['confirmation']) && empty($it['confirmationNumbers']))
                $ss['confirmation'] = 'warn';
            $schema['segments'][$j] = $ss;
        }
    }

    private function hotel($it, &$schema)
    {
        $this->arr($it, [
            'hotelName',
            'address',
            'checkInDate',
            'checkOutDate',
        ], $schema, 'err');
        $check = [
            'phone',
            'guestCount',
            'kidsCount',
            'roomsCount',
        ];
        if (empty($it['nonRefundable']))
            $check[] = 'deadline';
        if (empty($it['deadline']))
            $check[] = 'nonRefundable';
        $this->arr($it, $check, $schema, 'miss');
        $this->arr($it, [
            'cancellation',
            'deadline',
            'nonRefundable',
        ], $schema, 'warn');
    }

    private function rental($it, &$schema)
    {
        $this->arr($it, [
            'pickUpLocation',
            'pickUpDateTime',
            'dropOffLocation',
            'dropOffDateTime',
        ], $schema, 'err');
        $this->arr($it, [
            'pickUpPhone',
            'pickUpHours',
            'dropOffPhone',
            'dropOffHours',
            'carImageUrl',
        ], $schema, 'miss');
        $this->arr($it, [
            'carType',
            'carModel',
        ], $schema, 'warn');
    }

    private function event($it, &$schema)
    {
        $this->arr($it, [
            'address',
            'name',
            'eventType',
            'startDate',
        ], $schema, 'err');
        $this->arr($it, [
            'endDate',
            'phone',
            'guestCount',
            'seats',
        ], $schema, 'miss');
    }

    private function transfer($it, &$schema)
    {
        if (!isset($it['segments']))
            $it['segments'] = [];
        foreach($it['segments'] as $j => $seg) {
            $ss = [];
            $this->segment($seg, $ss, false);
            $this->arr($seg, [
                'carType',
                'carModel',
                'adults',
                'kids',
                'depAddress',
                'arrAddress',
            ], $ss, 'miss');
            $schema['segments'][$j] = $ss;
        }
    }

    private function bus($it, &$schema)
    {
        if (!isset($it['segments']))
            $it['segments'] = [];
        foreach($it['segments'] as $j => $seg) {
            $ss = [];
            $this->segment($seg, $ss, false);
            $this->arr($seg, [
                'busType', 'busModel',
            ], $ss, 'miss');
            $schema['segments'][$j] = $ss;
        }
    }

    private function train($it, &$schema)
    {
        if (!isset($it['segments']))
            $it['segments'] = [];
        foreach($it['segments'] as $j => $seg) {
            $ss = [];
            $this->segment($seg, $ss, false);
            $this->arr($seg, [
                'trainType', 'trainModel', 'carNumber', 'serviceName',
            ], $ss, 'miss');
            $schema['segments'][$j] = $ss;
        }
    }

    private function segment($seg, &$ss, $codes)
    {
        $this->arr($seg, ['depDate', 'arrDate', 'flightNumber', 'airlineName', 'number'], $ss, 'err');
        $this->arr($seg, ['depCode', 'arrCode'], $ss, $codes ? 'err' : 'miss');
        foreach(['dep', 'arr'] as $pre) {
            if (empty($seg[$pre . 'Date']) && !empty($seg[$pre . 'Day'])) {
                $ss[$pre . 'Date'] = $ss[$pre . 'Day'] = 'warn';
            }
            if (empty($seg[$pre.'Name']))
                $ss[$pre.'Name'] = ($codes && empty($seg[$pre.'Code'])) ? 'err' : 'warn';
        }
        $this->arr($seg, [
            'depTerminal',
            'arrTerminal',
            'operatedBy',
            'aircraft',
            'seats',
            'miles',
            'cabin',
            'duration',
            'meal',
        ], $ss, 'miss');
        $this->arr($seg, ['bookingCode'], $ss, $codes ? 'warn' : 'miss');
    }
    
    private function arr($data, $keys, &$ss, $level)
    {
        foreach($keys as $k) {
            if (array_key_exists($k, $data) && (is_null($data[$k]) || is_string($data[$k]) && strlen($data[$k]) === 0))
                $ss[$k] = $level;
        }
    }
    
    private function keyPair($data, $key, &$ss, $lvlMain, $lvlSub)
    {
        if (array_key_exists($key, $data)) {
            if (!isset($data[$key]) || is_string($data[$key]) && empty($data[$key]))
                $ss[$key] = $lvlMain;
            elseif (isset($lvlSub)) {
                $ss[$key] = [];
                foreach ($data[$key] as $i => $pair) {
                    if (!isset($pair[1]) || is_string($pair[1]) && empty($pair[1]))
                        $ss[$key][$i][1] = $lvlSub;
                }
            }
        }
    }
}