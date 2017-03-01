<?php
declare(strict_types=1);


$app['middleware.action'] = function (\Generator $next) use ($app) {
    $app->notify('start_action');
    $capsule = yield;
    $capsule['headers'] = [];
    $errorActionName = $app['service.error_action'] ?? 'errorAction';
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
    $capsule = $action($capsule);
    if ($capsule->hasError()) {
        $action = $app->$errorActionName;
        $capsule = $action($capsule);
    }
    $app->notify('finish_action');
    $next->send($capsule);

    return $next->getReturn();
};
