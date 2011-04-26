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
  private $resident = NULL;

  /**
   * Returns the resident as Doctrine_Record associated with this room.
   * False if there's on resident living in the room
   */
  public function getResident() {
      if(is_null($this->resident)) {
          try {
              $this->resident = Doctrine_Core::getTable('Residents')->findByRoomNo($this->Rooms[0]->get('room_no'));
          } catch (Exception $e) {
              $this->resident = null;
          }
      }

      return $this->resident;
  }

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
      $resident = $this->getResident();

      /* Check wheter the phone is really in a room */
      if ( ! $extension = $this->getExtension()) {
          sfContext::getInstance()->getLogger()->warning('Failed to get extension of a phone (' . $this->get('id') . ') which is not in any room.');
          return false;
      }

      /* Prepare the mailbox if whished */
      if($resident && $resident['vm_active']) {
        $resident->createVoicemailbox();
      }

      /* Prepare the extensions entries */
      // Calls to the phone from the PSTN
      $n = 1;

      // we mark the call as internal call eventhough it comes from outside
      // this is no problem because the call is still an incoming call and
      // thus never gets billed. we thereby solve the problem that calls from
      // analog phones to sip-phones arent marked as internal
      $arrayExtensions[0] = array(
           'exten'        => $extensionPrefix . $extension,
           'priority'     => $n++,
           'context'      => $context,
           'app'          => 'Set',
           'appdata'      => 'CDR(userfield)=internal'
      );

      $arrayExtensions[1] = array(
           'exten'        => $extensionPrefix . $extension,
           'priority'     => $n++,
           'context'      => $context,
           'app'          => 'Dial',
           'appdata'      => $this->getDialstring()
      );


      // include redirection of calls before the mailbox picks up
      if ($resident && $resident['redirect_active'] && $this['technology'] == 'SIP')
      {
          $residentsContext = ($resident['unlocked'])? 'unlocked' : 'locked';
          $arrayExtensions[2] = array(
              'exten'        => $extensionPrefix . $extension,
              'priority'     => $n++,
              'context'      => $context,
              'app'          => 'GoTo',
              'appdata'      => $residentsContext . sfConfig::get('asteriskParameterSeparator')
                              . $resident['redirect_to']  . sfConfig::get('asteriskParameterSeparator')
                              . '1'
          );
      }

      // include forwarding to mailbox if the resident turned on the vm
      if ($resident && $resident['vm_active'] && $this['technology'] == 'SIP')
      {
          $arrayExtensions[3] = array(
              'exten'        => $extensionPrefix . $extension,
              'priority'     => $n++,
              'context'      => $context,
              'app'          => 'Voicemail',
              'appdata'      => $resident['id'] . '@default'
          );
      }

      // hangup after the call finished
      $arrayExtensions[4] = array(
          'exten'        => $extensionPrefix . $extension,
          'priority'     => 99,
          'context'      => $context,
          'app'          => 'Hangup',
          'appdata'      => ''
      );


      // Calls to the phone from other sip phones
      $arrayExtensions[5] = array(
           'exten'        => $extension,
           'priority'     => 1,
           'context'      => $context,
           'app'          => 'GoTo',
           'appdata'      => $context . sfConfig::get('asteriskParameterSeparator')
                           . $extensionPrefix . $extension . sfConfig::get('asteriskParameterSeparator')
                           . '1' //Goto(context,extension,priority)
      );

      return $arrayExtensions;
  }

  /**
   * Gets the string for the appdata of the Dial command of asterisk for the phone
   * consideres redirections and voicemail
   * @return string
   */
  public function getDialstring() {
      $extension = $this->getExtension();
      $extensionPrefix = '8695';

      if ($this['technology'] == 'SIP') {
          $dialstring = $this['technology'] . '/' . $extension;
      } elseif($this['technology'] == 'DAHDI/g1') {
          $dialstring = $this['technology'] . '/' . $extensionPrefix . $extension;
      }

      $resident = $this->getResident();
      // redirect or call voicemailbox after the specified time period
      if ($resident != false && $resident['redirect_active']) {
          $dialstring .= sfConfig::get('asteriskParameterSeparator') . $resident['redirect_seconds'];
      } elseif($resident != false && $resident['vm_active']) {
          $dialstring .= sfConfig::get('asteriskParameterSeparator') . $resident['vm_seconds'];
      }

      return $dialstring;
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
  	  if($this->Rooms[0]->Residents[0]->get('password') != '')
  	  {
  	      $sip1Pwd = substr($this->Rooms[0]->Residents[0]->get('password'), 0 ,7);
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
          'overridePersonalSettings' => $overridePersonalSettings,
          'frontendPassword' => sfConfig::get("sipPhoneFrontendPwd")));

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
  public function uploadConfiguration($overwritePersonalSettings = false, $initialConfiguration = false) {
        if($this['technology'] != 'SIP')
        {
          return false;
        }

        $sendAuthCookie = 'c0a900010000009b';
        $httpHeaders = array(
            'Keep-Alive: 115',
            'Connection: keep-alive',
            'Cookie: auth=' . $sendAuthCookie);

        if ($initialConfiguration)
        {
	        $password = 'admin';
	        $username = 'admin';
        }
        else
        {
	        $password = sfconfig::get("sipPhoneFrontendPwd");
	        $username = 'admin';
        }


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

        if(strpos($loginResult, "PHONE CONFIG") === false) {
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

        if(strpos($uploadResult, "ok post") === false) {
          throw new Exception("Uploading the configuration file failed.");
        }

        return true;
  }
}