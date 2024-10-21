<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Passport\TokenRepository;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Bridge\AccessTokenRepository;
use League\OAuth2\Server\ResourceServer;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use League\OAuth2\Server\CryptKey;
use Illuminate\Support\Facades\Log;
use League\OAuth2\Server\Exception\OAuthServerException;

class VerifyAccessToken
{
    protected $server;
    protected $tokenRepository;
    protected $clientRepository;
    protected $accessTokenRepository;

    public function __construct(
        TokenRepository $tokenRepository,
        ClientRepository $clientRepository,
        AccessTokenRepository $accessTokenRepository
    ) {
        $this->tokenRepository = $tokenRepository;
        $this->clientRepository = $clientRepository;
        $this->accessTokenRepository = $accessTokenRepository;
    }

    public function handle(Request $request, Closure $next)
    {
        try {
            $bearerToken = $request->bearerToken();
            Log::debug('Incoming Authorization header', [
                'authorization' => $request->header('Authorization')
            ]);

            $bearerToken = $request->bearerToken();
            if (!$bearerToken) {
                throw new \Exception('No bearer token found in the request');
            }

            $token = $this->tokenRepository->find($bearerToken);
            if (!$token) {
                throw new \Exception('Token not found');
            }

            if ($token->revoked) {
                throw new \Exception('Token has been revoked');
            }

            if ($token->expires_at && $token->expires_at->isPast()) {
                throw new \Exception('Token has expired');
            }

            // Set the authenticated user
            $request->setUserResolver(function () use ($token) {
                return $token->user;
            });

            $request->attributes->set('oauth_access_token_id', $token->id);
            $request->attributes->set('oauth_client_id', $token->client_id);
            $request->attributes->set('oauth_user_id', $token->user_id);
            $request->attributes->set('oauth_scopes', $token->scopes);

            return $next($request);
        } catch (\Exception $e) {
            Log::error('Token validation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => $e->getMessage()
            ], 401);
        }
    }
}
