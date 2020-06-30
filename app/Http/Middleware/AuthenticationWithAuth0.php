<?php
namespace App\Http\Middleware;

use Closure;
use Auth0\SDK\JWTVerifier;

class AuthenticationWithAuth0
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($this->isAuthorized($request)) {
            return $next($request);
        } else {
            return response('Unauthorized.', 401);
        }
    }

    public function isAuthorized($request)
    {
        if (!$request->header('Authorization')) {
            return false;
        }

        $authType = null;
        $authData = null;

        // Extract the auth type and the data from the Authorization header.
        @list($authType, $authData) = explode(" ", $request->header('Authorization'), 2);

        // If the Authorization Header is not a bearer type, return a 401.
        if ($authType != 'Bearer') {
            return false;
        }

        // Verify/Decode JWT in the Authorization Header
        try {
          $verifier = new JWTVerifier([
            'supported_algs' => ['RS256'],
            'valid_audiences' => [getenv('AUDIENCE')],
            'authorized_iss' => [getenv('ISSUER')]
          ]);

          $jwt = $verifier->verifyAndDecode($authData);
        }
        catch(\Auth0\SDK\Exception\CoreException $e) {
          // We encountered an error, return a 401.
          return false;
        }

        return true;
    }
}
