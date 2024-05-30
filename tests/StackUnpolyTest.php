<?php

namespace Webstronauts\Unpoly\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Webstronauts\Unpoly\StackUnpoly;
use Webstronauts\Unpoly\Unpoly;

class StackUnpolyTest extends TestCase
{
    public function testPassesResponseToUnpolyInstance()
    {
        $request = new Request();
        $response = new Response();

        $app = $this->getMockBuilder(HttpKernelInterface::class)->getMock();
        $app->expects($this->once())->method('handle')->willReturn($response);

        $unpoly = $this->getMockBuilder(Unpoly::class)->getMock();
        $unpoly->expects($this->once())->method('decorateResponse')->with($request, $response);

        $stack = new StackUnpoly($app, $unpoly);
        $stack->handle($request, HttpKernelInterface::MAIN_REQUEST);
    }

    public function testSkipsSubRequests()
    {
        $request = new Request();
        $response = new Response();

        $app = $this->getMockBuilder(HttpKernelInterface::class)->getMock();
        $app->expects($this->once())->method('handle')->willReturn($response);

        $unpoly = $this->getMockBuilder(Unpoly::class)->getMock();
        $unpoly->expects($this->never())->method('decorateResponse');

        $stack = new StackUnpoly($app, $unpoly);
        $stack->handle($request, HttpKernelInterface::SUB_REQUEST);
    }
}
