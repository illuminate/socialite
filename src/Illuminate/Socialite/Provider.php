<?php namespace Illuminate\Socialite;

use Guzzle\Http\ClientInterface;
use Guzzle\Http\Message\Response;
use Symfony\Component\HttpFoundation\Request;

abstract class Provider {

	/**
	 * The state store implementation.
	 *
	 * @var Illuminate\Socialite\StateStoreInterface
	 */
	protected $state;

	/**
	 * The HTTP client to be used.
	 *
	 * @var Guzzle\Http\ClientInterface
	 */
	protected $client;

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
	 * The scope(s) requested by the provider.
	 *
	 * @var string|array
	 */
	protected $scope;

	/**
	 * The scope delimiter.
	 *
	 * @var string
	 */
	protected $scopeDelimiter = ',';

	/**
	 * Create a new provider instance.
	 *
	 * @param  Illuminate\Socialite\StateStoreInterface  $state
	 * @param  string  $clientId
	 * @param  string  $secret
	 * @return void
	 */
	public function __construct(StateStoreInterface $state, $clientId, $secret)
	{
		$this->state = $state;
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
	 * Get the user data end-point URL for the provider.
	 *
	 * @return string
	 */
	abstract protected function getUserDataEndpoint();

	/**
	 * Get an array of query string options for a grant type.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request
	 * @param  string $grantType
	 * @param  array  $options
	 * @return array
	 */
	abstract protected function getGrantTypeOptions(Request $request, $grantType, $options);

	/**
	 * Get the user information using a token.
	 *
	 * @param  Illuminate\Socialite\AccessToken  $token
	 * @return UserData
	 */
	public function getUserData(AccessToken $token)
	{
		$query = http_build_query(array('access_token' => $token->getValue()));

		$response = $this->getHttpClient()->get($this->getUserDataEndpoint().'?'.$query)->send();

		return new UserData($this->parseJsonResponse($response));
	}

	/**
	 * Get the URL to the provider's auth end-point.
	 *
	 * @param  string  $callbackUrl
	 * @param  array   $options
	 * @return string
	 */
	public function getAuthUrl($callbackUrl, array $options = array())
	{
		$state = md5(uniqid('', true).microtime(true));

		// First we'll store the state in storage. This allows us to verify the state when we
		// go to request the access token on the callback. This serves as protection from
		// cross-site requset forgery style attacks and is highly recommended by spec.
		$this->state->setState($state);

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
	 * This may be overriden by providers to customize options.
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
	 * Get the user's access token.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  array  $options
	 * @return Illuminate\Socialite\AccessToken
	 */
	public function getAccessToken(Request $request, array $options = array())
	{
		// First we want to verify that the state given in the request and the state
		// we have in storage match. If they do not, there could be a malicious
		// false requset taking place and we need to bail out with an error.
		if ($this->stateMismatch($request))
		{
			throw new StateMismatchException;
		}

		$client = $this->getHttpClient();

		// If the "grant_type" option is not set, we will default it to the value
		// needed to get the access token, which is the most likely candidate
		// for the value and makes for a good default value for us to set.
		if ( ! isset($options['grant_type']))
		{
			$options['grant_type'] = 'authorization_code';
		}

		$options = $this->getAccessOptions($request, $options);

		// Once we have all of our options we can execute a request to the server
		// to obtain the access token, which can be stored and used to access
		// the provider's user APIs for basic information about the user.
		$response = $this->executeAccessRequest($client, $options);

		$parameters = $this->parseAccessResponse($response);

		return $this->createAccessToken($parameters);
	}

	/**
	 * Determine if there is a state mismatch.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @return bool
	 */
	protected function stateMismatch(Request $request)
	{
		return $request->get('state') !== $this->state->getState();
	}

	/**
	 * Create the query array for the provider's access token end-point.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request
	 * @param  array  $options
	 * @return array
	 */
	protected function getAccessOptions(Request $request, $options)
	{
		$defaults = $this->buildDefaultAccessOptions($request, $options);

		return array_merge($defaults, $this->buildAccessOptions($request, $options));
	}

	/**
	 * Build the default query array for retrieving tokens.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request
	 * @param  array  $options
	 * @return array
	 */
	protected function buildDefaultAccessOptions(Request $request, $options)
	{
		// When requesting the access token, we will also attach the client secret key to
		// the request as an identifier for the consuming application. This key is not
		// needed when simply requesting the user code before getting acess tokens.
		$query['client_id'] = $this->getClientId();

		$query['client_secret'] = $this->getClientSecret();

		$query['redirect_uri'] = $this->getCurrentUrl($request);

		// We'll also add the code that retrieved from the first request we made to the
		// end-point. This code will be used to obtain the access token, which can
		// then be stored and used to get data from the provider's user APIs.
		$query['code'] = $request->get('code');

		$grant = $options['grant_type'];

		$grantOptions = $this->getGrantTypeOptions($request, $grant, $options);

		return array_merge($query, $grantOptions);
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
	protected function buildAccessOptions(Request $request, $options)
	{
		return $options;
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
	 * Get an array of parameters from the access token response.
	 *
	 * @param  Guzzle\Http\Message\Response  $response
	 * @return array
	 */
	protected function parseAccessResponse(Response $response)
	{
		$parameters = array();

		parse_str((string) $response->getBody(), $parameters);

		return $parameters;
	}

	/**
	 * Create an access token with the given parameters.
	 *
	 * @param  array  $parameters
	 * @return AccessToken
	 */
	protected function createAccessToken(array $parameters)
	{
		return new AccessToken($parameters);
	}

	/**
	 * Get the array representation of a JSON response.
	 *
	 * @param  Guzzle\Http\Message\Response
	 * @return array
	 */
	protected function parseJsonResponse(Response $response)
	{
		return (array) json_decode((string) $response->getBody());
	}

	/**
	 * Convert a snake case string to camel case.
	 *
	 * @param  string  $string
	 * @return string
	 */
	protected function snakeToCamel($string)
	{
		return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
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
	 * Get the HTTP client to be used.
	 *
	 * @return Guzzle\Http\ClientInterface
	 */
	public function getHttpClient()
	{
		return $this->client ?: new \Guzzle\Http\Client;		
	}

	/**
	 * Set the HTTP client to be used.
	 *
	 * @param  Guzzle\Http\ClientInterface  $client
	 * @return void
	 */
	public function setHttpClient(ClientInterface $client)
	{
		$this->client = $client;
	}

	/**
	 * Format the provider scope into a valid string.
	 *
	 * @return string
	 */
	protected function getFormattedScope()
	{
		$scope = $this->getScope();

		if (is_array($scope))
		{
			return implode($this->scopeDelimiter, $scope);
		}

		return $scope;
	}

	/**
	 * Get the provider scope.
	 *
	 * @return mixed
	 */
	public function getScope()
	{
		return $this->scope ?: $this->getDefaultScope();
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

	/**
	 * Set the provider scope.
	 *
	 * @param  mixed  $scope
	 * @return void
	 */
	public function setScope($scope)
	{
		$this->scope = (array) $scope;
	}

	/**
	 * Add a scope to the provider's list of scopes.
	 *
	 * @param  string  $scope
	 * @return Illuminate\Socialite\Provider
	 */
	public function addScope($scope)
	{
		$this->scope[] = $scope;

		return $this;
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

}