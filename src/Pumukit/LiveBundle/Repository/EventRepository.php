<?php

namespace Pumukit\LiveBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * EventRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EventRepository extends DocumentRepository
{
    /**
     * Find next events
     */
    public function findNextEvents()
    {
        $now = new \DateTime("now");

        return $this->createQueryBuilder()
            ->field('display')->equals(true)
            ->field('date')->gte($now)
            ->sort('date', 1)
            ->getQuery()->execute();
    }

    /**
     * Find next event
     */
    public function findNextEvent()
    {
        $now = new \DateTime("now");

        return $this->createQueryBuilder()
            ->field('display')->equals(true)
            ->field('date')->gte($now)
            ->sort('date', 1)
            ->getQuery()->getSingleResult();
    }

    /**
     * Find current events
     */
    public function findCurrentEvents($limit = null)
    {
        $dmColl = $this->dm->getDocumentCollection("PumukitLiveBundle:Event");

        $now = new \MongoDate();
        $pipeline = array(
            array('$match' => array('display'=> true)),
            array('$project' => array('date'=> true, 'end'=> array('$add'=> array('$date', array('$multiply'=> array('$duration', 60000)))))),
            array('$match' => array('$and' => array( array('date'=> array('$lte'=> $now)), array('end' =>  array('$gte' => $now)))))
        );

        if ($limit) {
            $pipeline[] = array('$limit' => $limit);
        }
        $aggregation = $dmColl->aggregate($pipeline);

        if (0 === $aggregation->count()) {
            return array();
        }

        $ids = array_map(function($e){return $e['_id'];}, $aggregation->toArray());

        return $this->createQueryBuilder()
            ->field('_id')->in($ids)
            ->getQuery()->execute();

    }

    /**
     * Find future and not finished
     *
     * @param integer $limit
     * @param Date $date
     * @return Cursor
     */
    public function findFutureAndNotFinished($limit=null, $date=null)
    {
        // First: look if there is a current live event broadcasting
        // for setting datetime minus duration
        if (!$date) {
            $currentDatetime = new \DateTime("now");
            $startDay = new \DateTime("now");
            $finishDay = new \DateTime("now");
        } else {
            $currentDatetime = new \DateTime($date->format('Y-m-d H:s:i'));
            $startDay = new \DateTime($date->format('Y-m-d H:s:i'));
            $finishDay = new \DateTime($date->format('Y-m-d H:s:i'));
        }
        $startDay->setTime(0, 0, 0);
        $finishDay->setTime(23, 59, 59);

        $currentDayEvents = $this->createQueryBuilder()
            ->field('display')->equals(true)
            ->field('date')->gte($startDay)
            ->field('date')->lte($finishDay)
            ->sort('date', 1)
            ->getQuery()->execute();

        $duration = 0;
        foreach ($currentDayEvents as $event) {
            $eventDate = new \DateTime($event->getDate()->format("Y-m-d H:i:s"));
            if (($eventDate < $currentDatetime) && ($currentDatetime < $eventDate->add(new \DateInterval('PT'.$event->getDuration().'M')))) {
                $duration = $event->getDuration();
            }
        }
        $currentDatetime->sub(new \DateInterval('PT'.$duration.'M'));

        // Second: look for current and next events
        $qb = $this->createQueryBuilder()
            ->field("display")->equals(true)
            ->field("date")->gte($currentDatetime)
            ->sort("date", 1);

        if ($limit) $qb->limit($limit);

        return $qb->getQuery()->execute();
    }

    /**
     * Find one by hours event
     *
     * @param integer $hours
     * @param Date $date
     * @return Cursor
     */
    public function findOneByHoursEvent($hours=null, $date=null)
    {
        if (!$date) {
            $currentDatetime = new \DateTime("now");
            $hoursDatetime = new \DateTime("now");
            $startDay = new \DateTime("now");
            $finishDay = new \DateTime("now");
        } else {
            $currentDatetime = new \DateTime($date->format('Y-m-d H:s:i'));
            $hoursDatetime = new \DateTime($date->format('Y-m-d H:s:i'));
            $startDay = new \DateTime($date->format('Y-m-d H:s:i'));
            $finishDay = new \DateTime($date->format('Y-m-d H:s:i'));
        }
        $hoursDatetime->add(new \DateInterval('PT'.$hours.'H'));
        $startDay->setTime(0, 0, 0);
        $finishDay->setTime(23, 59, 59);

        $currentDayEvents = $this->createQueryBuilder()
            ->field('display')->equals(true)
            ->field('date')->gte($startDay)
            ->field('date')->lte($finishDay)
            ->sort('date', 1)
            ->getQuery()->execute();

        $duration = 0;
        foreach ($currentDayEvents as $event) {
            $eventDate = new \DateTime($event->getDate()->format("Y-m-d H:i:s"));
            if (($eventDate <= $hoursDatetime) && ($currentDatetime <= $eventDate->add(new \DateInterval('PT'.$event->getDuration().'M')))) {
                return $event;
            }
        }

        return null;
    }
}
