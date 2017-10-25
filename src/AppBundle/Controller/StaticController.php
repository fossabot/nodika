<?php
/**
 * Created by PhpStorm.
 * User: famoser
 * Date: 28/12/2016
 * Time: 01:58
 */

namespace AppBundle\Controller;


use AppBundle\Controller\Base\BaseController;
use AppBundle\Entity\FrontendUser;
use AppBundle\Entity\Newsletter;
use AppBundle\Entity\Organisation;
use AppBundle\Entity\Person;
use AppBundle\Entity\UserEventLog;
use AppBundle\Form\Newsletter\RegisterForPreviewType;
use Faker\Factory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;


class StaticController extends BaseController
{
    /**
     * @Route("/", name="homepage")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $arr = [];
        $arr["is_logged_in"] = $this->getUser() instanceof FrontendUser;

        $form = $this->handleFormDoctrinePersist(
            $this->createForm(RegisterForPreviewType::class),
            $request,
            new Newsletter(),
            function ($form, $entity) {
                /* @var FormInterface $form */
                /* @var Newsletter $entity */

                $message = \Swift_Message::newInstance()
                    ->setSubject("Nachricht auf nodika")
                    ->setFrom($this->getParameter("mailer_email"))
                    ->setTo($this->getParameter("contact_email"))
                    ->setBody("Sie haben eine Kontaktanfrage auf nodika erhalten: \n" .
                        "\nListe: " . $entity->getChoice() .
                        "\nEmail: " . $entity->getEmail() .
                        "\nVorname: " . $entity->getGivenName() .
                        "\nNachname: " . $entity->getFamilyName() .
                        "\nNachricht: " . $entity->getMessage());
                $this->get('mailer')->send($message);

                $translator = $this->get("translator");

                $this->displaySuccess($translator->trans("index.thanks_for_contact_form", [], "static"));
                return $form;
            }
        );
        $arr["newsletter_form"] = $form->createView();


        return $this->renderNoBackUrl(
            'static/index.html.twig', $arr, "this is the homepage"
        );
    }

    /**
     * @Route("/start", name="study_entry_point")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function startAction(Request $request)
    {
        $id = uniqid();
        $email = $id . "@nodika.ch";
        $faker = Factory::create("de_CH");

        $person = new Person();
        $person->setEmail($email);
        $person->setGivenName($faker->name);
        $person->setFamilyName($faker->lastName);

        $user = FrontendUser::createFromPerson($person);
        $user->setEmail($email);

        $organisation = new Organisation();
        $organisation->setName("Notfalldienstverbund Zürich-West");
        $organisation->setDescription("Fitiker Notfalldienstverband");
        $organisation->setEmail($email);
        $organisation->setActiveEnd(new \DateTime());
        $organisation->setIsActive(true);
        $person->addLeaderOf($organisation);
        $organisation->addLeader($person);

        $manager = $this->getDoctrine()->getManager();
        $manager->persist($person);
        $manager->persist($user);
        $manager->persist($organisation);
        $manager->flush();


        //login programmatically
        $token = new UsernamePasswordToken($user, $user->getPassword(), "main", $user->getRoles());
        $this->get("security.token_storage")->setToken($token);

        $event = new InteractiveLoginEvent($request, $token);
        $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

        return $this->redirectToRoute("administration_organisation_setup", ["organisation" => $organisation->getId()]);
    }

    /**
     * @Route("/analyze/{person}", name="static_analyze")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function analyzeAction(Request $request, Person $person)
    {
        /* @var UserEventLog[¨$logs */
        $logs = $this->getDoctrine()->getRepository("AppBundle:UserEventLog")->findBy(["person" => $person->getId()], ["occurredAtDateTime" => "ASC"]);

        return $this->renderNoBackUrl(
            'static/analyze.html.twig', ["logs" => $logs], "this is the homepage"
        );
    }
}