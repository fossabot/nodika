<?php

/*
 * This file is part of the nodika project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use App\DataFixtures\Base\BaseFixture;
use App\Entity\EventGenerationDateException;
use App\Entity\EventGenerationFrontendUser;
use App\Entity\EventGenerationMember;
use App\Entity\EventLine;
use App\Entity\EventGeneration;
use App\Entity\FrontendUser;
use App\Entity\Member;
use App\Entity\Settings;
use App\Enum\EventType;
use App\Enum\GenerationStatus;
use App\Helper\DateTimeFormatter;
use Doctrine\Common\Persistence\ObjectManager;

class LoadGeneration extends BaseFixture
{
    const ORDER = LoadSettings::ORDER + LoadMember::ORDER + LoadFrontendUser::ORDER + LoadEventLine::ORDER;

    /**
     * Load data fixtures with the passed EntityManager.
     *
     * @param ObjectManager $manager
     *
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {

        $eventLines = $manager->getRepository(EventLine::class)->findAll();
        foreach ($eventLines as $eventLine) {
            $generation = new EventGeneration();
            $generation->setName("example generation at " . (new \DateTime())->format(DateTimeFormatter::DATE_TIME_FORMAT));
            $generation->setDifferentiateByEventType(false);
            $generation->setStartDateTime(new \DateTime());
            $generation->setEndDateTime(new \DateTime("now + 1 year"));
            $generation->setStartCronExpression("0 8 * * *");
            $generation->setEndCronExpression("0 8 * * *");
            $generation->setEventLine($eventLine);

            //date exceptions
            $dateExceptions = [
                [EventType::HOLIDAYS, EventType::HOLIDAYS, EventType::HOLIDAYS, EventType::HOLIDAYS, EventType::HOLIDAYS, EventType::HOLIDAYS]
            ];
            foreach ($dateExceptions as $dateException) {
                $exception = new EventGenerationDateException();
                $this->fillStartEnd($exception);
                $exception->setEventType($dateException[0]);
                $exception->setEventGeneration($generation);
            }

            $setting = $manager->getRepository(Settings::class)->findSingle();

            if ($setting->getUseMembers()) {
                $members = $manager->getRepository(Member::class)->findAll();
                $skipPossibility = count($members);

                foreach ($members as $member) {
                    if (rand(0, $skipPossibility) !== 0) {
                        $target = new EventGenerationMember();
                        $target->setEventGeneration($generation);
                        $target->setMember($member);
                        $manager->persist($target);
                    }
                }
            } else {
                $frontendUsers = $manager->getRepository(FrontendUser::class)->findAll();
                $skipPossibility = count($frontendUsers);

                foreach ($frontendUsers as $frontendUser) {
                    if (rand(0, $skipPossibility) !== 0) {
                        $target = new EventGenerationFrontendUser();
                        $target->setEventGeneration($generation);
                        $target->setFrontendUser($frontendUser);
                        $manager->persist($target);
                    }
                }
            }

            $manager->persist($generation);
            $manager->flush();

            $events = $this->getEventGenerationService()->generate($generation);
            foreach ($events as $event) {
                $manager->persist($event);
            }
            $manager->flush();
        }
    }

    /**
     * Get the order of this fixture.
     *
     * @return int
     */
    public function getOrder()
    {
        return static::ORDER;
    }

    /**
     * create an instance with all random values.
     *
     * @return EventLine
     */
    protected function getRandomInstance()
    {
        $eventLine = new EventLine();
        $this->fillThing($eventLine);

        return $eventLine;
    }
}