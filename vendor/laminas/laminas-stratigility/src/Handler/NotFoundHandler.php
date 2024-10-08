<?php

declare(strict_types=1);

namespace Laminas\Stratigility\Handler;

use Closure;
use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function sprintf;

final class NotFoundHandler implements RequestHandlerInterface
{
    /** @var Closure(): ResponseInterface */
    private Closure $responseFactory;

    /**
     * @param callable(): ResponseInterface $responseFactory A factory capable of returning an
     *     empty ResponseInterface instance to update and return when returning
     *     an 404 response.
     */
    public function __construct(callable $responseFactory)
    {
        $this->responseFactory = static fn(): ResponseInterface => $responseFactory();
    }

    /**
     * Creates and returns a 404 response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var ResponseInterface $response */
        $response = ($this->responseFactory)()
            ->withStatus(StatusCode::STATUS_NOT_FOUND);
        $response->getBody()->write(sprintf(
            'Cannot %s %s',
            $request->getMethod(),
            (string) $request->getUri()
        ));
        return $response;
    }
}
