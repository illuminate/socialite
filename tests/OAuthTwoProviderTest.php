<?php

use Mockery as m;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Socialite\OAuthTwo\OAuthTwoProvider;

class OAuthTwoProviderTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testGettingAuthUrlSetsStateInStorage()
	{
		$provider = $this->getMockProvider();
		$provider->getStateStore()->expects($this->once())->method('setState');
		$provider->getAuthUrl('foo');
	}


	public function testAuthUrlQueryStringConstruction()
	{
		$provider = $this->getMockProvider();
		$provider->expects($this->once())->method('getAuthEndpoint')->will($this->returnValue('http://bar.com'));
		$url = $provider->getAuthUrl('http://callback.com', array('boom' => 'zoom'));
		list($base, $query) = explode('?', $url);
		$this->assertEquals('http://bar.com', $base);
		$parameters = array();
		parse_str($query, $parameters);
		$this->assertEquals('zoom', $parameters['boom']);
		$this->assertEquals('client', $parameters['client_id']);
		$this->assertEquals('http://callback.com', $parameters['redirect_uri']);
		$this->assertTrue(is_string($parameters['state']));
	}


	/**
	 * @expectedException Illuminate\Socialite\OAuthTwo\StateMismatchException
	 */
	public function testStateMismatchThrowsException()
	{
		$provider = $this->getMockProvider();
		$provider->getStateStore()->expects($this->once())->method('getState')->will($this->returnValue('foo'));
		$request = Request::create('/', 'GET', array('state' => 'bar'));
		$provider->getAccessToken($request);
	}


	public function testAccessRequestCalledWithProperOptions()
	{
		$provider = $this->getMockProvider(array('getCurrentUrl'));
		$provider->getStateStore()->expects($this->once())->method('getState')->will($this->returnValue('bar'));
		$provider->expects($this->once())->method('getAccessEndpoint')->will($this->returnValue('http://access.com'));
		$provider->expects($this->once())->method('getCurrentUrl')->will($this->returnValue('http://current.com'));
		$request = Request::create('/', 'GET', array('state' => 'bar', 'code' => 'blah'));
		$url = 'http://access.com?client_id=client&client_secret=secret&redirect_uri='.urlencode('http://current.com').'&code=blah&grant_type=authorization_code';
		$client = $this->getMock('Guzzle\Http\ClientInterface');
		$requestMock = $this->getMock('Guzzle\Http\Message\RequestInterface');
		$client->expects($this->once())->method('get')->with($this->equalTo($url))->will($this->returnValue($requestMock));
		$response = new Guzzle\Http\Message\Response(200, null, 'access_token=token&expires=100');
		$requestMock->expects($this->once())->method('send')->will($this->returnValue($response));
		$provider->setHttpClient($client);
		$provider->getAccessToken($request);
	}


	protected function getMockProvider($methods = array(), $constructor = array())
	{
		$methods = array_merge($methods, array('getAuthEndpoint', 'getAccessEndpoint', 'getUserDataEndpoint'));
		if (count($constructor) == 0)
		{
			$stateStore = $this->getMock('Illuminate\Socialite\OAuthTwo\StateStoreInterface');
			$constructor = array($stateStore, 'client', 'secret');
		}
		return $this->getMock('Illuminate\Socialite\OAuthTwo\OAuthTwoProvider', $methods, $constructor);
	}

}