<?php

use Mockery as m;
use Illuminate\Socialite\OAuthTwo\OAuthTwoProvider;

class OAuthTwoProviderTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testGettingAuthUrlSetsStateInStorage()
	{
		$provider = $this->getMockProvider();
		$provider->getStateStore()->shouldReceive('setState')->once();
		$provider->getAuthUrl('foo');
	}


	protected function getMockProvider($methods = array(), $constructor = array())
	{
		$methods = array_merge($methods, array('getAuthEndpoint', 'getAccessEndpoint', 'getUserDataEndpoint', 'getGrantTypeOptions'));
		if (count($constructor) == 0)
		{
			$stateStore = m::mock('Illuminate\Socialite\OAuthTwo\StateStoreInterface');
			$constructor = array($stateStore, 'client', 'secret');
		}
		return $this->getMock('Illuminate\Socialite\OAuthTwo\OAuthTwoProvider', $methods, $constructor);
	}

}