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
        $capsule['headers'] = [];
        $errorActionName = $names['error_action'];
        switch ($capsule['http.status']) {
            case 200:
                break;

            case 404:
            case 405:
                $capsule['action'] = $errorActionName;
                break;

            default:
                $capsule['http.status'] = 500;
                $capsule['action'] = $errorActionName;
                break;
        }
        $action = $app->{$capsule['action']};
        try {
            $capsule = $action($capsule);
        } catch (\Exception $e) {
            $capsule->setError(500, $e->getMessage());
        }
        if ($capsule->hasError()) {
            $action = $app->$errorActionName;
            $capsule = $action($capsule);
        }
        $app->notify('finish_action');
        $next->send($capsule);

        return $next->getReturn();
    };
}
