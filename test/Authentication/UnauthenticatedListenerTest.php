<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\MvcAuth\Authentication;

use Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationPostListener;
use Laminas\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\Authentication\Result as AuthenticationResult;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\Response;
use LaminasTest\ApiTools\MvcAuth\TestAsset;
use PHPUnit_Framework_TestCase as TestCase;

class DefaultAuthenticationPostListenerTest extends TestCase
{
    public function setUp()
    {
        $response   = new HttpResponse();
        $mvcEvent   = new MvcEvent();
        $mvcEvent->setResponse($response);
        $this->mvcAuthEvent = $this->createMvcAuthEvent($mvcEvent);

        $this->listener = new DefaultAuthenticationPostListener();
    }

    public function createMvcAuthEvent(MvcEvent $mvcEvent)
    {
        $this->authentication = new TestAsset\AuthenticationService();
        $this->authorization  = $this->getMockBuilder('Laminas\ApiTools\MvcAuth\Authorization\AuthorizationInterface')->getMock();
        return new MvcAuthEvent($mvcEvent, $this->authentication, $this->authorization);
    }

    public function testReturnsNullWhenEventDoesNotHaveAuthenticationResult()
    {
        $listener = $this->listener;
        $this->assertNull($listener($this->mvcAuthEvent));
    }

    public function testReturnsNullWhenAuthenticationResultIsValid()
    {
        $listener = $this->listener;
        $this->mvcAuthEvent->setAuthenticationResult(new AuthenticationResult(1, 'foo'));
        $this->assertNull($listener($this->mvcAuthEvent));
    }

    public function testReturnsComposedEventResponseWhenNotAuthorizedButNotAnHttpResponse()
    {
        $listener = $this->listener;
        $this->mvcAuthEvent->setAuthenticationResult(new AuthenticationResult(0, 'foo'));
        $response = new Response;
        $this->mvcAuthEvent->getMvcEvent()->setResponse($response);
        $this->assertSame($response, $listener($this->mvcAuthEvent));
    }

    public function testReturns401ResponseWhenNotAuthorizedAndHttpResponseComposed()
    {
        $listener = $this->listener;
        $this->mvcAuthEvent->setAuthenticationResult(new AuthenticationResult(0, 'foo'));
        $response = $this->mvcAuthEvent->getMvcEvent()->getResponse();
        $this->assertSame($response, $listener($this->mvcAuthEvent));
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Unauthorized', $response->getReasonPhrase());
    }
}
