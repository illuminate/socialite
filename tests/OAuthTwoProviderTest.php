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


	protected function getMockProvider($methods = array(), $constructor = array())
	{
		$methods = array_merge($methods, array('getAuthEndpoint', 'getAccessEndpoint', 'getUserDataEndpoint', 'getGrantTypeOptions'));
		if (count($constructor) == 0)
		{
			$stateStore = $this->getMock('Illuminate\Socialite\OAuthTwo\StateStoreInterface');
			$constructor = array($stateStore, 'client', 'secret');
		}
		return $this->getMock('Illuminate\Socialite\OAuthTwo\OAuthTwoProvider', $methods, $constructor);
	}

}