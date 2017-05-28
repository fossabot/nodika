<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Member;
use AppBundle\Entity\Organisation;

/**
 * MemberRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class MemberRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @param Organisation $organisation
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getByOrganisationQueryBuilder(Organisation $organisation)
    {
        return $this->createQueryBuilder("u")->where('u.organisation = :organisation')->setParameter('organisation', $organisation);
    }

    /**
     * @param Organisation $organisation
     * @return Member[]
     */
    public function getIdAssociatedArray(Organisation $organisation)
    {
        $res = [];
        /* @var Member[] $members */
        $members = $this->findBy(["organisation" => $organisation]);
        foreach ($members as $memberEntry) {
            $res[$memberEntry->getId()] = $memberEntry;
        }
        return $res;
    }
}
