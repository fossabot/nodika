<?php

/*
 * This file is part of the nodika project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Controller\Base\BaseFormController;
use App\Controller\Traits\EventControllerTrait;
use App\Entity\Event;
use App\Entity\EventPast;
use App\Entity\Setting;
use App\Enum\EventChangeType;
use App\Model\Event\SearchModel;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/confirm")
 * @Security("has_role('ROLE_USER')")
 */
class ConfirmController extends BaseFormController
{
    use EventControllerTrait;

    /**
     * @Route("/api/events", name="confirm_events")
     *
     * @param SerializerInterface $serializer
     * @return JsonResponse
     * @throws \Exception
     */
    public function apiEventsAction(SerializerInterface $serializer)
    {
        //get all assignable events
        $settings = $this->getDoctrine()->getRepository(Setting::class)->findSingle();

        $searchModel = new SearchModel(SearchModel::NONE);
        $searchModel->setClinics($this->getUser()->getClinics());
        $searchModel->setEndDateTime((new \DateTime())->add(new \DateInterval("P" . $settings->getCanConfirmDaysAdvance() . "D")));
        $searchModel->setIsConfirmed(false);
        $events = $this->getDoctrine()->getRepository(Event::class)->search($searchModel);

        $apiEvents = [];
        foreach ($events as $event) {
            if ($event->getDoctor() == null || $event->getDoctor()->getId() == $this->getUser()->getId()) {
                $apiEvents[] = $event;
            }
        }

        return new JsonResponse($serializer->serialize($apiEvents, "json", ["attributes" => ["id", "startDateTime", "endDateTime", "clinic" => ["name"], "doctor" => ["id", "fullName"]]]), 200, [], true);
    }

    /**
     * @Route("/api/event/{event}", name="confirm_event")
     *
     * @param Event $event
     * @return Response
     */
    public function apiConfirmAction(Event $event)
    {
        //either assigned to this user or of a clinic the user is part of
        if ($event->getDoctor() != null && $event->getDoctor()->getId() == $this->getUser()->getId() ||
            $this->getUser()->getClinics()->contains($event->getClinic())) {
            $event->confirm($this->getUser());
            $eventPast = EventPast::create($event, EventChangeType::CONFIRMED_BY_PERSON, $this->getUser());
            $this->fastSave($event, $eventPast);
            return new Response("ACK");
        }

        return new Response("NACK");
    }
}
