<?php
/**
 * Created by PhpStorm.
 * User: famoser
 * Date: 19/05/2017
 * Time: 19:08
 */

namespace AppBundle\Controller\Administration\Organisation\EventLine;


use AppBundle\Controller\Base\BaseController;
use AppBundle\Entity\Event;
use AppBundle\Entity\Organisation;
use AppBundle\Form\Event\ImportEventsType;
use AppBundle\Form\Event\NewEventType;
use AppBundle\Form\Generic\RemoveThingType;
use AppBundle\Helper\DateTimeFormatter;
use AppBundle\Helper\EventLineChangeHelper;
use AppBundle\Helper\StaticMessageHelper;
use AppBundle\Model\Event\ImportEventModel;
use AppBundle\Security\Voter\EventVoter;
use AppBundle\Security\Voter\OrganisationVoter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

/**
 * @Route("/events")
 * @Security("has_role('ROLE_USER')")
 */
class EventController extends BaseController
{
    /**
     * @Route("/new", name="administration_organisation_event_new")
     * @param Request $request
     * @param Organisation $organisation
     * @return Response
     */
    public function newAction(Request $request, Organisation $organisation)
    {
        $this->denyAccessUnlessGranted(OrganisationVoter::EDIT, $organisation);
        $arr = [];
        $arr["organisation"] = $organisation;

        if ($organisation->getEventLines()->count() == 0) {
            return $this->render(
                'administration/organisation/event/new.html.twig', $arr + ["no_event_lines" => true]
            );
        }

        if ($organisation->getMembers()->count() == 0) {
            return $this->render(
                'administration/organisation/event/new.html.twig', $arr + ["no_members" => true]
            );
        }

        $arr["no_event_lines"] = false;

        $event = new Event();
        $newEventForm = $this->createForm(NewEventType::class, $event, ["organisation" => $organisation]);
        $newEventForm->handleRequest($request);

        if ($newEventForm->isSubmitted()) {
            if ($newEventForm->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $eventPast = EventLineChangeHelper::createCreatedByAdminChange($event, $this->getPerson());
                $em->persist($eventPast);
                $em->persist($event);
                $em->flush();

                $this->displaySuccess($this->get("translator")->trans("successful.event_add", [], "event"));
                $newEventForm = $this->createForm(NewEventType::class, new Event(), ["organisation" => $organisation]);
            } else {
                $this->displayFormValidationError();
            }
        }

        $arr["new_event_form"] = $newEventForm->createView();
        return $this->render(
            'administration/organisation/event/new.html.twig', $arr
        );
    }

    /**
     * @Route("/{event}/edit", name="administration_organisation_event_edit")
     * @param Request $request
     * @param Organisation $organisation
     * @param Event $event
     * @return Response
     */
    public function editAction(Request $request, Organisation $organisation, Event $event)
    {
        $this->denyAccessUnlessGranted(EventVoter::EDIT, $event);

        $editEventForm = $this->createForm(NewEventType::class, $event, ["organisation" => $organisation]);
        $arr = [];
        $arr["organisation"] = $organisation;
        $arr["event"] = $event;
        $oldEvent = clone($event);

        $editEventForm->handleRequest($request);

        if ($editEventForm->isSubmitted()) {
            if ($editEventForm->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $eventPast = EventLineChangeHelper::createChangedByAdminChange($event, $oldEvent, $this->getPerson());
                $em->persist($eventPast);
                $em->persist($event);
                $em->flush();

                $this->displaySuccess($this->get("translator")->trans("successful.event_save", [], "event"));
                $editEventForm = $this->createForm(NewEventType::class, $event, ["organisation" => $organisation]);
            } else {
                $this->displayFormValidationError();
            }
        }

        $arr["edit_event_form"] = $editEventForm->createView();
        return $this->render(
            'administration/organisation/event/edit.html.twig', $arr
        );
    }

    /**
     * @Route("/{event}/remove", name="administration_organisation_event_remove")
     * @param Request $request
     * @param Organisation $organisation
     * @param Event $event
     * @return Response
     */
    public function removeAction(Request $request, Organisation $organisation, Event $event)
    {
        $this->denyAccessUnlessGranted(EventVoter::EDIT, $event);

        $removeEventForm = $this->createForm(RemoveThingType::class, $event);
        $arr = [];
        $arr["organisation"] = $organisation;
        $arr["event"] = $event;

        $removeEventForm->handleRequest($request);

        if ($removeEventForm->isSubmitted()) {
            if ($removeEventForm->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $eventPast = EventLineChangeHelper::createRemovedByAdminChange($event, $this->getPerson());
                $em->persist($eventPast);
                $em->remove($event);
                $em->flush();

                $this->displaySuccess($this->get("translator")->trans("successful.event_save", [], "event"));
                return $this->redirectToRoute("administration_organisation_events", ["organisation" => $organisation->getId()]);
            } else {
                $this->displayFormValidationError();
            }
        }

        $arr["remove_event_form"] = $removeEventForm->createView();
        return $this->render(
            'administration/organisation/event/remove.html.twig', $arr
        );
    }


    /**
     * @Route("/import/download/template", name="administration_organisation_event_import_download_template")
     * @param Request $request
     * @param Organisation $organisation
     * @return Response
     */
    public function importDownloadTemplateAction(Request $request, Organisation $organisation)
    {
        $eventTrans = $this->get("translator")->trans("event", [], "event");

        return $this->renderCsv(
            $eventTrans . ".csv",
            $this->getImportFileHeader(),
            [$this->getImportFileExampleLine()]
        );
    }

    /**
     * @return string[]
     */
    private function getImportFileHeader()
    {
        $start = $this->get("translator")->trans("start_date_time", [], "event");
        $end = $this->get("translator")->trans("end_date_time", [], "event");
        $memberId = $this->get("translator")->trans("member_id", [], "event");
        return [$start, $end, $memberId];
    }

    /**
     * @return string[]
     */
    private function getImportFileExampleLine()
    {
        $nowExample = new \DateTime();
        $endExample = new \DateTime("now + 1 day");
        return [$nowExample->format(DateTimeFormatter::DATE_TIME_FORMAT), $endExample->format(DateTimeFormatter::DATE_TIME_FORMAT), 1];
    }

    /**
     * @Route("/import", name="administration_organisation_event_import")
     * @param Request $request
     * @param Organisation $organisation
     * @return Response
     */
    public function importAction(Request $request, Organisation $organisation)
    {
        $this->denyAccessUnlessGranted(OrganisationVoter::EDIT, $organisation);

        $members = $this->getDoctrine()->getRepository("AppBundle:Member")->getIdAssociatedArray($organisation);

        $arr = [];
        $arr["members"] = $members;

        $importFileModel = new ImportEventModel("/img/import");
        $importEventsForm = $this->createForm(ImportEventsType::class, $importFileModel, ["organisation" => $organisation]);
        $importEventsForm->handleRequest($request);

        if ($importEventsForm->isSubmitted()) {
            if ($importEventsForm->isValid()) {
                $exchangeService = $this->get("app.exchange_service");
                $eventLine = $importFileModel->getEventLine();
                if ($exchangeService->importCsvAdvanced(function ($data) use ($organisation, $members, $eventLine) {
                    $event = new Event();
                    $event->setStartDateTime(new \DateTime($data[0]));
                    $event->setEndDateTime(new \DateTime($data[1]));
                    if (isset($members[$data[2]])) {
                        $event->setMember($members[$data[2]]);
                    } else {
                        return null;
                    }
                    $event->setEventLine($eventLine);
                    return $event;
                }, function ($header) use ($organisation) {
                    $expectedHeader = $this->getImportFileHeader();
                    for ($i = 0; $i < count($header); $i++) {
                        if ($expectedHeader[$i] != $header[$i]) {
                            $this->get("session.flash_bag")->set(StaticMessageHelper::FLASH_ERROR, $this->get("translator")->trans("error.file_upload_failed", [], "import"));
                            return false;
                        }
                    }
                    return true;
                }, $importFileModel)
                ) {
                    $importEventsForm = $this->createForm(ImportEventsType::class, $importFileModel, ["organisation" => $organisation]);
                    $this->displaySuccess($this->get("translator")->trans("success.import_successful", [], "import"));
                }
            } else {
                $this->displayFormValidationError();
            }
        }

        $arr["import_events_form"] = $importEventsForm->createView();

        return $this->render(
            'administration/organisation/event/import.html.twig', $arr + ["organisation" => $organisation]
        );
    }
}