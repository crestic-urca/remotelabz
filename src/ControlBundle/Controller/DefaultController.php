<?php

namespace ControlBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;


class DefaultController extends Controller
{
    /**
     * @Route("/control", name="control_vm")
     */
    public function indexAction()
    {
			
		$authenticationUtils = $this->get('security.authentication_utils');
		$user = $this->get('security.token_storage')->getToken()->getUser();
		$group=$user->getGroupe();
		// Si l'utilisateur courant est anonyme, $user vaut « anon. »
		// Sinon, c'est une instance de notre entité User, on peut l'utiliser normalement
		
        return $this->render('ControlBundle:Default:index.html.twig', array(
		'user' => $user,
		'group' => $group,
		'host' => "194.57.105.124",
		'port' => "7220" // Linux
		//'port' => "7224" // Windows 7
		));
    }
	
	/**
     * @Route("/control/view_vm", name="view_vm")
     */
	 public function view_vmAction() {
		 
		$authenticationUtils = $this->get('security.authentication_utils');
		$user = $this->get('security.token_storage')->getToken()->getUser();
		$group=$user->getGroupe();
		
			return $this->render('ControlBundle:Default:vm_view.html.twig', array(
		'user' => $user,
		'group' => $group,
		'host' => "194.57.105.124",
		//'port' => "7220"
		'port' => "7227" // Windows 7
		));
	 }
	 
	 /**
     * @Route("/control2", name="control_vm2")
     */
    public function indexVM2Action()
    {
			
		$authenticationUtils = $this->get('security.authentication_utils');
		$user = $this->get('security.token_storage')->getToken()->getUser();
		$group=$user->getGroupe();
		// Si l'utilisateur courant est anonyme, $user vaut « anon. »
		// Sinon, c'est une instance de notre entité User, on peut l'utiliser normalement
		
        return $this->render('ControlBundle:Default:vm2.html.twig', array(
		'user' => $user,
		'group' => $group		
		));
    }
	/**
     * @Route("/control/view_vm1", name="view_vm1")
     */
	 public function view_vm1Action() {
		 
		$authenticationUtils = $this->get('security.authentication_utils');
		$user = $this->get('security.token_storage')->getToken()->getUser();
		$group=$user->getGroupe();
		
			return $this->render('ControlBundle:Default:vm_view.html.twig', array(
		'user' => $user,
		'group' => $group,
		'host' => "194.57.105.124",
		//'port' => "7220"
		'port' => "6686" // Windows 7
		));
	 }
	 
	/**
     * @Route("/control/view_vm2", name="view_vm2")
     */
	 public function view_vm2Action() {
		 
		$authenticationUtils = $this->get('security.authentication_utils');
		$user = $this->get('security.token_storage')->getToken()->getUser();
		$group=$user->getGroupe();
		
			return $this->render('ControlBundle:Default:vm_view.html.twig', array(
		'user' => $user,
		'group' => $group,
		'host' => "194.57.105.124",
		//'port' => "7220"
		'port' => "6688" // Windows 7
		));
	 }
	 
	 /**
     * @Route("/control/view_vm3", name="view_vm3")
     */
	 public function view_vm3Action() {
		 
		$authenticationUtils = $this->get('security.authentication_utils');
		$user = $this->get('security.token_storage')->getToken()->getUser();
		$group=$user->getGroupe();
		
			return $this->render('ControlBundle:Default:vm_view.html.twig', array(
		'user' => $user,
		'group' => $group,
		'host' => "194.57.105.124",
		//'port' => "7220"
		'port' => "6687"
		));
	 }
	 
	 /**
     * @Route("/control/view_vm4", name="view_vm4")
     */
	 public function view_vm4Action() {
		 
		$authenticationUtils = $this->get('security.authentication_utils');
		$user = $this->get('security.token_storage')->getToken()->getUser();
		$group=$user->getGroupe();
		
		return $this->render('ControlBundle:Default:wstelnet.html.twig', array(
		'user' => $user,
		'group' => $group,
		'host' => "194.57.105.124",
		//'port' => "7220"
		'port' => "6689"
		));
	 }
	 
	/**
     * @Route("/control/choixTP", name="choixTP")
     */
    public function choixTPAction()
    {		
		$authenticationUtils = $this->get('security.authentication_utils');
		$user = $this->get('security.token_storage')->getToken()->getUser();
		$group=$user->getGroupe();
		
		$repository = $this->getDoctrine()->getRepository('AppBundle:TP');
        $list_tp = $repository->findAll();
		
		
        return $this->render('ControlBundle:Default:choixTP.html.twig', array(
		'user' => $user,
		'group' => $group,
		'list_tp' => $list_tp
		));
    }
	
	public function UpdateInterfaceIndex($tp_id,$increment) { // increment permet de définir si il faut augmenter (+1) ou diminuer (-1) l'index des interfaces utilisables
		$authenticationUtils = $this->get('security.authentication_utils');
		$user = $this->get('security.token_storage')->getToken()->getUser();
		$group=$user->getGroupe();
		$em = $this->getDoctrine()->getManager();
				
		$param_system = $this->getDoctrine()->getRepository('AppBundle:Param_System')->findOneBy(array('id' => '1'));
		$start_index=$param_system->getIndexInterface();
		
		$tp = $this->getDoctrine()->getRepository('AppBundle:TP')->find($tp_id);
		$lab=$tp->getLab();
		foreach ($lab->getPod()->getDevices() as $dev) {
			if ($dev->getType() == "virtuel"){
				$intctrl_id=$dev->getInterfaceControle();
				foreach ($dev->getNetworkInterfaces() as $int)
					if ($intctrl_id && ($intctrl_id->getId() == $int->getId()))
					{} else $start_index=$start_index+$increment;
			}
		}
		$min_index=$param_system->getIndexMinInterface();
		if ($start_index < $min_index)
			$param_system->setIndexInterface($min_index);
		else 	
			$param_system->setIndexInterface($start_index+$min_index);	
		$em->persist($param_system);
		$em->flush();
	}
	
	/**
     * @Route("/control/stopLab{tp_id}", name="stopLab")
     */
	public function stopLab($tp_id){
		$authenticationUtils = $this->get('security.authentication_utils');
		$user = $this->get('security.token_storage')->getToken()->getUser();
		$group=$user->getGroupe();
		
		$param_system = $this->getDoctrine()->getRepository('AppBundle:Param_System')->findOneBy(array('id' => '1'));

		$this->UpdateInterfaceIndex($tp_id,-1);
		
		$list_tp = $this->getDoctrine()->getRepository('AppBundle:TP')->findAll();
				
        return $this->render('ControlBundle:Default:choixTP.html.twig', array(
		'user' => $user,
		'group' => $group,
		'list_tp' => $list_tp
		));
	}
	
	/**
     * @Route("/control/startLab{tp_id}", name="startLab")
     */
    public function startLabAction($tp_id)
    {	
	// Ajouter la gestion de l'objet réservation
	// Faire le exec avec le fichier XML stocké par generate_xml
	
	}
	
	/**
     * @Route("/control/generate_xml{tp_id}", name="generate_xml")
     */
    public function generate_xmlAction($tp_id)
    {	
		$authenticationUtils = $this->get('security.authentication_utils');
		$user = $this->get('security.token_storage')->getToken()->getUser();
		$group=$user->getGroupe();

		$param_system = $this->getDoctrine()->getRepository('AppBundle:Param_System')->findOneBy(array('id' => '1'));
		
		$repository = $this->getDoctrine()->getRepository('AppBundle:TP');
        $tp = $repository->find($tp_id);
		$lab=$tp->getLab();
		
		$rootNode = new \SimpleXMLElement("<?xml version='1.0' encoding='UTF-8' standalone='yes'?><lab></lab>" );
        $user_node=$rootNode->addChild('user');
		$index=$param_system->getIndexInterface();
		$nomlab_node=$rootNode->addChild('lab_name',$lab->getNomlab()."_".$param_system->getIndexInterface());
		$user_node->addAttribute('login',$user->getUsername());
		$nodes = $rootNode->addChild('nodes');
		$order=1;
		
		foreach ($lab->getPod()->getDevices() as $dev) {
			$device=$nodes->addChild('device');
			$device->addChild('nom', $dev->getNom());
			$device->addAttribute('type',$dev->getType());
			$device->addAttribute('property',$dev->getPropriete());
			$device->addAttribute('id',$dev->getId());
			$device->addAttribute('script',$dev->getScript());
			$device->addAttribute('image',$dev->getSysteme()->getPathMaster());
			$device->addAttribute('relativ_path',$dev->getSysteme()->getPathRelatif());
			$device->addAttribute('order',$order); //A remplacer par un $dev
			$device->addAttribute('hypervisor',$dev->getSysteme()->getHyperviseur()->getNom());
			$system=$dev->getSysteme();
			$system_node=$device->addChild('system', $system->getNom());
			$system_node->addAttribute('memory',$system->getParametres()->getSizeMemoire()." Mo");
			$system_node->addAttribute('disk',$system->getParametres()->getSizeDisque()." Go");
			$order++;
			foreach ($dev->getNetworkInterfaces() as $int) {
				if ($dev->getInterfaceControle()) {
					if ( $int->getId() != $dev->getInterfaceControle()->getId() ) {
						$interface=$device->addChild('interface');
						$interface->addAttribute('id',$int->getId());
						$interface->addAttribute('physique_name',$int->getNomPhysique());
						if ($int->getNomVirtuel() == "tap") {
							$interface->addAttribute('logical_name',"tap".$index);
							$index++;
						}
					}
				}
				else {
					$interface=$device->addChild('interface');
						$interface->addAttribute('id',$int->getId());
						$interface->addAttribute('nom_physique',$int->getNomPhysique());
						$interface->addAttribute('virtual_name',$int->getNomVirtuel());
				}
			}
			$int_ctrl=$dev->getInterfaceControle();
			if ($int_ctrl) {
			$int_ctrl_node=$device->addChild('interface_control');
			$int_ctrl_node->addAttribute('id',$int_ctrl->getId());
			$int_ctrl_node->addAttribute('physique_name',$int_ctrl->getNomPhysique());
			$int_ctrl_node->addAttribute('logical_name',$int_ctrl->getNomVirtuel());
			if ($int_ctrl->getConfigReseau()) {
				$int_ctrl_node->addAttribute('IPv4',$int_ctrl->getConfigReseau()->getIP());
				$int_ctrl_node->addAttribute('mask',$int_ctrl->getConfigReseau()->getMasque());
				$int_ctrl_node->addAttribute('IPv6',$int_ctrl->getConfigReseau()->getIPv6());
				$int_ctrl_node->addAttribute('prefix',$int_ctrl->getConfigReseau()->getPrefix());
				$int_ctrl_node->addAttribute('DNSv4',$int_ctrl->getConfigReseau()->getIPDNS());
				$int_ctrl_node->addAttribute('gatewayv4',$int_ctrl->getConfigReseau()->getIPGateway());
				$int_ctrl_node->addAttribute('protocol',$int_ctrl->getConfigReseau()->getProtocole());
				$int_ctrl_node->addAttribute('port',$int_ctrl->getConfigReseau()->getPort());
			}
			}
			if ($dev->getPropriete() == "switch") {
				$networks=$rootNode->addChild('networks');
				$network=$networks->addChild('network');
				$network->addAttribute('type','OVS');
				$network->addAttribute('device_id',$dev->getId());
				foreach ($lab->getConnexions() as $con) {
					$interface=$network->addChild('port');
					$interface->addAttribute('id',$con->getId());
					$interface->addAttribute('interface_id1',$con->getInterface1()->getId());			
					$interface->addAttribute('vlan1',$con->getVlan1());
					$interface->addAttribute('interface_id2',$con->getInterface2()->getId());
					$interface->addAttribute('vlan2',$con->getVlan2());
				}
			}
		}
		$init = $rootNode->addChild('init');
		$serveur = $init->addChild('serveur');	
		$serveur->addChild('IPv4',$param_system->getIpv4());
		$serveur->addChild('IPv6',$param_system->getIpv6());
		$serveur->addChild('index',$param_system->getIndexInterface());
		$this->UpdateInterfaceIndex($tp_id,1);

		$response=new Response($rootNode->asXML());
		$response->headers->set('Content-Type', 'application/xml');
		//$disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,'foo.xml');
		//$response->headers->set('Content-Disposition', $disposition);
        return $response;

    }
	
}
