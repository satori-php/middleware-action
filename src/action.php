<?php

/**
 * @author    Yuriy Davletshin <yuriy.davletshin@gmail.com>
 * @copyright 2017 Yuriy Davletshin
 * @license   MIT
 */

declare(strict_types=1);

namespace Satori\Middleware\Action;

use Satori\Application\ApplicationInterface;

/**
 * Initializes the action middleware.
 *
 * @param ApplicationInterface  $app   The application.
 * @param string                $id    The unique name of the middleware.
 * @param array<string, string> $names 
 *    The array with names `['error_action' => 'errorAction']`.
 */
function init(ApplicationInterface $app, string $id, array $names)
{
    $app[$id] = function (\Generator $next) use ($app, $names) {
        $app->notify('start_action');
        $capsule = yield;
        $capsule['http.status'] = $capsule['http.status'] ?? 500;
        switch ($capsule['http.status']) {
            case 403:
            case 404:
            case 405:
            case 500:
                $capsule['action'] = $names['error_action'];
        }
        if ($capsule['action']) {
            $capsule['http.headers'] = $capsule['http.headers'] ?? [];
            $action = $app->{$capsule['action']};
            try {
                $capsule = $action($capsule);
            } catch (\RuntimeException $e) {
                $capsule['http.status'] = 500;
                $capsule['exception'] = $e;
            }
            if (isset($capsule['exception']) || isset($capsule['error.message'])) {
                $action = $app->{$names['error_action']};
                $capsule = $action($capsule);
            }
        }
        $app->notify('finish_action');
        $next->send($capsule);

        return $next->getReturn();
    };
}
