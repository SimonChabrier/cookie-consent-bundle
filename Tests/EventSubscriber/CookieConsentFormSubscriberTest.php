<?php

declare(strict_types=1);

/*
 * This file is part of the ConnectHolland CookieConsentBundle package.
 * (c) Connect Holland.
 */

namespace ConnectHolland\CookieConsentBundle\Tests\EventSubscriber;

use ConnectHolland\CookieConsentBundle\Cookie\CookieHandler;
use ConnectHolland\CookieConsentBundle\Cookie\CookieLogger;
use ConnectHolland\CookieConsentBundle\EventSubscriber\CookieConsentFormSubscriber;
use ConnectHolland\CookieConsentBundle\Form\CookieConsentType;
use ConnectHolland\CookieConsentBundle\Enum\CookieNameEnum;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class CookieConsentFormSubscriberTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $formFactoryInterface;

    /**
     * @var MockObject
     */
    private $cookieLogger;

    /**
     * @var MockObject
     */
    private $cookieHandler;

    public function setUp(): void
    {
        $this->formFactoryInterface  = $this->createMock(FormFactoryInterface::class);
        $this->cookieLogger          = $this->createMock(CookieLogger::class);
        $this->cookieHandler         = $this->createMock(CookieHandler::class);
    }

    public function testGetSubscribedEvents(): void
    {
        $expectedEvents = [
           KernelEvents::RESPONSE => ['onResponse'],
        ];

        $cookieConsentFormSubscriber = new CookieConsentFormSubscriber($this->formFactoryInterface, $this->cookieLogger, $this->cookieHandler, true);
        $this->assertSame($expectedEvents, $cookieConsentFormSubscriber->getSubscribedEvents());
    }

    public function testOnResponse(): void
    {
        $request  = new Request();
        $response = new Response();
        $event    = $this->getResponseEvent($request, $response);

        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $form
            ->expects($this->once())
            ->method('getData')
            ->willReturn([]);

        $this->formFactoryInterface
            ->expects($this->once())
            ->method('create')
            ->with(CookieConsentType::class)
            ->willReturn($form);

        $this->cookieLogger
            ->expects($this->once())
            ->method('log');

        $cookieConsentFormSubscriber = new CookieConsentFormSubscriber($this->formFactoryInterface, $this->cookieLogger, $this->cookieHandler, true);
        $cookieConsentFormSubscriber->onResponse($event);
    }

    public function testOnResponseWithLoggerDisabled(): void
    {
        $request  = new Request();
        $response = new Response();
        $event    = $this->getResponseEvent($request, $response);

        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $form
            ->expects($this->once())
            ->method('getData')
            ->willReturn([]);

        $this->formFactoryInterface
            ->expects($this->once())
            ->method('create')
            ->with(CookieConsentType::class)
            ->willReturn($form);

        $this->cookieLogger
            ->expects($this->never())
            ->method('log');

        $cookieConsentFormSubscriber = new CookieConsentFormSubscriber($this->formFactoryInterface, $this->cookieLogger, $this->cookieHandler, false);
        $cookieConsentFormSubscriber->onResponse($event);
    }

    public function testOnResponseWithUnfoundResponseEvent(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No ResponseEvent class found');

        $cookieConsentFormSubscriber = new CookieConsentFormSubscriber($this->formFactoryInterface, $this->cookieLogger, $this->cookieHandler, false);
        $cookieConsentFormSubscriber->onResponse($this->createMock(KernelEvent::class));
    }

    public function testGetCookieConsentKeyGeneratesRandomHexString(): void
    {
        $request = new Request();

        $cookieConsentFormSubscriber = new CookieConsentFormSubscriber(
            $this->formFactoryInterface,
            $this->cookieLogger,
            $this->cookieHandler,
            true
        );

        $reflectionMethod = new \ReflectionMethod($cookieConsentFormSubscriber, 'getCookieConsentKey');
        $reflectionMethod->setAccessible(true);
        $key = $reflectionMethod->invoke($cookieConsentFormSubscriber, $request);

        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $key);
    }

    public function testGetCookieConsentKeyReturnsExistingKey(): void
    {
        $existingKey = 'existing-key';
        $request = new Request();
        $request->cookies->set(CookieNameEnum::COOKIE_CONSENT_KEY_NAME, $existingKey);

        $cookieConsentFormSubscriber = new CookieConsentFormSubscriber(
            $this->formFactoryInterface,
            $this->cookieLogger,
            $this->cookieHandler,
            true
        );

        $reflectionMethod = new \ReflectionMethod($cookieConsentFormSubscriber, 'getCookieConsentKey');
        $reflectionMethod->setAccessible(true);
        $key = $reflectionMethod->invoke($cookieConsentFormSubscriber, $request);

        $this->assertSame($existingKey, $key);
    }

    /**
     * @return ResponseEvent|FilterResponseEvent
     */
    private function getResponseEvent(Request $request, Response $response)
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        if (class_exists(ResponseEvent::class)) {
            return new ResponseEvent($kernel, $request, 200, $response);
        } else {
            // Support for Symfony 3.4 & < 4.3
            return new FilterResponseEvent($kernel, $request, 200, $response);
        }
    }
}
