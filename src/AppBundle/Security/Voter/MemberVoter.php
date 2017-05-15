<?php
/**
 * Created by PhpStorm.
 * User: famoser
 * Date: 14/05/2017
 * Time: 10:37
 */

namespace AppBundle\Security\Voter;


use AppBundle\Entity\FrontendUser;
use AppBundle\Entity\Member;
use AppBundle\Security\Voter\Base\CrudVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class MemberVoter extends OrganisationVoter
{
    /**
     * Determines if the attribute and subject are supported by this voter.
     *
     * @param string $attribute An attribute
     * @param mixed $subject The subject to secure, e.g. an object the user wants to access or any other PHP type
     *
     * @return bool True if the attribute and subject are supported, false otherwise
     */
    protected function supports($attribute, $subject)
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, array(self::VIEW, self::EDIT, self::REMOVE, self::ADMINISTRATE))) {
            return false;
        }

        // only vote on Post objects inside this voter
        if (!$subject instanceof Member) {
            return false;
        }

        return true;

    }

    /**
     * Perform a single access check operation on a given attribute, subject and token.
     * It is safe to assume that $attribute and $subject already passed the "supports()" method check.
     *
     * @param string $attribute
     * @param Member $subject
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof FrontendUser) {
            return false;
        }


        //check if own member
        $own = $subject->getPersons()->contains($user->getPerson());

        $organisation = $subject->getOrganisation();

        switch ($attribute) {
            case self::VIEW:
                return $own || parent::voteOnAttribute(self::VIEW, $organisation, $token);
            case self::EDIT:
                return $own || parent::voteOnAttribute(self::ADMINISTRATE, $organisation, $token);
            case self::ADMINISTRATE:
            case self::REMOVE:
                return parent::voteOnAttribute(self::ADMINISTRATE, $organisation, $token);
        }

        throw new \LogicException('This code should not be reached!');
    }
}