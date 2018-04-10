<?php

/*
 * This file is part of the nodika project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures\Production;

use App\DataFixtures\Base\BaseFixture;
use App\Entity\AdminUser;
use Doctrine\Common\Persistence\ObjectManager;

class LoadAdminUserData extends BaseFixture
{
    /**
     * Load data fixtures with the passed EntityManager.
     *
     * @param ObjectManager $manager
     *
     * @throws \BadMethodCallException
     */
    public function load(ObjectManager $manager)
    {
        $user = new AdminUser();
        $user->setEmail('info@nodika.ch');
        $user->setPlainPassword('jhagfgawefgajwef');
        $user->setPassword();
        $user->setRegistrationDate(new \DateTime());
        $manager->persist($user);
        $manager->flush();
    }

    public function getOrder()
    {
        return 1;
    }

    /**
     * create an instance with all random values.
     *
     * @return mixed
     */
    protected function getRandomInstance()
    {
        return null;
    }
}
