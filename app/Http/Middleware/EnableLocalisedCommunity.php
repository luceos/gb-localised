<?php

namespace App\Http\Middleware;

use Flarum\Event\ConfigureModelDefaultAttributes;
use Flarum\Http\AccessToken;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class EnableLocalisedCommunity implements MiddlewareInterface
{
    const localisations = ['fr', 'de'];
    const fallback = 'de';
    const ignore = [User::class, AccessToken::class];
    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri();
        $identified = substr($uri->getPath(), 0, 2);

        $use = self::fallback;

        if (in_array($identified, self::localisations)) {
            $use = $identified;
        }

        // Ignore the fallback, which will use the regular table at all times.
        if ($use !== self::fallback) {
            /** @var Dispatcher $events */
            $events = app(Dispatcher::class);

            $events->listen(ConfigureModelDefaultAttributes::class, function (ConfigureModelDefaultAttributes $event) use ($use) {
                // Ignore models that we need to store globally.
                if(! in_array(get_class($event->model), self::ignore)) {
                    // Sets model to use "<table>_<locale>"
                    $event->model->setTable($event->model->getTable() . '_' . $use);
                }
            });
        }
    }
}
