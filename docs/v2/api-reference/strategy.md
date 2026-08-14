# Strategy

## `StrategyInterface`

```php
public function handlerMethod(MessageInterface $message): string;
```

The single authority for choosing the handler method name. `MessageHandlerMiddleware` depends only on
this interface and never knows which concrete strategy is wired.

## `Strategy\HandleStrategy`

The default. Returns `'handle'`, so handle-based handlers keep working.

## `Strategy\ClassnameStrategy`

Opt-in. Inflects the message's short class name with `lcfirst`: `App\Command\CreateUser` becomes
`createUser`.

## Wiring

The default `ConfigProvider` aliases `StrategyInterface` to `HandleStrategy`. Opt into named methods by
changing that alias:

```php
use Webware\MessageBus\Strategy\ClassnameStrategy;
use Webware\MessageBus\StrategyInterface;

return [
    'dependencies' => [
        'aliases' => [
            StrategyInterface::class => ClassnameStrategy::class,
        ],
    ],
];
```
