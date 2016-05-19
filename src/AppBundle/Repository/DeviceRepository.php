<?php

namespace AppBundle\Repository;

/**
 * DeviceRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class DeviceRepository extends \Doctrine\ORM\EntityRepository
{

    public function getNotUsedDeviceQueryBuilder()
    {
        $qb = $this->_em->createQueryBuilder();
        return $qb->select('dev')
            ->from('AppBundle:Device', 'dev')

            ->where($qb->expr()->isNull('dev.pod'))//            ->andWhere($qb->expr()->isNull('net.config_reseau'));
            ;;

            //->where($qb->expr()->isNull('dev.pod'))
//            ->andWhere($qb->expr()->isNull('net.config_reseau'));
           ;

    }

    public function Device($pod)
    {
        return $this
            ->createQueryBuilder('dev')
            ->where('dev.pod = :pod')
            ->setParameter('pod', $pod);
//            ->getQuery()
//           ->getArrayResult();
    }

    public function InterfacesAttachedToPod($dev)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder()
            ->select('net')
            ->from('AppBundle:Network_Interface', 'net')
            ->innerJoin('AppBundle:Device', 'dev', 'WITH', 'net.device= :device_id')
            ->setParameter('device_id', $dev);
            return $res = $qb->getQuery()->getResult();
    }
}

