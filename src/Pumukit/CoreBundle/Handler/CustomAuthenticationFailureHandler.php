<?php

namespace Pumukit\CoreBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Symfony\Component\Security\Http\HttpUtils;

class CustomAuthenticationFailureHandler extends DefaultAuthenticationFailureHandler
{
    private const RETURNED_ROUTE = 'pumukit_auth';
    private const EXCEPTION_MESSAGE = 'Invalid login';
    private $documentManager;

    public function __construct(HttpKernelInterface $httpKernel, HttpUtils $httpUtils, DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
        parent::__construct($httpKernel, $httpUtils);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $username = $request->request->get('_username');
        if (!$username) {
            throw new UsernameNotFoundException(self::EXCEPTION_MESSAGE);
        }

        $user = $this->documentManager->getRepository(User::class)->findOneBy(['username' => $username]);
        if (!$user) {
            throw new UsernameNotFoundException(self::EXCEPTION_MESSAGE);
        }

        $this->updateUser($user);

        $this->setSessionException($request->getSession(), $exception);

        return $this->httpUtils->createRedirectResponse($request, self::RETURNED_ROUTE);
    }

    private function updateUser(User $user): void
    {
        $user->addLoginAttempt();
        $this->checkToEnableUser($user);
        $this->documentManager->flush();
    }

    private function checkToEnableUser(User $user): void
    {
        if (!$user->isEnabled()) {
            $now = new \DateTime();

            $lastLoginAttempt = $user->getLastLoginAttempt();
            $lastLoginAttempt->add(new \DateInterval('PT'.User::MAX_USER_TIME_MIN_LOCK.'M'));

            if ($lastLoginAttempt < $now) {
                $user->setEnabled(true);
                $user->setLoginAttempt(1);
            }
        }
    }

    private function setSessionException(?SessionInterface $session, AuthenticationException $exception)
    {
        if ($session) {
            $session->set(Security::AUTHENTICATION_ERROR, $exception);
        }
    }
}
