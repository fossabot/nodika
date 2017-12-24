<?php

/*
 * This file is part of the nodika project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Base;

use App\Entity\Traits\UserTrait;
use App\Helper\HashHelper;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class BaseAccessController extends BaseFrontendController
{
    /**
     * @param Request       $request
     * @param UserTrait     $user
     * @param FormInterface $loginForm
     *
     * @return FormInterface
     */
    protected function getLoginForm(Request $request, $user, FormInterface $loginForm)
    {
        /** @var $session \Symfony\Component\HttpFoundation\Session\Session */
        $session = $request->getSession();

        $authErrorKey = Security::AUTHENTICATION_ERROR;
        // get the error if any (works with forward and redirect -- see below)
        if ($request->attributes->has($authErrorKey)) {
            $error = $request->attributes->get($authErrorKey);
        } elseif (null !== $session && $session->has($authErrorKey)) {
            $error = $session->get($authErrorKey);
            $session->remove($authErrorKey);
        } else {
            $error = null;
        }
        if (null !== $error) {
            $this->displayError($this->get('translator')->trans('error.login_failed', [], 'access'));
        }

        // last username entered by the user
        $lastUsername = (null === $session) ? '' : $session->get(Security::LAST_USERNAME);
        $user->setEmail($lastUsername);

        $loginForm->handleRequest($request);

        if ($loginForm->isSubmitted()) {
            throw new \RuntimeException('You must configure the check path to be handled by the firewall using form_login in your security firewall configuration.');
        }

        return $loginForm;
    }

    /**
     * @param Request          $request
     * @param Form             $registerForm
     * @param UserTrait        $user
     * @param EntityRepository $repository
     * @param callable         $beforeInsertCallback
     * @param callable         $generateRegisterConfirmLink
     * @param callable         $generateRegisterThanksLink
     *
     * @return Form
     */
    protected function getRegisterForm(Request $request, $registerForm, $user, $repository, $beforeInsertCallback, $generateRegisterConfirmLink, $generateRegisterThanksLink)
    {
        $registerForm->setData($user);
        $registerForm->handleRequest($request);

        if ($registerForm->isSubmitted()) {
            if ($registerForm->isValid()) {
                if ($beforeInsertCallback($user)) {
                    $existingUser = $repository->findOneBy(['email' => $user->getEmail()]);
                    /* @var $existingUser UserTrait */
                    if (null !== $existingUser) {
                        $this->displayError($this->get('translator')->trans('error.email_already_registered', [], 'access'));
                    } else {
                        if (!$user->isValidPlainPassword()) {
                            $this->displayError($this->get('translator')->trans('error.new_password_not_valid', [], 'access'));
                        } else {
                            $user->persistNewPassword();
                            $user->setResetHash(HashHelper::createNewResetHash());
                            $user->setRegistrationDate(new \DateTime());

                            $this->fastSave($user);

                            $translator = $this->get('translator');
                            $subject = $translator->trans('register.subject', [], 'email_access');
                            $receiver = $user->getEmail();
                            $body = $translator->trans('register.message', [], 'email_access');
                            $actionText = $translator->trans('register.action_text', [], 'email_access');
                            $actionLink = $generateRegisterConfirmLink($user);
                            $this->get('app.email_service')->sendActionEmail($receiver, $subject, $body, $actionText, $actionLink);

                            return $generateRegisterThanksLink($user);
                        }
                    }
                }
            } else {
                $this->displayFormValidationError();
            }
        }

        return $registerForm;
    }

    /**
     * @param Request          $request
     * @param EntityRepository $repository
     * @param callable         $generateResetLink
     * @param callable         $generateResetDoneLink
     *
     * @return \Symfony\Component\Form\FormInterface|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function getResetForm(Request $request, $repository, $generateResetLink, $generateResetDoneLink)
    {
        $resetForm = $this->get('form.factory')->createNamedBuilder(
            null,
            FormType::class,
            [],
            ['translation_domain' => 'access']
        )
            ->add('email', EmailType::class)
            ->add('reset', SubmitType::class)
            ->getForm();

        $resetForm->handleRequest($request);

        if ($resetForm->isSubmitted()) {
            if ($resetForm->isValid()) {
                /* @var $existingUser UserTrait */
                $existingUser = $repository->findOneBy(['email' => $resetForm->get('email')->getData()]);
                if (null !== $existingUser) {
                    $existingUser->setResetHash(HashHelper::createNewResetHash());

                    $this->fastSave($existingUser);

                    $translator = $this->get('translator');
                    $subject = $translator->trans('reset.subject', [], 'email_access');
                    $receiver = $existingUser->getEmail();
                    $body = $translator->trans('reset.message', [], 'email_access');
                    $actionText = $translator->trans('reset.action_text', [], 'email_access');
                    $actionLink = $generateResetLink($existingUser);
                    $this->get('app.email_service')->sendActionEmail($receiver, $subject, $body, $actionText, $actionLink);
                }

                return $generateResetDoneLink($existingUser);
            }
            $this->displayFormValidationError();
        }

        return $resetForm;
    }

    /**
     * @param Request          $request
     * @param EntityRepository $repository
     * @param string           $confirmationToken
     * @param Form             $setPasswordForm
     *
     * @return bool|Form|Response
     */
    protected function processConfirmationToken(Request $request, $repository, $confirmationToken, $setPasswordForm)
    {
        /* @var $user AdvancedUserInterface|UserTrait */
        $user = $repository->findOneBy(['resetHash' => $confirmationToken]);
        if (null === $user) {
            return $this->renderNoBackUrl(
                'access/hash_invalid.html.twig',
                [],
                'no confirmation token'
            );
        }
        $setPasswordForm->setData($user);
        $setPasswordForm->handleRequest($request);

        if ($setPasswordForm->isSubmitted()) {
            if ($setPasswordForm->isValid()) {
                if ($user->isValidPlainPassword()) {
                    if ($user->getPlainPassword() === $user->getRepeatPlainPassword()) {
                        $user->persistNewPassword();
                        $user->setResetHash(HashHelper::createNewResetHash());

                        $em = $this->getDoctrine()->getManager();
                        $em->persist($user);
                        $em->flush();

                        //login programmatically
                        $token = new UsernamePasswordToken($user, $user->getPassword(), 'main', $user->getRoles());
                        $this->get('security.token_storage')->setToken($token);

                        $event = new InteractiveLoginEvent($request, $token);
                        $this->get('event_dispatcher')->dispatch('security.interactive_login', $event);

                        return true;
                    }
                    $this->displayError($this->get('translator')->trans('error.passwords_do_not_match', [], 'access'));
                } else {
                    $this->displayError($this->get('translator')->trans('error.new_password_not_valid', [], 'access'));
                }
            } else {
                $this->displayFormValidationError();
            }
        }

        return $setPasswordForm;
    }

    /**
     * @param AdvancedUserInterface $user
     * @param Request               $request
     */
    protected function loginUser(Request $request, AdvancedUserInterface $user)
    {
        //login programmatically
        $token = new UsernamePasswordToken($user, $user->getPassword(), 'main', $user->getRoles());
        $this->get('security.token_storage')->setToken($token);

        $event = new InteractiveLoginEvent($request, $token);
        $this->get('event_dispatcher')->dispatch('security.interactive_login', $event);
    }
}
