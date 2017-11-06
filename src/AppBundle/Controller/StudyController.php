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


class StudyController extends BaseController
{
    /**
     * @Route("/group/{id}", name="study_set_group")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function groupAction(Request $request, $id)
    {
        $request->getSession()->set("group", $id);

        return $this->renderNoBackUrl(
            'static/study_start.twig', ["group" => $id], "this is the homepage"
        );
    }

    /**
     * @Route("/demo/start", name="study_start_demo")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function demoStartAction(Request $request)
    {
        $request->getSession()->set("beta", "active");

        return $this->redirectToRoute("homepage");
    }

    /**
     * @Route("/demo/end", name="study_end_demo")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function demoEndAction(Request $request)
    {
        $request->getSession()->remove("beta");

        return $this->redirectToRoute("homepage");
    }

    /**
     * @Route("/start", name="study_entry_point")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function startAction(Request $request)
    {
        if (!$request->getSession()->has("group")) {
            return $this->redirectToRoute("homepage");
        }

        $id = uniqid();
        $email = $id . "@nodika.ch";
        $faker = Factory::create("de_CH");

        $person = new Person();
        $person->setEmail($email);
        $person->setGivenName($faker->name);
        $person->setFamilyName($faker->lastName);
        $person->setStudyGroup((int)$request->getSession()->get("group"));

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
     * @Route("/finish/{id}", name="study_exit_point")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function stopAction(Request $request, $id)
    {
        $arr["person_id"] = $id;
        return $this->renderNoBackUrl(
            'static/study_done.html.twig',
            $arr,
            $this->generateUrl("homepage")
        );
    }

    /**
     * @Route("/analyze/{person}", name="static_analyze")
     * @param Request $request
     * @param Person $person
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function analyzeAction(Request $request, Person $person)
    {
        $arr = [];
        /* @var UserEventLog[¨$logs */
        $logs = $this->getDoctrine()->getRepository("AppBundle:UserEventLog")->findBy(["person" => $person->getId()], ["occurredAtDateTime" => "ASC"]);
        $arr["logs"] = $logs;
        $arr["person"] = $person;

        $first = $logs[0];
        $last = null;
        foreach ($logs as $log) {
            if (strpos($log->getValue(), "/finish") > 0) {
                $last = $log;
                break;
            }
        }
        $arr["last"] = $last;
        $arr["first"] = $first;
        if ($last != null)
            $arr["length"] = $last->getOccurredAtDateTime()->getTimestamp() - $first->getOccurredAtDateTime()->getTimestamp();

        $organisation = $person->getLeaderOf()[0];
        $arr["organisation"] = $organisation;
        $arr["members"] = $organisation->getMembers();
        $arr["event_lines"] = $organisation->getEventLines();
        $events = [];
        foreach ($organisation->getEventLines() as $eventLine) {
            $events = array_merge($events, $eventLine->getEvents()->toArray());
        }
        $arr["events"] = $events;

        return $this->renderNoBackUrl(
            'static/analyze.html.twig', $arr, "this is the homepage"
        );
    }
}