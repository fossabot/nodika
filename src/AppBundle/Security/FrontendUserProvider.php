<?php
/**
 * Created by PhpStorm.
 * User: famoser
 * Date: 18.04.2017
 * Time: 11:34
 */

namespace AppBundle\Security;


use AppBundle\Entity\FrontendUser;
use AppBundle\Entity\UserEventLog;
use AppBundle\Security\Base\BaseUserProvider;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class FrontendUserProvider extends BaseUserProvider
{
    /**
     * @var RegistryInterface $registry
     */
    private $registry;
    /**
     * @var RequestContext
     */
    private $context;

    public function __construct(RegistryInterface $registry, RequestContext $context)
    {
        $this->registry = $registry;
        $this->context = $context;
    }

    /**
     * Loads the user for the given username.
     *
     * This method must throw UsernameNotFoundException if the user is not
     * found.
     *
     * @param string $username The username
     *
     * @return UserInterface
     *
     * @throws UsernameNotFoundException if the user is not found
     */
    public function loadUserByUsername($username)
    {
        $user = $this->registry->getRepository("AppBundle:FrontendUser")->findOneBy(["email" => $username]);
        if ($user != null) {
            //resolve path infos
            $path = $this->context->getPathInfo();
            $method = $this->context->getMethod();

            if (strpos($path, "analyze") === false) {
                //create new event
                $event = new UserEventLog();
                $event->setPerson($user->getPerson());
                $event->setKey("request logged");
                $event->setOccurredAtDateTime(new \DateTime());
                $event->setValue($method . ": " . $path);

                // log event
                $mng = $this->registry->getManager();
                $mng->persist($event);
                $mng->flush();
            }

            return $user;
        }

        throw new UsernameNotFoundException(
            sprintf('Username "%s" does not exist in UserProvider.', $username)
        );
    }

    /**
     * Refreshes the user for the account interface.
     *
     * It is up to the implementation to decide if the user data should be
     * totally reloaded (e.g. from the database), or if the UserInterface
     * object can just be merged into some internal array of users / identity
     * map.
     *
     * @param UserInterface $user
     *
     * @return UserInterface
     *
     * @throws UnsupportedUserException if the account is not supported
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof FrontendUser) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * Whether this provider supports the given user class.
     *
     * @param string $class
     *
     * @return bool
     */
    public function supportsClass($class)
    {
        return FrontendUser::class === $class;
    }
}