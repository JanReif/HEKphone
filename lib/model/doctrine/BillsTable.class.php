<?php

/**
 * BillsTable
 *
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class BillsTable extends Doctrine_Table
{
    /**
     * Returns an instance of this class.
     *
     * @return object BillsTable
     */
    public static function getInstance()
    {
        return Doctrine_Core::getTable('Bills');
    }


     /**
     * All unbillded calls are billed and the bill id is set to the now billed call
     * @param string $options['fromDate'] Start date for bill period
     * @param string $options['toDate'] End date for the bill period
     * @return boolean
     */
    public function createBills($start, $end)
    {
        //fetch all unbilled calls from the given time period
        $unbilledCalls = Doctrine_Query::create()
                            ->from('Calls')
                            ->addWhere('bill is null')
                            ->addWhere('date <= ?', $end)
                            ->addWhere('date >= ?', $start);

        if ( ! $unbilledCalls = $unbilledCalls->execute())
        {
            return false; // no bills were created
        }

        //Calculate the amount of all unbilled calls for one resident
        foreach ($unbilledCalls as $unbilledCall)
        {
        	$sums[$unbilledCall['resident']] += $unbilledCall['charges'];
        }

        if ( ! isset($sums)){
            return false;  // every call was a free call, so no bills were created
        }

        foreach ($sums as $residentid => $amount)
        {
        	//Prepare the bills for each resident
        	$billsArray[] = array(
        	               'resident'  => $residentid,
        	               'amount'    => $amount,
        	               'date'      => date("Y-m-d")
        	 );
        }

        //Create the bills and save them into the database.
        // $billsCollection now contains the bill ID
        $billsCollection = new BillsCollection('Bills');
        $billsCollection->fromArray($billsArray);
        $billsCollection->save();

        //Assign the bill id to the now billed calls
        $billsArray = $billsCollection->toArray();
        foreach ($unbilledCalls as $key => $unbilledCall)
        {
        	foreach ($billsArray as $bill)
        	{
        	   if($unbilledCall['resident'] == $bill['resident'])
        	   {
        	   	$currentBillId = $bill['id'];
        	   }
        	}
        	$unbilledCalls[$key]->set('bill', $currentBillId);
        }
        $unbilledCalls->save();

        // send the bills as email to the residents
        $billsCollection->loadRelated('Calls');
        $billsCollection->sendEmails($start, $end);

        return true;
    }

    public function deleteOldBills()
    {
        $this->createQuery()
            ->delete()
            ->where('date <= ?', date('Y-m-d',strtotime('-' . sfConfig::get('monthsToKeepBillsFor') . ' months')))
            ->execute();
    }
}