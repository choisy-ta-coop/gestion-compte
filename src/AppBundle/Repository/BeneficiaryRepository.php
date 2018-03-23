<?php

namespace AppBundle\Repository;

/**
 * BeneficiaryRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BeneficiaryRepository extends \Doctrine\ORM\EntityRepository
{
    public function findFromAutoComplete($str)
    {
        $re = '/.*\(([0-9]+)\)/';
        preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);
        if (count($matches) == 1) {
            $beneficiaryId = $matches[0][1];
            return $this->find($beneficiaryId);
        }
        return null;
    }
}
