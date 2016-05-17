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

       return  $qb = $em->createQueryBuilder()
            ->select('net')
            ->from('AppBundle:Network_Interface', 'net');
//            ->innerJoin('AppBundle:Device', 'dev', 'WITH', 'net.config_reseau IS NOT NULL ')
			;

//         $qb = $this->_em->createQueryBuilder();
//        $query = $this->_em->createQuery('SELECT net FROM AppBundle:Network_Interface net WHERE net.id  IS NOT EXISTS
//                                     SELECT dev FROM AppBundle:Device dev WHERE dev.interfaceControle  IS NOT NULL');
////        return $qb->select('net')
//            ->from('AppBundle:Network_Interafce','net')
//            ->where('net.id','NOT IN',$query);




//         $devs =$qb->select('dev')
//            ->from('AppBundle:Device', 'dev')
//            ->where($qb->expr()->isNotNull('dev.interfaceControle'))
//            ->getQuery()
//            ->getResult();
//


//            foreach($res as $r){
//                echo
//
//            }
//        die();


//        $ints = $qb->select('net')
//            ->from('AppBundle:Network_Interface', 'net')
//            ->where($qb->expr()->isNotNull('net.config_reseau'))
//            ->andWhere($qb->expr()->notIn('net.id', $sub));
//        return $ints;



//        return $qb->select('net')
//            ->from('AppBundle:Network_Interface', 'net')
//            ->where($qb->expr()->isNotNull('net.config_reseau'));

    }
    public function getNotUsedInterfacesQueryBuilder()
    {
        $qb = $this->_em->createQueryBuilder();
        return $qb->select('net')
        ->from('AppBundle:Network_Interface', 'net')
        ->where($qb->expr()->isNull('net.device'))
            ->andWhere($qb->expr()->isNull('net.config_reseau'));


    }
}
//            ->from('AppBundleDevice','dev')
//            ->Where($qb->expr()->notIn('dev.interfaceControle',
//            )
////
//
//
//            ->andWhere($qb->expr()->not($qb->expr()->exists(
//                $qb2->select('dev')
//                    ->from('AppBundle:Device', 'dev')
//                    ->where($qb2->expr()->isNotNull(c))
//            )));




//    public function getAllUsedInterfaceControlQueryBuilder()
//    {
//        $em = $this->getEntityManager();
//
//        $qb = $em->createQueryBuilder('dev');
//
//            $qb ->select('dev')
//                ->from('AppBundle:Device', 'dev')
//                ->where($qb->expr()->isNotNull('dev.interfaceControle'));
////                ->getQuery()
////                ->getResult();
//
//        return $qb;
//    }





//           return $qb =   $this
//            ->createQueryBuilder('net')
//            ->innerJoin('AppBundle:Device', 'd', 'WITH', 'd.interfaceControle != net.id ')
//
//            ->where('net.config_reseau !=  :id')
//             ->setParameter('id', 0 ) ;
////            ->select('net')
//            ->from('AppBundle:Network_Interface', 'net')
//            ->innerJoin('AppBundle:Device', 'd', 'WITH', 'd.interfaceControle != net.id ');

//
//
//
//              ->where($qb->expr()->isNotNull('net.config_reseau' ));



//            ->where('net.config_reseau != :status')
//            ->setParameter('status',NULL)

//        $qb->where($qb->expr()->isNotNull('net.config_reseau' ));

//          $result =  $qb->getQuery()->getResult();
//
////           ->andWhere($qb->expr()->isNull('net.device' ));
//
////            and($qb->expr()->isNull('net.device' )));
//        return $result;

