<?php

namespace ByJG\RestServer\Exception;

/**
 * Thrown when a container is set on the Server but the route's controller is not
 * registered in it.
 *
 * Falling back to `new $className()` here would silently skip constructor injection,
 * so the controller would be built with no dependencies and fail somewhere far from
 * the real cause. Register the controller, or pass `allowUnregistered: true` to
 * Server::withContainer() while migrating.
 */
class ControllerNotRegisteredException extends Error501Exception
{
    //put your code here
}
