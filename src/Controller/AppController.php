<?php

namespace App\Controller;

use App\Utils\RequestType;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Provides common need features and custom JSON handling using JMSSerializer.
 *
 * @author Julien Hubert <julien.hubert@outlook.com>
 */
class AppController extends AbstractController
{
    final protected function json($data, int $status = 200, array $headers = [], array $context = []): JsonResponse
    {
        $serializer = SerializerBuilder::create()->build();

        if (null !== $data) {
            $data = $serializer->serialize($data, 'json');
        } else {
            $data = '';
        }

        $headers['Content-Type'] = 'application/json';

        return new JsonResponse($data, $status, $headers, true);
    }

    /**
     * @return string
     */
    protected function generateUniqueFileName()
    {
        // md5() reduces the similarity of the file names generated by
        // uniqid(), which is based on timestamps
        return md5(uniqid());
    }

    protected function getRequestedFormat(Request $request): ?string
    {
        if ($request->headers->get('Accept') === 'application/json')
        {
            return RequestType::JsonRequest;
        }
        if ($request->getRequestFormat() === 'json')
        {
            return RequestType::JsonRequest;
        }

        return RequestType::HtmlRequest;
    }
}