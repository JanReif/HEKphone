<?php

/**
 * Phones
 *
 * This class has been auto-generated by the Doctrine ORM Framework
 *
 * @package    hekphone
 * @subpackage model
 * @author     Your name here
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
class Phones extends BasePhones
{
  /**
   * Update the phones details according to the room where it's currently located in.
   *
   * @param $room Doctrine_Record of the room where the phone is located
   * @return Phones $this
   */
  public function updateForRoom($room) {
      $extension = '1' . str_pad($room->get('room_no'), 3, "0", STR_PAD_LEFT);
      $this->set('name', $extension);
      $this->set('callerid', $extension);
      $this->set('defaultuser', $extension);
      $this->set('defaultip', '192.168.' . substr($extension,1,1) . "." . (int)substr($extension,2,3));

      return $this;
  }

  /**
   * Update the phones details according to who lives in the room. If the room is not inhabitated nothing is set.
   *
   * @param $resident Doctrine_Record of the resident
   * @return Phones $this
   */
  public function updateForResident($resident) {
      $this->set('callerid', $resident->first_name . " " . $resident->last_name . ' <' . $this->name . '>');
      $this->set('language', substr($resident->culture,0,2));
      $this->set('mailbox', $resident->id . "@default");

      return $this;
  }

  /**
   * Returns the extension of the phone (like 1400) if it's located in any room.
   * False if the room is not allocated to any room
   *
   * @return string
   */
  public function getExtension() {
      if(isset($this->Rooms[0])) {
        return $extension = "1" . $this->Rooms[0];
      } else {
        return false;
      }
  }

  /**
   * Returns the extension neccesair to call the phone as array
   * (Intended for use with asteriskExtensions->fromArray())
   * Creates voicemailbox for the resident associated with the phone if he activated it.
   *
   * @return array()
   */
  public function getExtensionsAsArray() {
       $context   = 'phones';
      $extensionPrefix = '8695';

      /* Check wheter the phone is really in a room */
      if ( ! $extension = $this->getExtension()) {
          sfContext::getInstance()->getLogger()->warning('Failed to update extension of a phone (' . $this->get('id') . ') which is not in any room.');
          return false;
      }

      /* Look for a resident in the phones room */
      try {
        $resident = Doctrine_Core::getTable('Residents')->findByRoomNo($this->Rooms[0]->get('room_no'));
      } catch (Exception $e) {
        $resident = false;
      }

      /* Prepare the mailbox if whished */
      if($resident['vm_active']) {
        $resident->createVoicemailbox();
      }

      /* Prepare the extensions entries */
      // Calls to the phone from the PSTN
      $arrayExtensions[0] = array(
           'exten'        => $extensionPrefix . $extension,
           'priority'     => 1,
           'context'      => $context,
           'app'          => 'Dial',
      );
      if ($resident && $resident['vm_active'] && $this['technology'] == 'SIP') {
          // for SIP-Phones and in case the voicemail is activated, only ring for a specified period of time
          $arrayExtensions[0]['appdata'] = $this['technology'] . '/' . $extension . sfConfig::get('asteriskParameterSeparator')
                                         . $resident['vm_seconds'];
      } elseif($this['technology'] == 'DAHDI/g1') {
          // For analog phones don't activate the vm box and dial with prefix so the PBX gets what we want
          $arrayExtensions[0]['appdata'] = $this['technology'] . '/' . $extensionPrefix . $extension;
      } else {
          // for every other phone don't activate the mailbox
          $arrayExtensions[0]['appdata'] = $this['technology'] . '/' . $extension;
      }

      // include forwarding to mailbox if the resident turned on the vm
      if ($resident && $resident['vm_active'] && $this['technology'] == 'SIP')
      {
          $arrayExtensions[1] = array(
              'exten'        => $extensionPrefix . $extension,
              'priority'     => 2,
              'context'      => $context,
              'app'          => 'Voicemail',
              'appdata'      => $resident->id . '@default'
          );
      }

      // hangup after the call finished
      $arrayExtensions[2] = array(
          'exten'        => $extensionPrefix . $extension,
          'priority'     => 99,
          'context'      => $context,
          'app'          => 'Hangup',
          'appdata'      => ''
      );

      // Calls to the phone from other sip phones
      $arrayExtensions[3] = array(
           'exten'        => $extension,
           'priority'     => 1,
           'context'      => $context,
           'app'          => 'Set',
           'appdata'      => 'CDR(userfield)=internal'
      );
      $arrayExtensions[4] = array(
           'exten'        => $extension,
           'priority'     => 2,
           'context'      => $context,
           'app'          => 'GoTo',
           'appdata'      => $context . sfConfig::get('asteriskParameterSeparator')
                           . $extensionPrefix . $extension . sfConfig::get('asteriskParameterSeparator')
                           . '1' //Goto(context,extension,priority)
      );

      return $arrayExtensions;
  }

  /**
   * Create and save a configuration file for the phone that can be uploaded and be
   * used to reset a tiptel 83 VoIP phone.
   *
   *
   * @param $overwritePersonalSettings bool Wheter to overwrite the phone book, short dial, ...
   * @return string
   */
  public function createPhoneConfigFile($overridePersonalSettings = false)
  {

  	  if (isset($this->Rooms[0]->Residents[0]))
  	  {
  	      $sip1Pwd = $this->Rooms[0]->Residents[0]->get('password');
  	  } else {
  	      $sip1Pwd = 'hekphone';
  	  }

      $configFileContent = get_partial('global/tiptel88PhoneConfiguration', array('ip' => $this['defaultip'],
          'sip1PhoneNumber' => $this['name'],
          'sip1DisplayName' => $this->Rooms[0]->Residents[0]->get('first_name') . " "
          .$this->Rooms[0]->Residents[0]->get('last_name') . " ("
          .$this->Rooms[0]->get('room_no') . ")",
          'sip1User' => $this['defaultuser'],
          'sip1Pwd' => $sip1Pwd,
          'overridePersonalSettings' => $overridePersonalSettings));

      $folder     = sfConfig::get("sf_data_dir") . DIRECTORY_SEPARATOR . "phoneConfigs" . DIRECTORY_SEPARATOR;
      $filepath   = $folder . $this['name'] . "-config.txt";
      $filehandle = fopen($filepath, "w+");
      if( ! fwrite($filehandle, $configFileContent))
      {
          throw new Exception("Could not write config file to $filepath");
      } else {
          return $filepath;
      }
	}

  /**
   * Generate and upload a configuration to the phone at $this->defaultip
   * via HTTP.
   *
   *  @param $overwritePersonalSettings bool Wheter to overwrite the phone book, short dial, ...
   */
  public function uploadConfiguration($overwritePersonalSettings = false) {
        $sendAuthCookie = 'c0a900010000009b';
        $httpHeaders = array(
            'Keep-Alive: 115',
            'Connection: keep-alive',
            'Cookie: auth=' . $sendAuthCookie);
        $password = 'admin';
        $username = 'admin';


        /* Get the front page to get an authentication cookie in return */
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
        curl_setopt($ch, CURLOPT_URL, "http://" . $this->defaultip);
        curl_setopt($ch,CURLOPT_TIMEOUT, 10);
        //curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // output to string
        curl_setopt($ch, CURLOPT_HEADER, 1); // include headers in the output

        if( ! $loginPageContent = curl_exec($ch) ) {
            throw new Exception("Unable to connect to a phone at $this->defaultip");
        }

        //get the cookie and use new headers from now on
        preg_match('/^Set-Cookie: auth=(.*?);/m', $loginPageContent, $m);
        $newAuthCookie = $m[1];
        $httpHeaders = array(
            'Keep-Alive: 115',
            'Connection: keep-alive',
            'Cookie: auth=' . $newAuthCookie);

        curl_close($ch);


        /* Login */
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
        curl_setopt($ch, CURLOPT_URL, "http://" . $this->defaultip);
        //curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // the sort order of the parameters MATTERS!
        //$loginPostData  = 'username' . '=' . $username . '&'; // we don't need to transfer the username and password the phone
        //$loginPostData .= 'password' . '=' . $password . '&'; // expects the username and a salted password hash as encoded field instead
        $loginPostData  = 'encoded'  . '=' . $username . '%3A' . md5($username . ':' . $password . ':' . $newAuthCookie) . '&';
        $loginPostData .= 'nonce'    . '=' . $newAuthCookie . '&';
        $loginPostData .= 'goto'     . '=' . 'OK' . '&';
        $loginPostData .= 'URL'      . '=' . '%2F';

        curl_setopt($ch, CURLOPT_POSTFIELDS, $loginPostData);

        $loginResult = curl_exec($ch);

        if( ! strpos($loginResult, "PHONE CONFIG")) {
          throw new Exception("Login on the phones webfrontend at $this->defaultip with password $password and username $username failed");
        }

        /* Generate configuration file and get the path */
        $configurationFilePath = $this->createPhoneConfigFile($overwritePersonalSettings);

        /* Upload the configuration */
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
        curl_setopt($ch, CURLOPT_URL, "http://" . $this->defaultip . '/directupdate.htm ');
        //curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $uploadPostData = array(
            'System' => '@' . $configurationFilePath,
            'WebUpdate' => 'Übertragen',);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $uploadPostData);

        $uploadResult = curl_exec($ch);

        if( ! strpos($uploadResult, "ok post")) {
          throw new Exception("Uploading the configuration file failed.");
        }

        return true;
  }
}