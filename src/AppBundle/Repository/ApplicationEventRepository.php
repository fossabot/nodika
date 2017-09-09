<?php

namespace AppBundle\Repository;

use AppBundle\Entity\ApplicationEvent;
use AppBundle\Entity\Organisation;

/**
 * EventRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ApplicationEventRepository extends \Doctrine\ORM\EntityRepository
{
    public function hasEventOccurred(Organisation $organisation, $applicationEventType)
    {
        $entity = $this->findOneBy(["organisation" => $organisation->getId(), "applicationEventType" => $applicationEventType]);
        return $entity instanceof ApplicationEvent;
    }

    public function registerEventOccurred(Organisation $organisation, $applicationEventType)
    {

    }
}