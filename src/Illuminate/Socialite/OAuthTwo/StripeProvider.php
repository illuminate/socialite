<?php namespace Illuminate\Socialite\OAuthTwo;

use Guzzle\Http\ClientInterface;
use Guzzle\Http\Message\Response;
use Symfony\Component\HttpFoundation\Request;

class StripeProvider extends OAuthTwoProvider {

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
		return 'https://connect.stripe.com/oauth/authorize';
	}

	/**
	 * Get the access token end-point URL for a provider.
	 *
	 * @return string
	 */
	protected function getAccessEndpoint()
	{
		return 'https://connect.stripe.com/oauth/token';
	}

	/**
	 * Get the user data end-point URL for the provider.
	 *
	 * @return string
	 */
	protected function getUserDataEndpoint()
	{
		return '';
	}

	/**
	 * Get the default scopes for the provider.
	 *
	 * @return array
	 */
	public function getDefaultScope()
	{
		return array('read_write');
	}

}