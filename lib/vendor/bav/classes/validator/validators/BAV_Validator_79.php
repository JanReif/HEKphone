<?php
BAV_Autoloader::add('../BAV_Validator.php');
BAV_Autoloader::add('BAV_Validator_00.php');
BAV_Autoloader::add('../../bank/BAV_Bank.php');


/**
 * Copyright (C) 2006  Markus Malkusch <bav@malkusch.de>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *
 * @package classes
 * @subpackage validator
 * @author Markus Malkusch <bav@malkusch.de>
 * @copyright Copyright (C) 2006 Markus Malkusch
 */
class BAV_Validator_79 extends BAV_Validator {


    private
    /**
     * @var BAV_Validator_00
     */
    $validator,
    /**
     * @var BAV_Validator_00
     */
    $mode1,
    /**
     * @var BAV_Validator_00
     */
    $mode2;


    public function __construct(BAV_Bank $bank) {
        parent::__construct($bank);
        
        $this->mode1 = new BAV_Validator_00($bank);
        $this->mode2 = new BAV_Validator_00($bank);
        $this->mode2->setStart(-3);
        $this->mode2->setChecknumberPosition(-2);
    }
    
    protected function init($account) {
        parent::init($account);
        
        $this->validator = null;
    }
    protected function validate() {
        if (array_search($this->account{0}, array(1, 2, 9)) !== false) {
            $this->validator = $this->mode2;
        
        } elseif ($this->account{0} == 0) {
            $this->validator = null;
        
        } else {
            $this->validator = $this->mode1;
        
        }
    }
    /**
     * @return bool
     */
    protected function getResult() {
        return ! is_null($this->validator) && $this->validator->isValid($this->account);
    }
    

}


?>