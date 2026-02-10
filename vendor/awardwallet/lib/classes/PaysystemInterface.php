<?php
interface PaysystemInterface {
    /**
     * 
     * Create Recurring Payment
     * @param float $recurringAmount
     * @param int $recurringPeriod - month
     * @param array $paymentInfo
     */
    public function createRecurringPayment($recurringAmount, $recurringPeriod, $paymentInfo);
    
    /**
     * 
     * Do recurring payment
     * @param $profileId
     */
    public function recurringPayment($profileId);
    
    public function getErrors();
}