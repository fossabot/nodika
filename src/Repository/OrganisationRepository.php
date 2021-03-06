<?php

/*
 * This file is part of the nodika project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Event;
use App\Entity\EventLine;
use App\Entity\Member;
use App\Entity\Organisation;
use App\Entity\Person;
use App\Enum\ApplicationEventType;
use App\Model\Event\SearchEventModel;
use App\Model\EventLine\EventLineModel;
use App\Model\Organisation\SetupStatusModel;
use Doctrine\ORM\EntityRepository;

/**
 * OrganisationRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class OrganisationRepository extends EntityRepository
{
    /**
     * @param Organisation $organisation
     * @param \DateTime    $dateTime
     * @param string       $comparator
     * @param int          $maxResults
     *
     * @return Event[]
     */
    public function findEvents(Organisation $organisation, \DateTime $dateTime, $comparator = '>', $maxResults = 30)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('e')
            ->from('App:Event', 'e')
            ->join('e.member', 'm')
            ->join('m.organisation', 'o')
            ->where("e.startDateTime $comparator :startDateTime")
            ->andWhere('o = :organisation')
            ->setParameter('startDateTime', $dateTime)
            ->setParameter('organisation', $organisation)
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param SearchEventModel $searchEventModel
     *
     * @return EventLineModel[]
     */
    public function findEventLineModels(SearchEventModel $searchEventModel)
    {
        $res = [];
        foreach ($searchEventModel->getOrganisation()->getEventLines() as $eventLine) {
            $eventLineModel = new EventLineModel();
            $eventLineModel->eventLine = $eventLine;

            if ($searchEventModel->getFilterEventLine() instanceof EventLine &&
                $searchEventModel->getFilterEventLine()->getId() != $eventLine->getId()) {
                continue;
            }

            $qb = $this->getEntityManager()->createQueryBuilder()
                ->select('e')
                ->from('App:Event', 'e')
                ->join('e.eventLine', 'el')
                ->leftJoin('e.member', 'm')
                ->leftJoin('e.person', 'p')
                ->where('el = :eventLine')
                ->setParameter('eventLine', $eventLine);

            $qb->andWhere('e.startDateTime > :startDateTime')
                ->setParameter('startDateTime', $searchEventModel->getStartDateTime());

            if ($searchEventModel->getEndDateTime() instanceof \DateTime) {
                $qb->andWhere('e.endDateTime < :endDateTime')
                    ->setParameter('endDateTime', $searchEventModel->getEndDateTime());
            }

            if ($searchEventModel->getFilterMember() instanceof Member) {
                $qb->andWhere('m = :member')
                    ->setParameter('member', $searchEventModel->getFilterMember());
            }

            if ($searchEventModel->getFilterPerson() instanceof Person) {
                $qb->andWhere('p = :person')
                    ->setParameter('person', $searchEventModel->getFilterPerson());
            }


            $qb->setMaxResults($searchEventModel->getMaxResults());

            $eventLineModel->events = $qb->getQuery()
                ->getResult();
            $res[] = $eventLineModel;
        }

        return $res;
    }

    /**
     * @param EventLineModel[] $eventLineModels
     *
     * @return boolean
     */
    public function addActiveEvents(array $eventLineModels)
    {
        $res = false;
        foreach ($eventLineModels as $eventLineModel2) {
            $eventLine = $eventLineModel2->eventLine;
            $qb = $this->getEntityManager()->createQueryBuilder()
                ->select('e')
                ->from('App:Event', 'e')
                ->join('e.eventLine', 'el')
                ->leftJoin('e.member', 'm')
                ->leftJoin('e.person', 'p')
                ->where('el = :eventLine')
                ->setParameter('eventLine', $eventLine);

            $qb->andWhere('e.startDateTime < :startDateTime')
                ->setParameter('startDateTime', new \DateTime());

            $qb->andWhere('e.endDateTime > :endDateTime')
                ->setParameter('endDateTime', new \DateTime());

            $eventLineModel2->activeEvents = $arr = $qb->getQuery()->getResult();
            $res |= count($eventLineModel2->activeEvents);
        }

        return $res;
    }

    /**
     * @param Person $person
     *
     * @return array
     */
    public function findByPerson(Person $person)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('o')
            ->from('App:Organisation', 'o')
            ->join('o.members', 'm')
            ->join('m.persons', 'p')
            ->where('p = :person')
            ->setParameter('person', $person)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Organisation $organisation
     *
     * @return SetupStatusModel
     */
    public function getSetupStatus(Organisation $organisation)
    {
        $setupStatus = new SetupStatusModel();
        $setupStatus->setHasMembers($organisation->getMembers()->count() > 0);
        $setupStatus->setHasEventLines($organisation->getEventLines()->count() > 0);
        $hasEvents = false;
        foreach ($organisation->getEventLines() as $eventLine) {
            $hasEvents = $hasEvents || $eventLine->getEvents()->count() > 0;
        }
        $setupStatus->setHasEvents($hasEvents);
        $hasInvitedMembers = false;
        foreach ($organisation->getMembers() as $member) {
            if (null !== $member->getInvitationDateTime()) {
                $hasInvitedMembers = true;
                break;
            }
            foreach ($member->getPersons() as $person) {
                if (null !== $person->getInvitationDateTime()) {
                    $hasInvitedMembers = true;
                    break;
                }
            }
        }
        $setupStatus->setHasInvitedMembers($hasInvitedMembers);
        $applicationEventRepo = $this->getEntityManager()->getRepository('App:ApplicationEvent');
        $hasVisitedSettings = $applicationEventRepo->hasEventOccurred($organisation, ApplicationEventType::VISITED_SETTINGS);
        $setupStatus->setHasVisitedSettings($hasVisitedSettings);

        return $setupStatus;
    }
}
