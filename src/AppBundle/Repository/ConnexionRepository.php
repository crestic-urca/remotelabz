<?php

namespace AppBundle\Repository;

/**
 * ConnexionRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ConnexionRepository extends \Doctrine\ORM\EntityRepository
{

    public function getConnexionByPOD($pod)
    {
        return $this
            ->createQueryBuilder('con')
            ->where('con.pod = :pod')
			->andwhere('con.lab IS NULL')
            ->setParameter('pod', $pod)
            ->getQuery()
            ->getArrayResult();
    }
    public function getConnexionByPOD_QueryBuilder($pod)
    {
        return $this
            ->createQueryBuilder('con')
            ->where('con.pod = :pod')
			->andwhere('con.lab IS NULL')
            ->setParameter('pod', $pod);

    }
}
