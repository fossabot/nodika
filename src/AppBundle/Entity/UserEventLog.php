<?php
/**
 * Created by PhpStorm.
 * User: famoser
 * Date: 13.02.2017
 * Time: 19:54
 */

namespace AppBundle\Entity;

use AppBundle\Entity\Base\BaseEntity;
use AppBundle\Entity\Traits\IdTrait;
use Doctrine\ORM\Mapping as ORM;


/**
 * An ApplicationEvent is an event which saves the actions of the organisation
 *
 * @ORM\Table
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserEventLogRepository")
 */
class UserEventLog extends BaseEntity
{
    use IdTrait;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $key;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $value;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $occurredAtDateTime;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Person")
     */
    private $person;

    /**
     * Constructor
     */
    public function __construct()
    {
    }


    /**
     * returns a string representation of this entity
     *
     * @return string
     */
    public function getFullIdentifier()
    {
        return $this->getPerson() . " " . $this->getOccurredAtDateTime()->format("d.m.Y");
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey(string $key)
    {
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value)
    {
        $this->value = $value;
    }

    /**
     * @return \DateTime
     */
    public function getOccurredAtDateTime(): \DateTime
    {
        return $this->occurredAtDateTime;
    }

    /**
     * @param \DateTime $occurredAtDateTime
     */
    public function setOccurredAtDateTime(\DateTime $occurredAtDateTime)
    {
        $this->occurredAtDateTime = $occurredAtDateTime;
    }

    /**
     * @return Person
     */
    public function getPerson(): Person
    {
        return $this->person;
    }

    /**
     * @param Person $person
     */
    public function setPerson(Person $person)
    {
        $this->person = $person;
    }
}
