<?php

namespace AppBundle\Repository;

/**
 * ClosingExceptionRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ClosingExceptionRepository extends \Doctrine\ORM\EntityRepository
{
    public function findAll()
    {
        return $this->findBy(array(), array('date' => 'DESC'));
    }

    public function findFuturesOrOngoing(\DateTime $date = null)
    {
        if (!$date) {
            $date = new \DateTime('now');
        }

        $qb = $this->createQueryBuilder('ce')
            ->where("ce.date > :date OR DATE_FORMAT(ce.date, '%Y-%m-%d') = :date_formatted")
            ->setParameter('date', $date)
            ->setParameter('date_formatted', $date->format('Y-m-d'));

        $qb->orderBy('ce.date', 'ASC');

        return $qb
            ->getQuery()
            ->getResult();
    }

    public function findFutures(\DateTime $date = null)
    {
        if (!$date) {
            $date = new \DateTime('now');
        }

        $qb = $this->createQueryBuilder('ce')
            ->where('ce.date > :date')
            ->andWhere("DATE_FORMAT(ce.date, '%Y-%m-%d') != :date_formatted")
            ->setParameter('date', $date)
            ->setParameter('date_formatted', $date->format('Y-m-d'));

        $qb->orderBy('ce.date', 'ASC');

        return $qb
            ->getQuery()
            ->getResult();
    }

    public function findOngoing(\DateTime $date = null)
    {
        if (!$date) {
            $date = new \DateTime('now');
        }

        $qb = $this->createQueryBuilder('ce')
        ->where("DATE_FORMAT(ce.date, '%Y-%m-%d') = :date")
        ->setParameter('date', $date->format('Y-m-d'));

        return $qb
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPast(\DateTime $date = null, int $limit = null)
    {
        if (!$date) {
            $date = new \DateTime('now');
        }

        $qb = $this->createQueryBuilder('ce')
            ->where('ce.date < :date')
            ->andWhere("DATE_FORMAT(ce.date, '%Y-%m-%d') != :date_formatted")
            ->setParameter('date', $date)
            ->setParameter('date_formatted', $date->format('Y-m-d'));

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        $qb->orderBy('ce.date', 'DESC');

        return $qb
            ->getQuery()
            ->getResult();
    }
}
