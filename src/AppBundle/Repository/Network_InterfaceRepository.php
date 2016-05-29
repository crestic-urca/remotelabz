<?php

namespace AppBundle\Repository;


/**
 * Network_InterfaceRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class Network_InterfaceRepository extends \Doctrine\ORM\EntityRepository
{
    public function getNotUsedInterfaceControlQueryBuilder()
    {
        $em = $this->getEntityManager();
        $qb_used_interface = $em->createQueryBuilder()
        ->select('net')
		->from('AppBundle:Network_Interface', 'net')
		->innerJoin('AppBundle:Device','dev', 'WITH', 'dev.interfaceControle = net.id')
		->getQuery()
		->getArrayResult();
		
		$qb = $em->createQueryBuilder();
		return $qb
		->select('net')
		->from('AppBundle:Network_Interface', 'net')
		->where($qb->expr()->notin('net.id',':qb_used_interface'))
		->andWhere(' net.config_reseau IS NOT NULL')
		->setParameter('qb_used_interface',$qb_used_interface)
		;
		

    }
    public function getNotUsedInterfacesQueryBuilder()
    {
			$em = $this->getEntityManager();
        return  $qb = $em->createQueryBuilder()
            ->select('net')
            ->from('AppBundle:Network_Interface', 'net')
            ->where('net.device IS NULL and net.config_reseau IS NULL')
			;


    }

    public function getInterfacesAttachedToDevice($dev)
    {

        return $this
            ->createQueryBuilder('net')
            ->where('net.device = :device')
            ->setParameter('device', $dev);

    }


    public function Network_Interface($device)
    {
        return $this
            ->createQueryBuilder('net')
            ->where('net.device = :device')
            ->setParameter('device', $device)
            ->getQuery()
            ->getArrayResult();
    }
	 public function getInterfaceForList()
    {
        return $this
            ->createQueryBuilder('net')
            ->where('net.config_reseau IS NULL')
            ->getQuery()
            ->getResult();
    }
	
}


