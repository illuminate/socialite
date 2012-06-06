<?php namespace Illuminate\Socialite;

use Guzzle\Http\Message\Response;
use Symfony\Component\HttpFoundation\Request;

class GoogleProvider extends Provider {

	/**
	 * Get the auth end-point URL for a provider.
	 *
	 * @return string
	 */
	protected function getAuthEndpoint()
	{
		return 'https://accounts.google.com/o/oauth2/auth';
	}

	/**
	 * Get the access token end-point URL for a provider.
	 *
	 * @return string
	 */
	protected function getAccessEndpoint()
	{
		return 'https://accounts.google.com/o/oauth2/token';
	}

	/**
	 * Get an array of query string options for a grant type.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request
	 * @param  string  $grantType
	 * @param  array  $options
	 * @return array
	 */
	protected function getGrantTypeOptions(Request $request, $grantType, $options)
	{
		return array();
	}

	/**
	 * Get an array of parameters from the access token response.
	 *
	 * @param  Guzzle\Http\Message\Response  $response
	 * @return array
	 */
	protected function parseAccessResponse(Response $response)
	{
		die(var_dump($response->getBody()));
	}

	/**
	 * Create an access token with the given parameters.
	 *
	 * @param  array  $parameters
	 * @return AccessTokenInterface
	 */
	protected function createAccessToken(array $parameters)
	{
		//
	}

}