<?php
require_once 'PaysystemInterface.php';

/**
 * 
 * Base class of Recurring Payment System
 * @author pavel
 *
 */
class PaysystemAbstract implements PaysystemInterface {
    /**
     * 
     * Payment info
     * @var array
     */
    protected $_paymentInfo;
    
    /**
     * 
     * Recurring amount
     * @var float
     */
    protected $_recurringAmount;
    
    /**
     * 
     * Period of recurring payment
     * @var int
     */
    protected $_recurringPeriod;
    
    /**
     * 
     * Recurring Profile Id
     * @var mixed
     */
    protected $_recurringProfileId;
    
    /**
     * 
     * Last errors
     * @var string
     */
    protected $_errors;

	public $controlSubscription = true;
    
    protected function onBeforeCreateRecurring()
    {
        
    }
    
    protected function onCreateRecurring() {}
    
    protected function onAfterCreateRecurring()
    {
    }
    
    public function createRecurringPayment($recurringAmount, $recurringPeriod, $paymentInfo)
    {
        $this->_recurringAmount = $recurringAmount;
        $this->_recurringPeriod = $recurringPeriod;
        $this->_paymentInfo = $paymentInfo;
        $this->_errors = null;
        
        $this->onBeforeCreateRecurring();
        $this->onCreateRecurring();
        $this->onAfterCreateRecurring();
        
        return  $this->_recurringProfileId;
    }
    
    public function recurringPayment($profileId) {}
    
    public function getErrors()
    {
        return $this->_errors;
    }
}