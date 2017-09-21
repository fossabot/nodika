<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Organisation;
use Doctrine\ORM\EntityRepository;

/**
 * EventLineRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EventLineRepository extends EntityRepository
{
    /**
     * @param Organisation $organisation
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getByOrganisationQueryBuilder(Organisation $organisation)
    {
        return $this->createQueryBuilder("u")->where('u.organisation = :organisation')->setParameter('organisation', $organisation);
    }
}
