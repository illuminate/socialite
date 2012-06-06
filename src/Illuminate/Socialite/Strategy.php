<?php namespace Illuminate\Socialite;

use Guzzle\Http\ClientInterface;
use Guzzle\Http\Message\Response;
use Symfony\Component\HttpFoundation\Request;

abstract class Strategy {

	/**
	 * The client ID for the provider.
	 *
	 * @var string
	 */
	protected $clientId;

	/**
	 * The secret key for the provider.
	 *
	 * @var string
	 */
	protected $secret;

	/**
	 * Create a new strategy instance.
	 *
	 * @param  string  $clientId
	 * @param  string  $secret
	 * @return void
	 */
	public function __construct($clientId, $secret)
	{
		$this->secret = $secret;
		$this->clientId = $clientId;
	}

	/**
	 * Get the auth end-point URL for a provider.
	 *
	 * @return string
	 */
	abstract protected function getAuthEndpoint();

	/**
	 * Get the access token end-point URL for a provider.
	 *
	 * @return string
	 */
	abstract protected function getAccessEndpoint();

	/**
	 * Get an array of query string options for a grant type.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request
	 * @param  string $grantType
	 * @return array
	 */
	abstract protected function getGrantTypeOptions(Request $request, $grantType);

	/**
	 * Get an array of parameters from the access token response.
	 *
	 * @param  Guzzle\Http\Message\Response  $response
	 * @return array
	 */
	abstract protected function parseAccessResponse(Response $response);

	/**
	 * Create an access token with the given parameters.
	 *
	 * @param  array  $parameters
	 * @return AccessTokenInterface
	 */
	abstract protected function createAccessToken(array $parameters);

	/**
	 * Get the URL to the provider's auth end-point.
	 *
	 * @param  string  $callbackUrl
	 * @param  string  $state
	 * @param  array   $options
	 * @return string
	 */
	public function getAuthUrl($callbackUrl, $state, array $options = array())
	{
		return $this->buildAuthUrl($callbackUrl, $state, $options);
	}

	/**
	 * Get the user's access token.
	 *
	 * @param  Guzzle\Http\ClientInterface  $client
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  array  $options
	 * @return Illuminate\Socialite\AccessTokenInterface
	 */
	public function getAccessToken(ClientInterface $client, Request $request, array $options = array())
	{
		// If the "grant_type" option is not set, we will default it to the value
		// needed to get the access token, which is the most likely candidate
		// for the value and makes for a good default value for us to set.
		if ( ! isset($options['grant_type']))
		{
			$options['grant_type'] = 'authorization_code';
		}

		$url = $this->buildAccessUrl($request, $options);

		return $this->createAccessToken($this->parseAccessResponse($client->get($url)->send()));
	}

	/**
	 * Create the URL for the provider's auth end-point.
	 *
	 * @param  string  $callbackUrl
	 * @param  string  $state
	 * @param  array   $options
	 * @return string
	 */
	protected function buildAuthUrl($callbackUrl, $state, $options)
	{
		$defaults = $this->buildDefaultAuthQuery($callbackUrl, $state);

		$query = array_merge($defaults, $this->buildAuthQuery($callbackUrl, $state, $options));

		return $this->getAuthEndpoint().'?'.http_build_query($query);
	}

	/**
	 * Create the query string for the provider's auth end-point.
	 *
	 * @param  string  $callbackUrl
	 * @param  string  $state
	 * @return array
	 */
	protected function buildDefaultAuthQuery($callbackUrl, $state)
	{
		$query = array();

		$elements = array('client_id', 'redirect_uri', 'state', 'response_type');

		// We'll simply spin through the various query string elements and call the retrieval
		// function for each one, which is responsible for returning the value and may be
		// overriden by the stragegy for more complete request parameter customization.
		foreach ($elements as $element)
		{
			$method = $this->snakeToCamel($element);

			$query[$element] = $this->{"get{$method}"}($callbackUrl, $state);
		}

		$query['scope'] = $this->getFormattedScope();

		return $query;
	}

	/**
	 * Create the array query string for the provider's auth end-point.
	 *
	 * This may be overriden by strategies to customize options.
	 *
	 * @param  string  $callbackUrl
	 * @param  string  $state
	 * @param  array   $options
	 * @return array
	 */
	protected function buildAuthQuery($callbackUrl, $state, $options)
	{
		return $options;
	}

	/**
	 * Get the default client ID.
	 *
	 * @return string
	 */
	protected function getClientId()
	{
		return $this->clientId;
	}

	/**
	 * Get the default client secret.
	 *
	 * @return string
	 */
	protected function getClientSecret()
	{
		return $this->secret;
	}

	/**
	 * Get the default redirect URI.
	 *
	 * @param  string  $callbackUrl
	 * @return string
	 */
	protected function getRedirectUri($callbackUrl)
	{
		return $callbackUrl;
	}

	/**
	 * Get the default state.
	 *
	 * @param  string  $callbackUrl
	 * @param  string  $state
	 * @return string
	 */
	protected function getState($callbackUrl, $state)
	{
		return $state;
	}

	/**
	 * Get the default response type.
	 *
	 * @return string
	 */
	protected function getResponseType()
	{
		return 'code';
	}

	/**
	 * Create the URL for the provider's access token end-point.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request
	 * @param  array  $options
	 * @return string
	 */
	protected function buildAccessUrl(Request $request, $options)
	{
		$defaults = $this->buildDefaultAccessQuery($request, $options);

		$query = array_merge($defaults, $this->buildAccessQuery($request, $options));

		return $this->getAccessEndpoint().'?'.http_build_query($query);
	}

	/**
	 * Build the default query array for retrieving tokens.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request
	 * @param  array  $options
	 * @return array
	 */
	protected function buildDefaultAccessQuery(Request $request, $options)
	{
		$query['client_id'] = $this->getClientId();

		$grantType = $options['grant_type'];

		// When requesting the access token, we will also attach the client secret key to
		// the request as an identifier for the consuming application. This key is not
		// needed when simply requesting the user code before getting acess tokens.
		$query['client_secret'] = $this->getClientSecret();

		$query['grant_type'] = $grantType;

		return array_merge($query, $this->getGrantTypeOptions($request, $grantType));
	}

	/**
	 * Build the query array for retrieving tokens.
	 *
	 * This may be overriden by strategies to customize options.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request
	 * @param  array  $options
	 * @return array
	 */
	protected function buildAccessQuery(Request $request, $options)
	{
		return $options;
	}

	/**
	 * Get the current URL from a request object.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request
	 * @return string
	 */
	protected function getCurrentUrl(Request $request)
	{
		return $request->getScheme().'://'.$request->getHttpHost().$request->getPathInfo();
	}

	/**
	 * Format the provider scope into a valid string.
	 *
	 * @return string
	 */
	protected function getFormattedScope()
	{
		if (is_array($this->scope))
		{
			return implode($this->scopeDelimiter, $this->scope);
		}

		return $this->scope;
	}

	/**
	 * Get the provider scope.
	 *
	 * @return mixed
	 */
	public function getScope()
	{
		return $this->scope;
	}

	/**
	 * Set the provider scope.
	 *
	 * @param  mixed  $scope
	 * @return void
	 */
	public function setScope($scope)
	{
		$this->scope = $scope;
	}

	/**
	 * Get the provider scope delimiter.
	 *
	 * @return mixed
	 */
	public function getScopeDelimiter()
	{
		return $this->scopeDelimiter;
	}

	/**
	 * Set the provider scope delimiter.
	 *
	 * @param  mixed  $scope
	 * @return void
	 */
	public function setScopeDelimiter($scope)
	{
		$this->scopeDelimiter = $scopeDelimiter;
	}

	/**
	 * Convert a snake case string to camel case.
	 *
	 * @param  string  $string
	 * @return string
	 */
	protected function snakeToCamel($string)
	{
		return str_replace(' ', '', ucwords(str_replace('_', ' ', $val)));
	}

}