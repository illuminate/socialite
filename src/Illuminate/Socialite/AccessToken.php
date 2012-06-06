<?php namespace Illuminate\Socialite;

use Symfony\Component\HttpFoundation\ParameterBag;

class AccessToken extends ParameterBag {

	/**
	 * Get the value of the access token.
	 *
	 * @param  string  $default
	 * @return string
	 */
	public function getValue($default = null)
	{
		return $this->get('access_token', $default);
	}

}