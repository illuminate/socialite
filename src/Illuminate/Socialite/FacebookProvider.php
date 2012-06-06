<?php namespace Illuminate\Socialite;

use Guzzle\Http\ClientInterface;
use Guzzle\Http\Message\Response;
use Symfony\Component\HttpFoundation\Request;

class FacebookProvider extends Provider {

	/**
	 * The scope delimiter.
	 *
	 * @var string
	 */
	protected $scopeDelimiter = ',';

	/**
	 * Get the auth end-point URL for a provider.
	 *
	 * @return string
	 */
	protected function getAuthEndpoint()
	{
		return 'https://www.facebook.com/dialog/oauth';
	}

	/**
	 * Get the access token end-point URL for a provider.
	 *
	 * @return string
	 */
	protected function getAccessEndpoint()
	{
		return 'https://graph.facebook.com/oauth/access_token';
	}

	/**
	 * Get the user data end-point URL for the provider.
	 *
	 * @return string
	 */
	protected function getUserDataEndpoint()
	{
		return 'https://graph.facebook.com/me';
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
	 * Execute the request to get the access token.
	 *
	 * @param  Guzzle\Http\ClientInterface  $client
	 * @param  array  $options
	 * @return Guzzle\Http\Message\Response
	 */
	protected function executeAccessRequest(ClientInterface $client, $options)
	{
		$url = $this->getAccessEndpoint().'?'.http_build_query($options);

		return $client->get($url)->send();
	}

	/**
	 * Get the default scopes for the provider.
	 *
	 * @return array
	 */
	public function getDefaultScope()
	{
		return array();
	}

}