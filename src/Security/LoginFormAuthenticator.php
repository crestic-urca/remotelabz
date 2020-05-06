<?php

namespace App\Security;

use DateTime;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class LoginFormAuthenticator extends AbstractFormLoginAuthenticator implements LogoutSuccessHandlerInterface
{
    use TargetPathTrait;

    private $entityManager;
    private $router;
    private $csrfTokenManager;
    private $passwordEncoder;
    private $JWTManager;
    /**
     * @var RefreshTokenManagerInterface
     */
    protected $refreshTokenManager;
    private $config;

    public function __construct(
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        CsrfTokenManagerInterface $csrfTokenManager,
        UserPasswordEncoderInterface $passwordEncoder,
        JWTTokenManagerInterface $JWTManager,
        RefreshTokenManagerInterface $refreshTokenManager,
        ContainerBagInterface $config
    ) {
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->JWTManager = $JWTManager;
        $this->refreshTokenManager = $refreshTokenManager;
        $this->config = $config;
    }

    public function supports(Request $request)
    {
        return 'login' === $request->attributes->get('_route')
            && $request->isMethod('POST');
    }

    public function getCredentials(Request $request)
    {
        $credentials = [
            'email' => $request->request->get('email'),
            'password' => $request->request->get('password'),
            'csrf_token' => $request->request->get('_csrf_token'),
        ];
        $request->getSession()->set(
            Security::LAST_USERNAME,
            $credentials['email']
        );

        return $credentials;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $token = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $credentials['email']]);

        if (!$user) {
            // fail authentication with a custom error
            throw new CustomUserMessageAuthenticationException('Email could not be found.');
        }

        if (!$user->isEnabled()) {
            throw new DisabledException();
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $response = new RedirectResponse('/');
        /** @var User $user */
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $request->get('email')]);

        $jwtToken = $this->JWTManager->create($user);
        $now = new DateTime();
        $jwtTokenCookie = Cookie::create('bearer', $jwtToken, $now->getTimestamp() + 24 * 3600);

        $response->headers->setCookie($jwtTokenCookie);
        // $response->headers->setCookie(Cookie::create('rt', $this->createRefreshToken($user)));
        $user->setLastActivity(new DateTime());
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        if ($request->query->has('ref_url')) {
            $response->setTargetUrl(urldecode($request->query->get('ref_url')));
        } else if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            $response->setTargetUrl($targetPath);
        } else {
            $response->setTargetUrl($this->router->generate('index'));
        }

        return $response;
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if ($request->hasSession()) {
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
        }

        $parameters = $request->query->has('ref_url') ? ['ref_url' => $request->query->get('ref_url')] : [];
        $url = $this->getLoginUrl($parameters);

        return new RedirectResponse($url);
    }

    protected function getLoginUrl($parameters = [])
    {
        return $this->router->generate('login', $parameters);
    }

    /**
     * @param Request $request
     *
     * @return Response never null
     */
    public function onLogoutSuccess(Request $request)
    {
        $response = new RedirectResponse('/');
        $response->headers->clearCookie($this->config->get('api_key_cookie_name'));

        if ($request->server->has('eppn')) {
            $response->setTargetUrl(
                $this->router->generate('shib_logout', [
                    'return' => $this->router->generate('login')
                ])
            );
        } else {
            $response->setTargetUrl($this->router->generate('login'));
        }

        return $response;
    }

    protected function createRefreshToken($user)
    {
        $valid = new \DateTime('now');
        $valid->add(new \DateInterval('P3D'));

        $refreshToken = $this->refreshTokenManager->create();
        $refreshToken->setUsername($user->getEmail());
        $refreshToken->setRefreshToken();
        $refreshToken->setValid($valid);

        $this->refreshTokenManager->save($refreshToken);

        return $refreshToken->getRefreshToken();
    }
}
