<?php

namespace App\Controller;

use App\Entity\Lab;
use GuzzleHttp\Psr7;
use App\Form\LabType;
use App\Entity\Device;
use GuzzleHttp\Client;
use App\Entity\Network;
use App\Form\DeviceType;
use App\Entity\LabInstance;
use Psr\Log\LoggerInterface;
use App\Service\FileUploader;
use App\Entity\DeviceInstance;
use Swagger\Annotations as SWG;
use App\Repository\LabRepository;
use FOS\RestBundle\Context\Context;
use App\Repository\DeviceRepository;
use JMS\Serializer\SerializerInterface;
use App\Entity\NetworkInterfaceInstance;
use App\Exception\NotInstancedException;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Request\ParamFetcher;
use JMS\Serializer\SerializationContext;
use App\Repository\LabInstanceRepository;
use Doctrine\Common\Collections\Criteria;
use GuzzleHttp\Exception\ServerException;
use Nelmio\ApiDocBundle\Annotation\Model;
use GuzzleHttp\Exception\RequestException;
use App\Exception\AlreadyInstancedException;
use App\Repository\DeviceInstanceRepository;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use App\Repository\NetworkInterfaceInstanceRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\User\UserInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class LabController extends AbstractFOSRestController
{
    /**
     * IP or FQDN of worker.
     *
     * @var string
     */
    private $workerServer;
    /**
     * Port of worker.
     *
     * @var int
     */
    private $workerPort;
    /**
     * Workers full URL (IP and port).
     *
     * @var string
     */
    private $workerAddress;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var LabRepository $labRepository */
    private $labRepository;

    public function __construct(LoggerInterface $logger, LabRepository $labRepository)
    {
        $this->workerServer = (string) getenv('WORKER_SERVER');
        $this->workerPort = (int) getenv('WORKER_PORT');
        $this->workerAddress = $this->workerServer . ":" . $this->workerPort;
        $this->logger = $logger;
        $this->labRepository = $labRepository;
    }

    /**
     * @Route("/labs", name="labs")
     * 
     * @Rest\Get("/api/labs", name="api_get_labs")
     * @Rest\QueryParam(name="limit", requirements="\d+", default="10")
     * 
     * @SWG\Parameter(
     *     name="search",
     *     in="query",
     *     type="string",
     *     description="Filter labs by name. All labs with a name containing this value will be shown."
     * )
     * 
     * @SWG\Response(
     *     response=200,
     *     description="Returns all existing labs",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Lab::class))
     *     )
     * )
     * 
     * @SWG\Tag(name="Lab")
     */
    public function indexAction(Request $request)
    {
        $search = $request->query->get('search', '');
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        
        $criteria = Criteria::create()
            ->where(Criteria::expr()->contains('name', $search))
            ->orderBy([
                'id' => Criteria::DESC
            ])
            ->setMaxResults($limit)
        ;

        $labs = $this->labRepository->matching($criteria);

        $context = new Context();
        $context
            ->addGroup("lab")
        ;

        $view = $this->view($labs->getValues())
            ->setTemplate("lab/index.html.twig")
            ->setTemplateData([
                'labs' => $labs,
                'search' => $search
            ])
            ->setContext($context)
        ;

        return $this->handleView($view);
    }

    /**
     * @Route("/labs/{id<\d+>}",
     *  name="show_lab",
     *  methods="GET")
     * 
     * @Rest\Get("/api/labs/{id<\d+>}", name="api_get_lab")
     * 
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="integer",
     *     description="ID of the lab."
     * )
     * 
     * @SWG\Response(
     *     response=200,
     *     description="Returns requested lab",
     *     @Model(type=Lab::class)
     * )
     * 
     * @SWG\Tag(name="Lab")
     */
    public function showAction(int $id, UserInterface $user, LabInstanceRepository $labInstanceRepository, LabRepository $labRepository)
    {
        $lab = $labRepository->find($id);

        if (!$lab) {
            throw new NotFoundHttpException("Lab " . $id . " does not exist.");
        }

        // Remove all instances not belongs to current user (changes are not stored in database)
        $userLabInstance = $labInstanceRepository->findByUserAndLab($user, $lab);
        $lab->setInstances($userLabInstance != null ? [ $userLabInstance ] : []);
        $deviceStarted = [];

        /** @var Device $device */
        foreach ($lab->getDevices() as $device) {
            $deviceStarted[$device->getId()] = false;

            if ($userLabInstance && $userLabInstance->getUserDeviceInstance($device)) {
                $deviceStarted[$device->getId()] = true;
            }
        }

        $context = new Context();
        $context->setGroups([
            "primary_key",
            "lab",
            "author" => [
                "primary_key"
            ],
            "editor"
        ]);
        $view = $this->view($lab, 200)
            ->setTemplate("lab/view.html.twig")
            ->setTemplateData([
                'lab' => $lab,
                'labInstance' => $userLabInstance,
                'deviceStarted' => $deviceStarted,
                'user' => $user
            ])
            ->setContext($context)
        ;

        return $this->handleView($view);
    }

    /**
     * @Route("/labs/new", name="new_lab")
     * 
     * @Rest\Post("/api/labs", name="api_new_lab")
     */
    public function newAction()
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->startsWith('name', 'Untitled Lab'))
        ;

        $untitledLabsCount = count($this->labRepository->matching($criteria));
        $name = 'Untitled Lab';

        if ($untitledLabsCount != 0) {
            $name .= ' (' . $untitledLabsCount . ')';
        }

        $lab = new Lab();
        $lab->setName($name)
            ->setAuthor($this->getUser())
        ;

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($lab);
        $entityManager->flush();

        $view = $this->view($lab, 201)
            ->setTemplate("lab/editor.html.twig")
            ->setLocation($this->generateUrl('edit_lab', ['id' => $lab->getId()]))
        ;

        $context = new Context();
        $context
            ->setGroups([
                "primary_key",
                "lab",
                "author" => [
                    "primary_key"
                ]
            ])
        ;
        $view->setContext($context);

        return $this->handleView($view);
    }

    /**
     * @Rest\Post("/api/labs/{id<\d+>}/devices", name="api_add_device_lab")
     */
    public function addDeviceAction(Request $request, int $id)
    {
        $device = new Device();
        $deviceForm = $this->createForm(DeviceType::class, $device);
        $deviceForm->handleRequest($request);

        if ($request->getContentType() === 'json') {
            $device = json_decode($request->getContent(), true);
            $deviceForm->submit($device);
        } 

        $view = $this->view($deviceForm);

        if ($deviceForm->isSubmitted() && $deviceForm->isValid()) {
            /** @var Device $device */
            $device = $deviceForm->getData();
            $lab = $this->labRepository->find($id);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($device);
            $lab->addDevice($device);
            $entityManager->persist($lab);
            $entityManager->flush();

            $view->setLocation($this->generateUrl('devices'));
            $view->setStatusCode(201);
            $view->setData($device);
            $context = new Context();
            $context
                ->addGroup("lab")
                ->addGroup("primary_key")
                ->addGroup("editor")
            ;
            $view->setContext($context);
        }

        return $this->handleView($view);

        // $criteria = Criteria::create()
        //     ->where(Criteria::expr()->startsWith('name', 'Untitled Lab'))
        // ;

        // $untitledLabsCount = count($this->labRepository->matching($criteria));
        // $name = 'Untitled Lab';

        // if ($untitledLabsCount != 0) {
        //     $name .= ' (' . $untitledLabsCount . ')';
        // }

        // $lab = new Lab();
        // $lab->setName($name)
        //     ->setAuthor($this->getUser())
        // ;

        // $entityManager = $this->getDoctrine()->getManager();
        // $entityManager->persist($lab);
        // $entityManager->flush();

        // $view = $this->view($lab, 201)
        //     ->setTemplate("lab/editor.html.twig")
        //     ->setLocation($this->generateUrl('edit_lab', ['id' => $lab->getId()]))
        // ;

        // $context = new Context();
        // $context
        //     ->setGroups([
        //         "primary_key",
        //         "lab",
        //         "author" => [
        //             "primary_key"
        //         ]
        //     ])
        // ;
        // $view->setContext($context);

        // return $this->handleView($view);
    }

    /**
     * @Route("/admin/labs/{id<\d+>}/edit", name="edit_lab")
     * 
     * @Rest\Put("/api/labs/{id<\d+>}", name="api_edit_lab")
     * 
     * @SWG\Parameter(
     *     name="lab",
     *     in="body",
     *     @SWG\Schema(ref=@Model(type=Lab::class, groups={"api"})),
     *     description="Lab data."
     * )
     * 
     * @SWG\Response(
     *     response=200,
     *     description="Returns the newly edited lab.",
     *     @SWG\Schema(ref=@Model(type=Lab::class))
     * )
     * 
     * @SWG\Tag(name="Lab")
     */
    public function updateAction(Request $request, int $id)
    {
        $lab = $this->labRepository->find($id);

        if (!$lab) {
            throw new NotFoundHttpException("Lab " . $id . " does not exist.");
        }

        $labForm = $this->createForm(LabType::class, $lab);
        $labForm->handleRequest($request);

        if ($request->getContentType() === 'json') {
            $lab = json_decode($request->getContent(), true);
            $labForm->submit($lab, false);
        } 

        $view = $this->view($labForm)
            ->setTemplate("lab/editor.html.twig")
        ;

        if ($labForm->isSubmitted() && $labForm->isValid()) {
            /** @var Lab $lab */
            $lab = $labForm->getData();
            $lab->setLastUpdated(new \DateTime());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($lab);
            $entityManager->flush();

            if ($request->getRequestFormat() === 'html') {
                $this->addFlash('info', 'Lab has been edited.');
            }

            $view->setStatusCode(200);
            $view->setData($lab);
            $context = new Context();
            $context
                ->setGroups([
                    "primary_key",
                    "lab",
                    "author" => [
                        "primary_key"
                    ]
                ])
            ;
            $view->setContext($context);
        }

        return $this->handleView($view);
    }

    // /**
    //  * @Route("/labs/{id<\d+>}/edit", name="edit_lab", methods={"GET", "POST"})
    //  */
    // public function editAction(Request $request, int $id, LabRepository $labRepository, EntityManagerInterface $entityManager)
    // {
    //     $lab = $labRepository->find($id);

    //     if (null === $lab) {
    //         throw new NotFoundHttpException();
    //     }

    //     $labForm = $this->createForm(LabType::class, $lab);
    //     $labForm->handleRequest($request);
        
    //     if ($labForm->isSubmitted() && $labForm->isValid()) {
    //         $lab = $labForm->getData();

            
    //         $entityManager->persist($lab);
    //         $entityManager->flush();
            
    //         $this->addFlash('success', 'Lab has been edited.');

    //         return $this->redirectToRoute('show_lab', [
    //             'id' => $id
    //         ]);
    //     }
        
    //     return $this->render('lab/new.html.twig', [
    //         'labForm' => $labForm->createView(),
    //         'id' => $id,
    //         'name' => $lab->getName()
    //     ]);
    // }

    /**
     * @Route("/admin/labs/{id<\d+>}/delete", name="delete_lab", methods="GET")
     * 
     * @Rest\Delete("/api/labs/{id<\d+>}", name="api_delete_lab")
     */
    public function deleteAction(int $id)
    {
        $lab = $this->labRepository->find($id);

        if (null === $lab) {
            throw new NotFoundHttpException("Lab " . $id . " does not exist.");
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($lab);
        $entityManager->flush();

        $this->addFlash('success', $lab->getName() . ' has been deleted.');

        $view = $this->view()
            ->setLocation($this->generateUrl('labs'));
        ;

        return $this->handleView($view);
    }

    /**
     * @Route("/labs/{id<\d+>}/start", name="start_lab", methods="GET")
     */
    public function startLabAction(int $id, UserInterface $user, LabRepository $labRepository)
    {
        $lab = $labRepository->find($id);
        $hasError = false;

        /** @var Device $device */
        foreach ($lab->getDevices() as $device) {
            try {
                $this->startDevice($lab, $device, $user);
            } catch (AlreadyInstancedException $exception) {
                $this->logger->debug("There is already an instance for device " . $device->getName() . " (" . $device->getUuid() . ") with UUID " . $exception->getInstance()->getUuid());
            } catch (ClientException $exception) {
                $this->logger->error(Psr7\str($exception->getRequest()));
                if ($exception->hasResponse()) {
                    $this->logger->error(Psr7\str($exception->getResponse()));
                }
                $hasError = true;
            } catch (ServerException $exception) {
                $this->logger->error(Psr7\str($exception->getRequest()));
                if ($exception->hasResponse()) {
                    $this->logger->error(Psr7\str($exception->getResponse()));
                }
                $hasError = true;
                $this->stopDevice($lab, $device, $user);
            }
        }

        if ($hasError) {
            $this->addFlash('warning', 'Some devices failed to start. Please verify your parameters or contact an administrator.');
        } else {
            $this->addFlash('success', $lab->getName().' has been started.');
        }

        return $this->redirectToRoute('show_lab', [
            'id' => $id
        ]);
    }

    /**
     * @Route("/labs/{id<\d+>}/stop", name="stop_lab", methods="GET")
     */
    public function stopLabAction(int $id, UserInterface $user, LabRepository $labRepository)
    {
        $lab = $labRepository->find($id);

        foreach ($lab->getDevices() as $device) {
            try {
                $this->stopDevice($lab, $device, $user);
            } catch (NotInstancedException $exception) {
                $this->logger->debug("Device " . $device->getName() . " was not instanced in lab " . $lab->getName());
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage());
            }
        }

        $this->addFlash('success', $lab->getName().' has been stopped.');

        return $this->redirectToRoute('show_lab', [
            'id' => $id
        ]);
    }

    /**
     * @Route("/labs/{labId<\d+>}/device/{deviceId<\d+>}/start", name="start_lab_device", methods="GET")
     */
    public function startDeviceAction(int $labId, int $deviceId, UserInterface $user, LabRepository $labRepository, DeviceRepository $deviceRepository)
    {
        $lab = $labRepository->find($labId);
        $device = $deviceRepository->find($deviceId);

        try {
            $this->startDevice($lab, $device, $user);

            $this->addFlash('success', $device->getName() . ' has been started.');
        } catch (AlreadyInstancedException $exception) {
            $this->logger->debug("There is already an instance for device " . $device->getName() . " (" . $device->getUuid() . ") with UUID " . $exception->getInstance()->getUuid());
            $this->addFlash('warning', $device->getName() . ' is already instanced.');
        } catch (ClientException $exception) {
            $this->addFlash('danger', "Worker can't be reached. Please contact your administrator.");
            $this->logger->error(Psr7\str($exception->getRequest()));
            if ($exception->hasResponse()) {
                $this->logger->error(Psr7\str($exception->getResponse()));
            }
        } catch (ServerException $exception) {
            $this->addFlash('danger', "Device " . $device->getName() . " failed to start. Please verify your parameters or contact an administrator.");
            $this->logger->error(Psr7\str($exception->getRequest()));
            if ($exception->hasResponse()) {
                $this->logger->error(Psr7\str($exception->getResponse()));
            }
            $this->stopDevice($lab, $device, $user);
        } finally {
            return $this->redirectToRoute('show_lab', [
                'id' => $labId,
            ]);
        }
    }

    /**
     * @Route("/labs/{labId<\d+>}/device/{deviceId<\d+>}/stop", name="stop_lab_device", methods="GET")
     */
    public function stopDeviceAction(int $labId, int $deviceId, UserInterface $user, LabRepository $labRepository, DeviceRepository $deviceRepository)
    {
        $lab = $labRepository->find($labId);
        $device = $deviceRepository->find($deviceId);

        try {
            $this->stopDevice($lab, $device, $user);

            $this->addFlash('success', $device->getName() . ' has been stopped.');
        } catch (NotInstancedException $exception) {
            $this->logger->debug("There is no instance for device " . $device->getName() . " (" . $device->getUuid() . ") with UUID " . $exception->getInstance()->getUuid());
            $this->addFlash('warning', $device->getName() . ' is not instanced.');
        } catch (ClientException $exception) {
            $this->addFlash('danger', "Worker can't be reached. Please contact your administrator.");
            $this->logger->error(Psr7\str($exception->getRequest()));
            if ($exception->hasResponse()) {
                $this->logger->error(Psr7\str($exception->getResponse()));
            }
        } catch (ServerException $exception) {
            $this->addFlash('danger', "Device " . $device->getName() . " failed to stop. Please verify your parameters or contact an administrator.");
            $this->logger->error(Psr7\str($exception->getRequest()));
            if ($exception->hasResponse()) {
                $this->logger->error(Psr7\str($exception->getResponse()));
            }
        } finally {
            return $this->redirectToRoute('show_lab', [
                'id' => $labId,
            ]);
        }

        $this->stopDevice($lab, $device, $user);

        $this->addFlash('success', $device->getName() . ' has been stopped.');

        return $this->redirectToRoute('show_lab', [
            'id' => $labId
        ]);
    }

    /**
     * Instanciate and start a device from a lab.
     *
     * @param Lab $lab
     * @param Device $device
     * 
     * @throws AlreadyInstancedException If an instanciable object is already instancied.
     * @throws RequestException If something went wrong with the worker
     * 
     * @return void
     */
    private function startDevice(Lab $lab, Device $device, UserInterface $user)
    {
        $client = new Client();
        $serializer = $this->container->get('jms_serializer');
        $entityManager = $this->getDoctrine()->getManager();
        $labInstanceRepository = $this->getDoctrine()->getRepository(LabInstance::class);

        $labInstance = $labInstanceRepository->findByUserAndLab($user, $lab);

        if ($labInstance && $labInstance->isStarted()) {
            throw new AlreadyInstancedException($labInstance);
        } elseif (!$labInstance) {
            $labInstance = LabInstance::create()
                ->setLab($lab)
                ->setUser($user)
                ->setIsInternetConnected(false)
            ;
            $lab->addInstance($labInstance);
            $entityManager->persist($lab);
        }

        $deviceInstance = $labInstance->getDeviceInstance($device);

        if ($deviceInstance != null && $deviceInstance->isStarted()) {
            throw new AlreadyInstancedException($deviceInstance);
        } elseif ($deviceInstance == null) {
            $deviceInstance = DeviceInstance::create()
                ->setDevice($device)
                ->setUser($user)
                ->setLab($lab)
            ;
            $device->addInstance($deviceInstance);
            $labInstance->addDeviceInstance($deviceInstance);
            $entityManager->persist($deviceInstance);
        }

        // create nic instances as well
        foreach ($device->getNetworkInterfaces() as $networkInterface) {
            $networkInterfaceInstance = $deviceInstance->getNetworkInterfaceInstance($networkInterface);

            if ($networkInterfaceInstance == null) {
                $networkInterfaceInstance = NetworkInterfaceInstance::create()
                    ->setNetworkInterface($networkInterface)
                    ->setUser($user)
                    ->setLab($lab)
                ;

                // if vnc access is requested, ask for a free port and register it
                if ($networkInterface->getSettings()->getProtocol() == "VNC") {
                    $remotePort = $this->getRemoteAvailablePort();
                    $networkInterfaceInstance->setRemotePort($remotePort);
                    try {
                        $this->createDeviceInstanceProxyRoute($deviceInstance->getUuid(), $remotePort);
                    } catch (ServerException $exception) {
                        $this->logger->error($exception->getResponse()->getBody()->getContents());
                        throw $exception;
                    }
                }

                $networkInterface->addInstance($networkInterfaceInstance);
                $deviceInstance->addNetworkInterfaceInstance($networkInterfaceInstance);
                $entityManager->persist($networkInterfaceInstance);
            }
        }

        $entityManager->flush();

        $context = SerializationContext::create()->setGroups("start_lab");
        $labXml = $serializer->serialize($labInstance, 'json', $context);

        $deviceUuid = $deviceInstance->getUuid();

        $url = "http://" . $this->workerAddress . "/lab/device/{$deviceUuid}/start";
        $headers = [ 'Content-Type' => 'application/json' ];
        try {
            $response = $client->post($url, [
                'body' => $labXml,
                'headers' => $headers
            ]);
        } catch (RequestException $exception) {
            $this->logger->error($exception->getResponse()->getBody()->getContents());
            throw $exception;
            // dd($exception->getResponse()->getBody()->getContents(), $labXml, $lab->getInstances());
        }


        foreach ($device->getNetworkInterfaces() as $networkInterface) {
            $networkInterfaceInstance = $deviceInstance->getNetworkInterfaceInstance($networkInterface);

            $networkInterfaceInstance->setStarted(true);
            $entityManager->persist($networkInterfaceInstance);
        }

        $deviceInstance->setStarted(true);
        $entityManager->persist($deviceInstance);

        // check if the whole lab is started
        $isStarted = $lab->getDevices()->forAll(function ($index, $value) use ($labInstance) {
            /** @var Device $value */
            return $labInstance->getUserDeviceInstance($value) && $labInstance->getUserDeviceInstance($value)->isStarted();
        });
        $labInstance->setStarted($isStarted);
        $entityManager->persist($labInstance);

        $entityManager->flush();
    }

    /**
     * Instanciate and stop a device from a lab.
     *
     * @param Lab $lab
     * @param Device $device
     * 
     * @throws NotInstancedException If an instanciable object was not instancied.
     * 
     * @return void
     */
    private function stopDevice(Lab $lab, Device $device, UserInterface $user)
    {
        $client = new Client();
        $serializer = $this->container->get('jms_serializer');
        $entityManager = $this->getDoctrine()->getManager();
        $labInstanceRepository = $this->getDoctrine()->getRepository(LabInstance::class);

        $labInstance = $labInstanceRepository->findByUserAndLab($user, $lab);

        if ($labInstance == null) {
            throw new NotInstancedException($lab);
        }

        $deviceInstance = $labInstance->getDeviceInstance($device);

        if ($deviceInstance == null) {
            throw new NotInstancedException($device);
        }
        
        $context = SerializationContext::create()->setGroups("stop_lab");
        $labXml = $serializer->serialize($labInstance, 'json', $context);

        $deviceUuid = $deviceInstance->getUuid();

        $url = "http://" . $this->workerAddress . "/lab/device/" . $deviceUuid . "/stop";
        $headers = [ 'Content-Type' => 'application/json' ];
        
        $response = $client->post($url, [
            'body' => $labXml,
            'headers' => $headers
        ]);

        $this->deleteDeviceInstanceProxyRoute($deviceUuid);

        foreach ($device->getNetworkInterfaces() as $networkInterface) {
            $networkInterfaceInstance = $deviceInstance->getNetworkInterfaceInstance($networkInterface);

            if ($networkInterfaceInstance != null) {
                $networkInterface->removeInstance($networkInterfaceInstance);
                $deviceInstance->removeNetworkInterfaceInstance($networkInterfaceInstance);
                $entityManager->remove($networkInterfaceInstance);
                $entityManager->persist($networkInterface);
                $entityManager->persist($deviceInstance);
            }
        }
        
        // first, remove device instance
        $labInstance->removeDeviceInstance($deviceInstance);
        $device->removeInstance($deviceInstance);
        $entityManager->remove($deviceInstance);
        $entityManager->persist($labInstance);

        // then, if there is no device instance left for current user, delete lab instance
        if (! $labInstance->hasDeviceInstance()) {
            $this->disconnectLabInstance($labInstance);
            $entityManager->remove($labInstance);
            $lab->removeInstance($labInstance);
        } else { // otherwise, just tell the system the lab is not fully started
            $labInstance->setStarted(false);
        }
        
        $entityManager->persist($device);
        $entityManager->persist($lab);
        $entityManager->flush();
    }

    private function getRemoteAvailablePort(): int
    {
        $client = new Client();

        $url = "http://" . $this->workerAddress . "/worker/port/free";
        try {
            $response = $client->get($url);
        } catch (RequestException $exception) {
            throw $exception;
        }

        return (int) $response->getBody()->getContents();
    }

    /**
     * @param string $uuid
     * @param integer $remotePort
     * 
     * @throws RequestException 
     * 
     * @return void
     */
    private function createDeviceInstanceProxyRoute(string $uuid, int $remotePort)
    {
        $client = new Client();
        
        $url = 'http://localhost:' .
            getenv('WEBSOCKET_PROXY_API_PORT') .
            '/api/routes/device/' .
            $uuid
        ;
        try {
            $client->post($url, [
                'body' => '{
                    "target": "ws://' . $this->workerServer . ':' . ($remotePort + 1000) . '"
                }',
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ]);
        } catch (RequestException $exception) {
        } catch (ServerException $exception) {
            throw $exception;
        }
    }

    private function deleteDeviceInstanceProxyRoute(string $uuid)
    {
        $client = new Client();
        
        $url = 'http://localhost:' .
            getenv('WEBSOCKET_PROXY_API_PORT') .
            '/api/routes/device/' .
            $uuid
        ;
        try {
            $client->delete($url);
        } catch (RequestException $exception) {
        } catch (ServerException $exception) {
            throw $exception;
        }
    }

    /**
     * Return a string representing an available subnetwork in the specified CIDR.
     *
     * @param string $cidr
     * @param integer $maxSize
     * @return string|null CIDR notation of the subnet
     */
    private function getAvailableNetwork(string $cidr, int $maxSize): ?string
    {
        $networkRepository = $this->getDoctrine()->getRepository(Network::class);

        $network = IPTools\Network::parse($cidr);

        // Get all possible subnetworks from specified config
        $subnets = $network->moveTo(32 - log((float) $maxSize, 2));

        // If $subnets is empty, it means that user's config has a problem
        if (is_empty($subnets)) {
            throw new BadRequestHttpException('Your network configuration is wrong, please check the dotenv file.');
        }
        
        // Exclude all reserved subnetworks from the list
        foreach ($networkRepository->findAll() as $reservedNetwork) {
            $subnets->exclude(IPTools\Network::parse($reservedNetwork->CIDR));
        }

        // If subnets list is empty now, it means that every subnet is already allocated
        if (is_empty($subnets)) {
            // TODO: Create an new exception class
            throw new BadRequestHttpException(
                'No available subnetwork.' .
                'Please delete some networks or check your config and try again.'
            );
        }

        return (string)$subnets[0];
    }

    /**
     * @Route("/labs/{id<\d+>}/json", name="test_lab_json")
     */
    public function testLabSerializer(int $id, SerializerInterface $serializer, UserInterface $user, LabRepository $labRepository, LabInstanceRepository $labInstanceRepository)
    {
        $lab = $labRepository->find($id);
        $labInstance = $labInstanceRepository->findByUserAndLab($user, $lab);
        
        $context = SerializationContext::create();
        $context->setGroups(
            "stop_lab"
        );
        
        return new Response($serializer->serialize($labInstance, 'json', $context), 200, [
            "Content-Type" => "application/json"
        ]);
    }

    /**
     * @Route("/labs/{id<\d+>}/device/{deviceId<\d+>}/json", name="test_lab_device_json")
     */
    public function testLabDeviceSerializer(int $deviceId, SerializerInterface $serializer, UserInterface $user, DeviceRepository $deviceRepository, DeviceInstanceRepository $deviceInstanceRepository)
    {
        $device = $deviceRepository->find($deviceId);
        $deviceInstance = $deviceInstanceRepository->findByUserAndDevice($user, $device);

        $context = SerializationContext::create();
        $context->setGroups(
            'start_lab'
        );
        
        return new Response($serializer->serialize($deviceInstance, 'xml', $context), 200, [
            'Content-Type' => 'application/xml'
        ]);
    }

    /**
     * @Route("/labs/{id<\d+>}/device/{deviceId<\d+>}/view", name="view_lab_device")
     */
    public function viewLabDeviceAction(Request $request, int $id, int $deviceId, UserInterface $user, LabRepository $labRepository, DeviceRepository $deviceRepository, LabInstanceRepository $labInstanceRepository)
    {
        $lab = $labRepository->find($id);
        $device = $deviceRepository->find($deviceId);
        $labInstance = $labInstanceRepository->findByUserAndLab($user, $lab);
        $deviceInstance = $labInstance->getUserDeviceInstance($device);

        if ($request->get('size') == "fullscreen") {
            $fullscreen = true;
        } else {
            $fullscreen = false;
        }
        $protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === 0 ? 'wss://' : 'ws://';

        return $this->render(($fullscreen ? 'lab/vm_view_fullscreen.html.twig' : 'lab/vm_view.html.twig'), [
            'lab' => $lab,
            'device' => $device,
            'host' => $protocol."".($request->get('host') ?: getenv('WEBSOCKET_PROXY_SERVER')),
            'port' => $request->get('port') ?: getenv('WEBSOCKET_PROXY_PORT'),
            'path' => $request->get('path') ?: 'device/' . $deviceInstance->getUuid()
        ]);
    }

    /**
     * @Route("/labs/{id<\d+>}/connect", name="connect_internet")
     */
    public function connectLabAction(int $id, UserInterface $user, LabRepository $labRepository, LabInstanceRepository $labInstanceRepository, EntityManagerInterface $entityManager)
    {
        $lab = $labRepository->find($id);
        $labInstance = $labInstanceRepository->findByUserAndLab($user, $lab);

        try {
            $this->connectLabInstance($labInstance);
        } catch (ProcessFailedException $e) {
            $this->addFlash('danger', "Lab " . $lab->getName() . " failed to connect to internet. Please verify your parameters or contact an administrator.");
        }
        
        $labInstance->setIsInternetConnected(true);
        $entityManager->persist($labInstance);
        $entityManager->flush();
        $this->addFlash('success', 'The lab '.$lab->getName().' is connected.');

        return $this->redirectToRoute('show_lab', [
            'id' => $id
        ]);
    }

    private function connectLabInstance(LabInstance $labInstance)
    {
        $client = new Client();
        $serializer = $this->container->get('jms_serializer');

        if ($labInstance == null) {
            throw new NotInstancedException($labInstance->getLab());
        }

        $context = SerializationContext::create()->setGroups("start_lab");
        $serialized = $serializer->serialize($labInstance, 'json', $context);

        $url = "http://" . $this->workerAddress . "/lab/connect/internet";
        $headers = [ 'Content-Type' => 'application/json' ];
        try {
            $response = $client->post($url, [
                'body' => $serialized,
                'headers' => $headers
            ]);
        } catch (RequestException $exception) {
            dd($exception->getResponse()->getBody()->getContents(), $serialized, $labInstance);
        }
    }

    /**
     * @Route("/labs/{id<\d+>}/disconnect", name="disconnect_internet")
     */
    public function disconnectLabAction(int $id, UserInterface $user, LabRepository $labRepository, LabInstanceRepository $labInstanceRepository, EntityManagerInterface $entityManager)
    {
        $lab = $labRepository->find($id);
        $labInstance = $labInstanceRepository->findByUserAndLab($user, $lab);

        try {
            $this->disconnectLabInstance($labInstance);
        } catch (ProcessFailedException $e) {
            $this->addFlash('danger', "Lab " . $lab->getName() . " failed to disconnect to internet. Please verify your parameters or contact an administrator.");
        }
        
        $labInstance->setIsInternetConnected(false);
        $entityManager->persist($labInstance);
        $entityManager->flush();
        $this->addFlash('success', 'The lab '.$lab->getName().' is disconnected.');

        return $this->redirectToRoute('show_lab', [
            'id' => $id
        ]);
    }

    private function disconnectLabInstance(LabInstance $labInstance)
    {
        $client = new Client();
        $serializer = $this->container->get('jms_serializer');

        if ($labInstance == null) {
            throw new NotInstancedException($labInstance->getLab());
        }

        $context = SerializationContext::create()->setGroups("stop_lab");
        $serialized = $serializer->serialize($labInstance, 'json', $context);

        $url = "http://" . $this->workerAddress . "/lab/disconnect/internet";
        $headers = [ 'Content-Type' => 'application/json' ];
        
        $response = $client->post($url, [
            'body' => $serialized,
            'headers' => $headers
        ]);
    }
}
