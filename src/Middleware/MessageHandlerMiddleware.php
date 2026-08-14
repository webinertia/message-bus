<?php

declare(strict_types=1);

namespace Webware\MessageBus\Middleware;

use Override;
use TypeError;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\Exception\HandlerMethodNotFoundException;
use Webware\MessageBus\MessageHandlerResolverInterface;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MiddlewareInterface;
use Webware\MessageBus\PipelineHandlerInterface;
use Webware\MessageBus\QueryHandlerInterface;
use Webware\MessageBus\ResultInterface;
use Webware\MessageBus\StrategyInterface;

use function get_debug_type;
use function is_callable;
use function sprintf;

/**
 * @internal
 */
final readonly class MessageHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private MessageHandlerResolverInterface $resolver,
        private StrategyInterface $strategy,
    ) {}

    /**
     * @throws HandlerMethodNotFoundException
     */
    #[Override]
    public function process(
        MessageInterface $message,
        PipelineHandlerInterface $next,
    ): ResultInterface {
        $resolved = $this->resolver->resolve($message);
        $method   = $this->strategy->match($message);

        if (! is_callable([$resolved, $method])) {
            throw HandlerMethodNotFoundException::forMethod($resolved, $method);
        }

        return $next->handle($this->coerceResult(
            $resolved->{$method}($message),
            $resolved,
            $method,
        ));
    }

    private function coerceResult(
        mixed $result,
        CommandHandlerInterface|QueryHandlerInterface $handler,
        string $method,
    ): ResultInterface {
        if (! $result instanceof ResultInterface) {
            throw new TypeError(sprintf(
                'Handler "%s::%s()" must return %s, %s returned.',
                $handler::class,
                $method,
                ResultInterface::class,
                get_debug_type($result),
            ));
        }

        return $result;
    }
}
