<?php
/**
 * Created by PhpStorm.
 * User: famoser
 * Date: 11/05/2017
 * Time: 14:01
 */

namespace AppBundle\DataFixtures\ORM\Production;


use AppBundle\DataFixtures\ORM\Base\BaseFixture;
use AppBundle\Entity\Organisation;
use Doctrine\Common\Persistence\ObjectManager;

class LoadOrganisationData extends BaseFixture
{

    /**
     * create an instance with all random values
     *
     * @return Organisation
     */
    protected function getAllRandomInstance()
    {
        $organisation = new Organisation();
        $this->fillRandomCommunication($organisation);
        $this->fillRandomAddress($organisation);
        $this->fillRandomThing($organisation);
        return $organisation;
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $organisation = $this->getAllRandomInstance();
        $organisation->addLeader($this->getReference("person-1"));
        $manager->persist($organisation);
        $manager->flush();
    }

    /**
     * Get the order of this fixture
     *
     * @return integer
     */
    public function getOrder()
    {
        return 12;
    }
}