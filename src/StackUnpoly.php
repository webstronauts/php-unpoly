<?php

namespace Webstronauts\Unpoly;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class StackUnpoly implements HttpKernelInterface
{
    /**
     * @var \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    private $app;

    /**
     * @var \Webstronauts\Unpoly\Unpoly
     */
    private $unpoly;

    /**
     * Constructor.
     *
     * @param  \Symfony\Component\HttpKernel\HttpKernelInterface  $app
     * @param  \Webstronauts\Unpoly\Unpoly  $unpoly
     * @return void
     */
    public function __construct(HttpKernelInterface $app, Unpoly $unpoly)
    {
        $this->app = $app;
        $this->unpoly = $unpoly;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true): Response
    {
        $response = $this->app->handle($request, $type, $catch);

        if (self::MASTER_REQUEST === $type) {
            $this->unpoly->decorateResponse($request, $response);
        }

        return $response;
    }
}
