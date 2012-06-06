<?php namespace Illuminate\Socialite;

use Guzzle\Http\Message\Response;
use Symfony\Component\HttpFoundation\Request;

class GoogleStrategy extends Strategy {

	/**
	 * Get the auth end-point URL for a provider.
	 *
	 * @return string
	 */
	protected function getAuthEndpoint()
	{

	}

	/**
	 * Get the access token end-point URL for a provider.
	 *
	 * @return string
	 */
	protected function getAccessEndpoint()
	{

	}

	/**
	 * Get an array of query string options for a grant type.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request
	 * @param  string $grantType
	 * @return array
	 */
	protected function getGrantTypeOptions(Request $request, $grantType)
	{

	}

	/**
	 * Get an array of parameters from the access token response.
	 *
	 * @param  Guzzle\Http\Message\Response  $response
	 * @return array
	 */
	protected function parseAccessResponse(Response $response)
	{

	}

	/**
	 * Create an access token with the given parameters.
	 *
	 * @param  array  $parameters
	 * @return AccessTokenInterface
	 */
	protected function createAccessToken(array $parameters)
	{
		
	}

}