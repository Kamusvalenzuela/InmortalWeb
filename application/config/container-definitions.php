<?php

declare(strict_types=1);

use App\Preferences;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

return [
    LoggerInterface::class => function (ContainerInterface $container): LoggerInterface {
        // Get the preferences from the container.
        $preferences = $container->get(Preferences::class);

        // Instantiate a new logger and push a handler into the logger.
        $logger = new Logger('slim4-skeleton');
        $logger->pushHandler(
            new RotatingFileHandler(
                $preferences->getRootPath() . '/logs/slim4-skeleton.log'
            )
        );

        return $logger;
    },
    Twig::class => function (ContainerInterface $container): Twig {
        // Get the preferences from the container.
        $preferences = $container->get(Preferences::class);

        // Instantiate twig.
        return Twig::create(
            $preferences->getRootPath() . '/application/templates/' . $preferences->getThemeName(),
            [
                'cache' => $preferences->getRootPath() . '/cache',
                'auto_reload' => true,
                'debug' => false,
                'strict_variables' => false
            ]
        );
    },
];
