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

use App\Entity\FrontendUser;
use Doctrine\ORM\EntityRepository;

/**
 * UserRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class FrontendUserRepository extends EntityRepository
{
    /**
     * @param FrontendUser $user
     *
     * @return FrontendUser|null
     */
    public function tryLogin(FrontendUser $user)
    {
        /* @var FrontendUser $tryUser */
        $tryUser = $this->findOneBy(['email' => $user->getEmail()]);
        $tryUser->setPlainPassword($user->getPlainPassword());
        if ($tryUser->tryLoginWithPlainPassword()) {
            return $tryUser;
        }

        return null;
    }
}
