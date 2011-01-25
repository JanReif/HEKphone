<?php

/**
 * Groupcalls
 *
 * This class has been auto-generated by the Doctrine ORM Framework
 *
 * @package    hekphone
 * @subpackage model
 * @author     Your name here
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
class Groupcalls extends BaseGroupcalls
{
  /**
   * Returns an array of groupcalls extensions for the asterisk dialplan. Intended for use with
   * AsteriskExtensions->fromArray()
   *
   * @return array $arayExtensions
   */
  public function getExtensionsAsArray() {
    $context = 'groupcalls';

    /* We're creating an extension that will dial to the phones of all Residents in the Groupcall
     * simultaniousely. We just need to connect the dialstrings with & for asterisk for this.
     */
    $dialstring = '';
    foreach($this->Residents as $Resident) {
        // Analog phones and sip phones have a slightly different dialstring.
        if($Resident->Rooms->Phones->get('technology') == 'DAHDI/g1')
        {
          $prefix = '86951';
        } else {
          $prefix = '1';
        }

        // Append to the dialstring
        $dialstring .= $Resident->Rooms->Phones->get('technology') . '/' . $prefix . $Resident->Rooms . '&';
        ;
    }

    // Return an empty array, if there are no residents in the groupcall.
    if($dialstring == ''){
        return array();
    }

    // Strip the last &
    $dialstring = substr($dialstring, 0, -1);


    $arrayExtensions[0] = array(
         'exten'        => $this->extension,
         'priority'     => 1,
         'context'      => $context,
         'app'          => 'Dial',
         'appdata'      => $dialstring
    );
    $arrayExtensions[1] = array(
         'exten'        => $this->extension,
         'priority'     => 1,
         'context'      => $context,
         'app'          => 'Hangup',
    );

    return $arrayExtensions;
  }
}