<?php

/**
 * Bills
 *
 * This class has been auto-generated by the Doctrine ORM Framework
 *
 * @package    hekphone
 * @subpackage model
 * @author     Your name here
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
class Bills extends BaseBills
{
    /**
     * Get all calls associated with the bill.
     */
    public function getCalls()
    {
        return Doctrine_query::create()
               ->from('Calls c')
               ->addWhere('c.bill = ?', $this->id)
               ->execute();
    }

    /**
     * A string with all itemized Bill entries for each related call of the bill is returned
     *
     * @return string $itemizedBill
     */
    public function getItemizedBill()
    {
    	$itemizedBill = str_pad('Datum',21)
        		        .str_pad('Dauer(sec)',12)
                        .str_pad('externe Nummer',25)
                        .str_pad('Kosten (ct)',14)
                        .str_pad('Rate',18)."\n";

        foreach($this['Calls'] as $call)
        {
            $itemizedBill .= $call->getItemizedBillEntry()."\n";
  	    }

        return $itemizedBill;
    }

     /**
     * Send the bill via Email to the resident.
     *
     * @param string $start Start of the billing period
     * @param string $end End of the billing period
     */
    public function sendEmail()
    {
        // check for non_empty email-field rather than unlocked user?
        if ($this['Residents']['unlocked'] == true)
        {


            // compose the message
            $messageBody = get_partial('global/billingMail', array('firstName' => $this['Residents']['first_name'],
                                                                'start' => $this['billingperiod_start'],
                                                                'end' => $this['billingperiod_end'],
                                                                'billId' => $this['id'],
                                                                'amount' => $this['amount'],
                                                                'accountNumber' => $this['Residents']['account_number'],
                                                                'bankNumber' => $this['Residents']['bank_number'],
                                                                'itemizedBill' => $this->getItemizedBill()));
            $message = Swift_Message::newInstance()
                ->setFrom(sfConfig::get('hekphoneFromEmailAdress'))
                ->setTo($this['Residents']['email'])
                ->setSubject('[HEKphone] Deine Rechnung vom ' . $this['date'])
                ->setBody($messageBody);

            return sfContext::getInstance()->getMailer()->send($message);
        }
    }
    /**
     * Send the bill via Email to the resident. The bill is already paid with cash. No bank transaction required
     *
     * @param string $start Start of the billing period
     * @param string $end End of the billing period
     */
    public function sendEmailWithoutDirectDebit()
    {
        // check for non_empty email-field rather than unlocked user?
        if ($this['Residents']['unlocked'] == true)
        {


            // compose the message
            $messageBody = get_partial('global/billingMailWithoutDirectDebit', array('firstName' => $this['Residents']['first_name'],
                                                                'start' => $this['billingperiod_start'],
                                                                'end' => $this['billingperiod_end'],
                                                                'billId' => $this['id'],
                                                                'amount' => $this['amount'],
                                                                'itemizedBill' => $this->getItemizedBill()));
            $message = Swift_Message::newInstance()
                ->setFrom(sfConfig::get('hekphoneFromEmailAdress'))
                ->setTo($this['Residents']['email'])
                ->setSubject('[HEKphone] Deine Rechnung vom ' . $this['date'])
                ->setBody($messageBody);

            return sfContext::getInstance()->getMailer()->send($message);
        }
    }    
    
    /**
     * Changes the field "bill" of every unbilled call of the corresponding resident
     * in the given time period to the according bill id.
     * First the bill need an id, so call save() first or an exception will be thrown
     *
     * @throws Exception when the bill has no id
     * @throws Exception when there are already some linked calls
     * @returns Bills $this
     */
    public function linkCalls() {
        if( ! $this->exists()) {
            throw new Exception("Is not yet saved in the database. Use save() first.");
        }

        if(isset($this->Calls)) {
            throw new Exception("There are already some related calls. This method is not supposed to relink calls.");
        }

        $newAmount = Doctrine_Query::create()
            ->from('Calls c')
            ->select('sum(charges)/100')
            ->where('resident = ?', $this->resident)
            ->addWhere('bill is null')
            ->addWhere('date <= ?', $this->get('billingperiod_end') . ' 23:59:59')
            ->addWhere('date >= ?', $this->get('billingperiod_start'))
            ->setHydrationMode(Doctrine::HYDRATE_SINGLE_SCALAR)
            ->execute();

        $newAmount = (float)$newAmount;
        if(round($newAmount,4) != round($this->amount,4)) {
            throw new Exception("The amount of the bill changed between creation of the bill and allocating the calls to the bill.");
        }

        $calls = Doctrine_Query::create()
            ->from('Calls c')
            ->select('c.bill')
            ->where('resident = ?', $this->resident)
            ->addWhere('bill is null')
            ->addWhere('date <= ?', $this->get('billingperiod_end') . ' 23:59:59')
            ->addWhere('date >= ?', $this->get('billingperiod_start'))
            ->execute();

        foreach($calls as $call) {
            $call->set('bill', $this->id);
        }

        $calls->save();

        return $this;
    }
}
