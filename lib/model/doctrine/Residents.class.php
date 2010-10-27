<?php

/**
 * Residents
 *
 * This class has been auto-generated by the Doctrine ORM Framework
 *
 * @package    hekphone
 * @subpackage model
 * @author     Your name here
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
class Residents extends BaseResidents
{
     /**
     * Writes the residents password md5-encrypted to the database
     *
     * @param string $password
     */
    public function setPassword($password)
    {
      // don't update to empty password
      if ($password == '')
      {
        return $this;
      }
        return $this->_set('password', md5($password));
    }

    /**
     * Sets a residents voicemail-settings. Modifies the asterisk_voicemail and
     * asterisk_extensions database table.
     *
     * @param boolean $active
     * @param integer $seconds
     * @param boolean $mailOnNewMessage
     * @param boolean $attachMessage
     * @param boolean $mailOnMissedCall
     */
    public function setVoicemailSettings($active, $seconds, $mailOnNewMessage, $attachMessage, $mailOnMissedCall)
    {
      var_dump($active);
      var_dump($seconds);
      var_dump($mailOnNewMessage);
      var_dump($attachMessage);
      var_dump($mailOnMissedCall);
      //TODO: Implement this!
    }

    public function getVoicemailSettings()
    {
      //TODO: Implement this!
    }
}
