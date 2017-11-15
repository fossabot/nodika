<?php
/**
 * Created by PhpStorm.
 * User: famoser
 * Date: 28/12/2016
 * Time: 01:58
 */

namespace AppBundle\Controller;


use AppBundle\Controller\Administration\Organisation\EventLine\Generate\RoundRobinController;
use AppBundle\Controller\Base\BaseController;
use AppBundle\Entity\Event;
use AppBundle\Entity\EventLineGeneration;
use AppBundle\Entity\FrontendUser;
use AppBundle\Entity\Newsletter;
use AppBundle\Entity\Organisation;
use AppBundle\Entity\Person;
use AppBundle\Entity\UserEventLog;
use AppBundle\Enum\DistributionType;
use AppBundle\Form\Newsletter\RegisterForPreviewType;
use AppBundle\Model\EventLineGeneration\Nodika\NodikaConfiguration;
use AppBundle\Model\EventLineGeneration\RoundRobin\RoundRobinConfiguration;
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
     * @Route("/analyze/all", name="static_analyze_all")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function analyzeAllAction(Request $request)
    {
        /* @var UserEventLog[] $logs */
        $logs = $this->getDoctrine()->getRepository("AppBundle:UserEventLog")->findBy([], ["occurredAtDateTime" => "ASC"]);
        /* @var Event[] $events */
        $eventLines = $this->getDoctrine()->getRepository("AppBundle:EventLine")->findBy([]);
        /* @var UserEventLog[][] $logsByPerson */
        $logsByPerson = [];
        foreach ($logs as $log) {
            $logsByPerson[$log->getPerson()->getId()][] = $log;
        }

        /* @var Event[][] $eventsByEventLine */
        $eventsByEventLine = [];
        foreach ($eventLines as $eventLine) {
            $eventsByEventLine[$eventLine->getId()] = $eventLine->getEvents();
        }

        $data = [];
        foreach ($this->getDoctrine()->getRepository("AppBundle:Person")->findAll() as $person) {
            $row = [];
            $row[] = $person->getId();
            $row[] = $person->getStudyGroup();

            if (count($person->getLeaderOf()) > 0) {
                /* @var Organisation $organisation */
                $organisation = $person->getLeaderOf()->get(0);
                $row[] = $organisation->getId();

                /* @var UserEventLog[¨$logs */
                if (!isset($logsByPerson[$person->getId()])) {
                    continue;
                }

                $logsOfPerson = $logsByPerson[$person->getId()];
                $setupStart = null;
                $firstMember = null;
                $lastMember = null;
                $firstEventLine = null;
                $lastEventLine = null;
                $firstGeneration = null;
                $lastGeneration = null;
                $setupEnd = null;
                foreach ($logsOfPerson as $log) {
                    if (strpos($log->getValue(), "/setup") > 0) {
                        if ($setupStart == null) {
                            $setupStart = $log;
                        }
                        $setupEnd = $log;
                    }
                    if (
                        (strpos($log->getValue(), "/members/new") > 0) ||
                        (strpos($log->getValue(), "/members/") > 0 && strpos($log->getValue(), "/administer"))
                    ) {
                        if ($firstMember == null) {
                            $firstMember = $log;
                        }
                        $lastMember = $log;
                    } else if (
                        (strpos($log->getValue(), "/event_line/new") > 0) ||
                        (strpos($log->getValue(), "/event_line/") > 0 && strpos($log->getValue(), "/administer"))
                    ) {
                        if ($firstEventLine == null) {
                            $firstEventLine = $log;
                        }
                        $lastEventLine = $log;
                    } else if (strpos($log->getValue(), "/generate") > 0) {
                        if ($firstGeneration == null) {
                            $firstGeneration = $log;
                        }
                        $lastGeneration = $log;
                    } else if (strpos($log->getValue(), "/finish") > 0) {
                        $setupEnd = $log;
                        break;
                    }
                }

                if ($setupStart == null || $setupEnd == null ||
                    $firstMember == null || $lastMember == null ||
                    $firstEventLine == null || $lastEventLine == null ||
                    $firstGeneration == null || $lastGeneration == null) {
                    continue;
                }


                $row[] = $setupEnd->getOccurredAtDateTime()->getTimestamp() - $setupStart->getOccurredAtDateTime()->getTimestamp();
                $row[] = $lastMember->getOccurredAtDateTime()->getTimestamp() - $firstMember->getOccurredAtDateTime()->getTimestamp();
                $row[] = $lastEventLine->getOccurredAtDateTime()->getTimestamp() - $firstEventLine->getOccurredAtDateTime()->getTimestamp();
                $row[] = $lastGeneration->getOccurredAtDateTime()->getTimestamp() - $firstGeneration->getOccurredAtDateTime()->getTimestamp();

                $row[] = count($organisation->getMembers());

                $generations = [];
                $eventLineIds = [];
                foreach ($organisation->getEventLines() as $eventLine) {
                    $generations = array_merge($eventLine->getEventLineGenerations()->toArray(), $generations);
                    $eventLineIds[] = $eventLine->getId();
                }
                $row[] = count($eventLineIds);
                $row[] = count($generations);

                if (count($generations) > 0) {
                    /* @var EventLineGeneration $lastGeneration */
                    $lastGeneration = $generations[count($generations) - 1];
                    if ($lastGeneration->getDistributionType() == DistributionType::NODIKA) {
                        $config = new NodikaConfiguration(json_decode($lastGeneration->getDistributionConfigurationJson()));
                        $row[] = "nodika";
                    } else {
                        $config = new RoundRobinConfiguration(json_decode($lastGeneration->getDistributionConfigurationJson()));
                        $row[] = "round robin";
                    }
                    $row[] = $config->lengthInHours;
                    $row[] = $config->startDateTime->format("d.m.Y H:s");
                    $row[] = $config->endDateTime->format("d.m.Y H:s");

                    $decemberStartDate = new \DateTime("01.12.2017 00:01");
                    $decemberEndDate = new \DateTime("31.12.2017 23:59");
                    $decemberRandomDates = [new \DateTime("10.12.2017 00:00"), new \DateTime("15.12.2017 00:00"), new \DateTime("20.12.2017 00:00")];
                    $decemberStart = false;
                    $decemberEnd = false;
                    $changeAtEight = true;
                    $anyChangeAtEight = false;
                    foreach ($eventLineIds as $eventLineId) {
                        foreach ($eventsByEventLine[$eventLineId] as $event) {
                            if ($event->getStartDateTime() < $decemberStartDate && $event->getEndDateTime() > $decemberStartDate) {
                                $decemberStart = true;
                            } else if ($event->getStartDateTime() < $decemberEndDate && $event->getEndDateTime() > $decemberEndDate) {
                                $decemberEnd = true;
                            }

                            if ($event->getEndDateTime() > $decemberStartDate && $event->getEndDateTime() < $decemberEndDate) {
                                $changeAtEight &= $event->getEndDateTime()->format("H:i") == "08:00";
                                $anyChangeAtEight = true;
                            }
                            if ($event->getStartDateTime() > $decemberStartDate && $event->getStartDateTime() < $decemberEndDate) {
                                $changeAtEight &= $event->getStartDateTime()->format("H:i") == "08:00";
                                $anyChangeAtEight = true;
                            }

                            for ($i = 0; $i < count($decemberRandomDates); $i++) {
                                if ($event->getEndDateTime() > $decemberRandomDates[$i] && $event->getStartDateTime() < $decemberRandomDates[$i]) {
                                    unset($decemberRandomDates[$i]);
                                    break;
                                }
                            }
                            $decemberRandomDates = array_values($decemberRandomDates);
                        }
                    }

                    $row[] = $decemberStart ? "yes" : "no";
                    $row[] = $decemberEnd ? "yes" : "no";
                    $row[] = count($decemberRandomDates) == 0 ? "yes" : "no";
                    $row[] = $changeAtEight && $anyChangeAtEight ? "yes" : "no";

                    $data[] = $row;
                }
            }
        }

        return $this->renderCsv("summary.csv", [
            "person id",
            "study group",
            "organisation id",
            "start zu end zeit",
            "praxiserfassung zeit",
            "termingruppenzeit",
            "generierungszeit",
            "anzahl praxen erfasst",
            "anzahl termingruppen erfasst",
            "anzahl generierungen",
            "used algorithm",
            "used duration",
            "used start",
            "used end",
            "covered december start",
            "covered december end",
            "covered all of december",
            "changed at 8:00"
        ], $data);
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