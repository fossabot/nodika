<?php
/**
 * Created by PhpStorm.
 * User: famoser
 * Date: 22/02/2018
 * Time: 11:35
 */

namespace App\Controller;

use App\Controller\Base\BaseLoginController;
use App\Entity\FrontendUser;
use App\Form\Traits\User\LoginType;
use App\Form\Traits\User\RecoverType;
use App\Form\Traits\User\SetPasswordType;
use App\Service\Interfaces\EmailServiceInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/login")
 */
class LoginController extends BaseLoginController
{
    /**
     * @Route("/", name="login_index")
     *
     * @return Response
     */
    public function indexAction()
    {
        $form = $this->createForm(LoginType::class);
        $form->add("form.login", SubmitType::class);
        $arr["form"] = $form->createView();
        return $this->render('login/index.html.twig', $arr);
    }

    /**
     * @Route("/recover", name="login_recover")
     *
     * @param Request $request
     * @param EmailServiceInterface $emailService
     * @param TranslatorInterface $translator
     * @return Response
     */
    public function recoverAction(Request $request, EmailServiceInterface $emailService, TranslatorInterface $translator)
    {
        $form = $this->handleForm(
            $this->createForm(RecoverType::class)
                ->add("form.recover", SubmitType::class),
            $request,
            function ($form) use ($emailService, $translator) {
                /* @var FormInterface $form */

                //display success
                $this->displaySuccess($translator->trans("recover.success.email_sent", [], "login"));

                //check if user exists
                $exitingUser = $this->getDoctrine()->getRepository(FrontendUser::class)->findOneBy(["email" => $form->getData()["email"]]);
                if (null === $exitingUser) {
                    return $form;
                }

                //create new reset hash
                $exitingUser->setResetHash();
                $this->fastSave($exitingUser);

                //sent according email
                $emailService->sendActionEmail(
                    $exitingUser->getEmail(),
                    $translator->trans("recover.email.reset_password.subject", [], "login"),
                    $translator->trans("recover.email.reset_password.message", [], "login"),
                    $translator->trans("recover.email.reset_password.action_text", [], "login"),
                    $this->generateUrl("frontend_login_reset", ["resetHash" => $exitingUser->getResetHash()], UrlGeneratorInterface::ABSOLUTE_URL)
                );

                return $form;
            }
        );
        $arr["form"] = $form->createView();
        return $this->render('login/recover.html.twig', $arr);
    }

    /**
     * @Route("/reset/{resetHash}", name="login_reset")
     *
     * @param Request $request
     * @param $resetHash
     * @param TranslatorInterface $translator
     * @return Response
     */
    public function resetAction(Request $request, $resetHash, TranslatorInterface $translator)
    {
        $user = $this->getDoctrine()->getRepository(FrontendUser::class)->findOneBy(["resetHash" => $resetHash]);
        if (null === $user) {
            $this->displayError($translator->trans("reset.error.invalid_hash", [], "login"));
            return $this->redirectToRoute("frontend_login_invalid", ["resetHash" => $resetHash]);
        }

        $form = $this->handleForm(
            $this->createForm(SetPasswordType::class, $user, ["data_class" => FrontendUser::class])
                ->add("form.set_password", SubmitType::class),
            $request,
            function ($form) use ($user, $translator, $request) {
                //check for valid password
                if ($user->getPlainPassword() != $user->getRepeatPlainPassword()) {
                    $this->displayError($translator->trans("reset.error.passwords_do_not_match", [], "login"));
                    return $form;
                }

                //display success
                $this->displaySuccess($translator->trans("reset.success.password_set", [], "login"));

                //set new password & save
                $user->setPassword();
                $user->setResetHash();
                $this->fastSave($user);

                //login user & redirect
                $this->loginUser($request, $user);
                return $this->redirectToRoute("index_index");
            }
        );

        if ($form instanceof Response) {
            return $form;
        }

        $arr["form"] = $form->createView();
        return $this->render('login/reset.html.twig', $arr);
    }

    /**
     * @Route("/login_check", name="login_check")
     */
    public function loginCheck()
    {
        throw new \RuntimeException('You must configure the check path to be handled by the firewall using form_login in your security firewall configuration.');
    }

    /**
     * @Route("/logout", name="login_logout")
     */
    public function logoutAction()
    {
        throw new \RuntimeException('You must configure the logout path to be handled by the firewall using form_login.logout in your security firewall configuration.');
    }
}
