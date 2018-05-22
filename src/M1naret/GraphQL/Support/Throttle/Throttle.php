<?php
/**
 * Created by PhpStorm.
 * User: I.Kapelyushny
 * Date: 22.05.2018
 * Time: 16:15
 */

namespace M1naret\GraphQL\Support\Throttle;

use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\InteractsWithTime;
use Illuminate\Support\Str;
use M1naret\GraphQL\Error\ThrottleError;
use M1naret\GraphQL\Support\Field;
use Symfony\Component\HttpFoundation\Response;

class Throttle
{
    use InteractsWithTime;

    /**
     * The rate limiter instance.
     *
     * @var \Illuminate\Cache\RateLimiter
     */
    protected $limiter;

    /** @var Request */
    protected $request;

    /**
     * Create a new request throttler.
     *
     * @param Request $request
     * @param  \Illuminate\Cache\RateLimiter $limiter
     *
     * @return void
     */
    public function __construct(Request $request, RateLimiter $limiter)
    {
        $this->limiter = $limiter;
        $this->request = $request;
    }

    /**
     * @param Field $field
     *
     * @throws ThrottleError
     */
    public function check(Field $field)
    {
        $params = $field->throttle();

        $maxAttempts = array_get($params, 'attempts');
        $delay = array_get($params, 'timeout');

        $key = $this->resolveKey($field);

        $maxAttempts = $this->resolveMaxAttempts($maxAttempts);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            throw $this->buildException($key, $maxAttempts);
        }

        $this->limiter->hit($key, $delay);
    }

    protected function resolveKey(Field $field) : string
    {
        $keyParts = [
            \get_class($field),
        ];

        /** @var Authenticatable $user */
        if ($user = $this->request->user()) {
            $keyParts[] = $user->getAuthIdentifier();
        } else {
            array_push(
                $keyParts,
                $this->request->method(),
                $this->request->server('SERVER_NAME'),
                $this->request->server('HTTP_USER_AGENT'),
                $this->request->path(),
                $this->request->ip()
            );
        }

        return sha1(implode('|', $keyParts));
    }

    /**
     * Resolve the number of attempts if the user is authenticated or not.
     *
     * @param  int|string $maxAttempts
     *
     * @return int
     */
    protected function resolveMaxAttempts($maxAttempts): int
    {
        if (Str::contains($maxAttempts, '|')) {
            $maxAttempts = explode('|', $maxAttempts, 2)[$this->request->user() ? 1 : 0];
        }

        return (int)$maxAttempts;
    }

    /**
     * Create a 'too many attempts' exception.
     *
     * @param  string $key
     * @param  int    $maxAttempts
     *
     * @return ThrottleError
     */
    protected function buildException($key, $maxAttempts) : ThrottleError
    {
        $retryAfter = $this->getTimeUntilNextRetry($key);

        $headers = $this->getHeaders(
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts, $retryAfter),
            $retryAfter
        );

        return new ThrottleError($headers);
    }

    /**
     * Get the number of seconds until the next retry.
     *
     * @param  string $key
     *
     * @return int
     */
    protected function getTimeUntilNextRetry($key) : int
    {
        return $this->limiter->availableIn($key);
    }

    /**
     * Add the limit header information to the given response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response $response
     * @param  int                                        $maxAttempts
     * @param  int                                        $remainingAttempts
     * @param  int|null                                   $retryAfter
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function addHeaders(Response $response, $maxAttempts, $remainingAttempts, $retryAfter = null) : Response
    {
        $response->headers->add(
            $this->getHeaders($maxAttempts, $remainingAttempts, $retryAfter)
        );

        return $response;
    }

    /**
     * Get the limit headers information.
     *
     * @param  int      $maxAttempts
     * @param  int      $remainingAttempts
     * @param  int|null $retryAfter
     *
     * @return array
     */
    protected function getHeaders($maxAttempts, $remainingAttempts, $retryAfter = null) : array
    {
        $headers = [
            'X-RateLimit-Limit'     => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ];

        if (null !== $retryAfter) {
            $headers['Retry-After'] = $retryAfter;
            $headers['X-RateLimit-Reset'] = $this->availableAt($retryAfter);
        }

        return $headers;
    }

    /**
     * Calculate the number of remaining attempts.
     *
     * @param  string   $key
     * @param  int      $maxAttempts
     * @param  int|null $retryAfter
     *
     * @return int
     */
    protected function calculateRemainingAttempts($key, $maxAttempts, $retryAfter = null): int
    {
        if (null === $retryAfter) {
            return $this->limiter->retriesLeft($key, $maxAttempts);
        }

        return 0;
    }
}