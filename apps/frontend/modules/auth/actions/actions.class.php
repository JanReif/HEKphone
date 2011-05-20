<?php

/**
 * user actions.
 *
 * @package    hekphone
 * @subpackage user
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class authActions extends sfActions
{
  /**
   * Executes the login-action: Checks room no/password and then sets the user as
   * authenticated, sets user attributes ('name', 'id', 'roomNo', culture) and sets credentials
   *
   * @param sfRequest $request A request object
   */
  public function executeIndex(sfWebRequest $request)
  {
    if( ! $this->getUser()->isAuthenticated())
    {
      $this->getUser()->setCulture($request->getPreferredCulture(array('de','en')));
    }

    $this->form = new LoginForm();

    if($request->isMethod('post'))
    {
      $this->form->bind($request->getParameter($this->form->getName()));
      if ($this->form->isValid())
      {
        $residentsTable = Doctrine_Core::getTable('Residents');
        try
        {
          $resident = $residentsTable->findByRoomNo($this->form->getValue('roomNo'));
        } catch(Exception $e) {
          $this->getUser()->setFlash('notice', 'auth.login.failed');
          $this->redirect('auth/index');
        }
        if(null !== $resident && $resident->password === md5($this->form->getValue('password')))
        {
          $this->getUser()->setAuthenticated(true);

          // Set basic attributes of the signed in user.
          $this->getUser()->setAttribute("name", $resident->first_name);
          $this->getUser()->setAttribute("id", $resident->id);
          $this->getUser()->setAttribute("roomNo", $resident['Rooms']['room_no']);

          if( ! is_null($resident->culture) && $resident->culture != '') {
            // set the language according to what the user chose
            $this->getUser()->setCulture($resident->culture);
          } else {
            // set the language according to the preferred culture of the browser
            // and save the settings for the resident
            $preferedCulture = $request->getPreferredCulture(array('de','en'));
            $this->getUser()->setCulture($preferedCulture);
            $resident->set('culture', $preferedCulture);
            $resident->save();
          }

          if($resident->hekphone)
          {
            // user is a HEKPhone staff member
            $this->getUser()->addCredential('hekphone');
          }

          $this->getUser()->setFlash('notice', 'auth.login.successful');
          $this->redirect('calls/index');
        }
        else
        {
          $this->getUser()->setFlash('notice', 'auth.login.failed');
          $this->redirect('auth/index');
        }
      }
    }
  }

  public function executeLogout(sfWebRequest $request)
  {
    $this->getUser()->setAuthenticated(false);
    $this->getUser()->setFlash('notice', 'auth.logout.successful');
    $this->redirect('auth/index');
  }
}
