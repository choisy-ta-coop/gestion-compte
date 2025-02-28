<?php

namespace AppBundle\Repository;

use AppBundle\Entity\EventKind;

/**
 * EventRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EventRepository extends \Doctrine\ORM\EntityRepository
{
    public function findAll()
    {
        return $this->findBy(array(), array('date' => 'DESC'));
    }

    public function findFutureOrOngoing(EventKind $eventKind = null, \DateTime $max = null, int $limit = null)
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.kind', 'ek')
            ->addSelect('ek')
            ->where('e.date > :now OR e.date < :now AND e.end IS NOT NULL AND e.end > :now')
            ->setParameter('now', new \Datetime('now'));

        if ($eventKind) {
            $qb
                ->andwhere('e.kind = :kind')
                ->setParameter('kind', $eventKind);
        }

        if ($max) {
            $qb
                ->andWhere('e.date < :max')
                ->setParameter('max', $max);
        }

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        $qb->orderBy('e.date', 'ASC');

        return $qb
            ->getQuery()
            ->getResult();
    }

    public function findFutures(EventKind $eventKind = null, \DateTime $max = null, int $limit = null)
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.kind', 'ek')
            ->addSelect('ek')
            ->where('e.date > :now')
            ->setParameter('now', new \Datetime('now'));

        if ($eventKind) {
            $qb
                ->andwhere('e.kind = :kind')
                ->setParameter('kind', $eventKind);
        }

        if ($max) {
            $qb
                ->andWhere('e.date < :max')
                ->setParameter('max', $max);
        }

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        $qb->orderBy('e.date', 'ASC');

        return $qb
            ->getQuery()
            ->getResult();
    }

    public function findOngoing(EventKind $eventKind = null)
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.kind', 'ek')
            ->addSelect('ek')
            ->where('e.date < :now')
            ->andWhere('e.end IS NOT NULL')
            ->andWhere('e.end > :now')
            ->setParameter('now', new \Datetime('now'));

        if ($eventKind) {
                $qb
                    ->andwhere('e.kind = :kind')
                    ->setParameter('kind', $eventKind);
            }

            $qb->orderBy('e.date', 'ASC');

            return $qb
                ->getQuery()
                ->getResult();
    }

    public function findPast(EventKind $eventKind = null, int $limit = null)
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.kind', 'ek')
            ->addSelect('ek')
            ->where('e.date < :now')
            ->setParameter('now', new \Datetime('now'));

        if ($eventKind) {
            $qb
                ->andwhere('e.kind = :kind')
                ->setParameter('kind', $eventKind);
        }

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        $qb->orderBy('e.date', 'DESC');

        return $qb
            ->getQuery()
            ->getResult();
    }
}
