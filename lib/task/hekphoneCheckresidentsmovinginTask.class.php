<?php

class hekphoneCheckresidentsmovinginTask extends sfBaseTask
{
  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),

      new sfCommandOption('prepare-phone', null, sfCommandOption::PARAMETER_NONE, 'Prepare phone for the new residents'),
      new sfCommandOption('silent', null, sfCommandOption::PARAMETER_NONE, 'Suppress logging to stdout.'),
    ));

    // Prepare rendering of partials (load the PartialHelper)
    $configuration = ProjectConfiguration::getApplicationConfiguration('frontend', 'dev', true);
    sfContext::createInstance($configuration);
    sfProjectConfiguration::getActive()->loadHelpers("Partial");

    $this->namespace        = 'hekphone';
    $this->name             = 'check-residents-moving-in';
    $this->briefDescription = 'Check ';
    $this->detailedDescription = <<<EOF
The [hekphone:check-residents-moving-in|INFO] task checks which residents move in today and prepares the phone in the room
with the residents details.

If called withoud parameters it prints a list of all residents moving in today.
Call it with:

  [php symfony hekphone:check-residents-moving-in|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    $logger = new sfAggregateLogger($this->dispatcher);
    $logger->addLogger(new sfFileLogger($this->dispatcher, array('file' => $this->configuration->getRootDir() . '/log/cron-move_in.log')));
    if( ! $options['silent']) {
        $logger->addLogger(new sfCommandLogger($this->dispatcher));
    }

    $residentsMovingInToday = Doctrine_Core::getTable('Residents')->findResidentsMovingInToday();
    $logger->info("There are " . count($residentsMovingInToday) . " Residents moving in today.");

    if($options["prepare-phone"]){
        foreach($residentsMovingInToday as $resident){
            // get the phone if there's any
            if ( $resident['Rooms']->phone == NULL ) {
                $logger->error("No phone in room of resident " . $resident->getId() . ": " . $resident . ".");

                continue;
            } else {
                $phone = Doctrine_Core::getTable('Phones')->findOneById($resident['Rooms']->phone);
            }

            // Delete personal information from the phones properties (not from the settings on the phone)
            $phone->updateForResident($resident);
            $phone->save();

            // Reset phone and thereby delete the users information.
            if($phone->get('technology') == 'SIP') {
                try {
                    $phone->uploadConfiguration(true);
                    $phone->pruneAsteriskPeer();

                    $logger->notice("Prepared phone for resident " . $resident);
                } catch (Exception $e) {
                    $logger->error("Preparing the phone of resident " . $resident . " failed: " . $e->getMessage());
                }
            }
        }
    }
  }
}