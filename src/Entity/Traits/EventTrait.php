<?php
/**
 * Created by PhpStorm.
 * User: famoser
 * Date: 4/2/18
 * Time: 10:10 AM
 */

namespace App\Entity\Traits;

use App\Entity\Clinic;
use App\Entity\Doctor;
use App\Entity\EventGeneration;
use App\Enum\EventType;
use App\Enum\TradeTag;
use Doctrine\ORM\Mapping as ORM;

trait EventTrait
{
    use StartEndTrait;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $confirmDateTime = null;

    /**
     * @var Doctor|null
     *
     * @ORM\ManyToOne(targetEntity="Doctor")
     */
    private $confirmedBy = null;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastRemainderEmailSent = null;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $tradeTag = TradeTag::MAYBE_TRADE;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $eventType = EventType::UNSPECIFIED;

    /**
     * @var Clinic
     *
     * @ORM\ManyToOne(targetEntity="Clinic", inversedBy="events")
     */
    private $clinic;

    /**
     * @var Doctor|null
     *
     * @ORM\ManyToOne(targetEntity="Doctor", inversedBy="events")
     */
    private $doctor;

    /**
     * @var EventGeneration|null
     *
     * @ORM\ManyToOne(targetEntity="EventGeneration", inversedBy="generatedEvents")
     */
    private $generatedBy;

    /**
     * @return int
     */
    public function getEventType(): int
    {
        return $this->eventType;
    }

    /**
     * @param int $eventType
     */
    public function setEventType(int $eventType): void
    {
        $this->eventType = $eventType;
    }

    /**
     * @return \DateTime|null
     */
    public function getConfirmDateTime(): ?\DateTime
    {
        return $this->confirmDateTime;
    }

    /**
     * @return Doctor|null
     */
    public function getConfirmedBy(): ?Doctor
    {
        return $this->confirmedBy;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastRemainderEmailSent(): ?\DateTime
    {
        return $this->lastRemainderEmailSent;
    }

    /**
     * @param \DateTime|null $lastRemainderEmailSent
     */
    public function setLastRemainderEmailSent(?\DateTime $lastRemainderEmailSent): void
    {
        $this->lastRemainderEmailSent = $lastRemainderEmailSent;
    }

    /**
     * @return int
     */
    public function getTradeTag(): int
    {
        return $this->tradeTag;
    }

    /**
     * @param int $tradeTag
     */
    public function setTradeTag(int $tradeTag): void
    {
        $this->tradeTag = $tradeTag;
    }

    /**
     * @return Clinic|null
     */
    public function getClinic(): ?Clinic
    {
        return $this->clinic;
    }

    /**
     * @param Clinic|null $clinic
     */
    public function setClinic(?Clinic $clinic): void
    {
        $this->clinic = $clinic;
    }

    /**
     * @return Doctor|null
     */
    public function getDoctor(): ?Doctor
    {
        return $this->doctor;
    }

    /**
     * @param Doctor|null $doctor
     */
    public function setDoctor(?Doctor $doctor): void
    {
        $this->doctor = $doctor;
    }

    /**
     * @return EventGeneration|null
     */
    public function getGeneratedBy(): ?EventGeneration
    {
        return $this->generatedBy;
    }

    /**
     * @param EventGeneration|null $generatedBy
     */
    public function setGeneratedBy(?EventGeneration $generatedBy): void
    {
        $this->generatedBy = $generatedBy;
    }

    /**
     * @return bool
     */
    public function isConfirmed()
    {
        return
            $this->getConfirmDateTime() != null &&
            (
                $this->getDoctor() == null ||
                ($this->getConfirmedBy() != null && $this->getConfirmedBy()->getId() == $this->getDoctor()->getId())
            );
    }

    public function confirm(Doctor $user)
    {
        $this->confirmDateTime = new \DateTime();
        $this->confirmedBy = $user;
    }

    public function undoConfirm()
    {
        $this->confirmedBy = null;
        $this->confirmDateTime = null;
    }

    /**
     * @param EventTrait $eventTrait
     */
    protected function writeValues($eventTrait)
    {
        $this->setStartDateTime($eventTrait->getStartDateTime());
        $this->setEndDateTime($eventTrait->getEndDateTime());
        $this->confirmDateTime = $eventTrait->getConfirmDateTime();
        $this->confirmedBy = $eventTrait->getConfirmedBy();
        $this->lastRemainderEmailSent = $eventTrait->getLastRemainderEmailSent();
        $this->eventType = $this->getEventType();
        $this->tradeTag = $eventTrait->getTradeTag();
        $this->clinic = $eventTrait->getClinic();
        $this->doctor = $eventTrait->getDoctor();
        $this->generatedBy = $eventTrait->getGeneratedBy();
    }

    public function isActive()
    {
        $now = new \DateTime();
        return $this->getStartDateTime() < $now && $this->getEndDateTime() > $now;
    }
}
