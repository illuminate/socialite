<?php namespace Illuminate\Socialite;

interface AccessTokenInterface {

	/**
	 * Get the value of the access token.
	 *
	 * @return string
	 */
	public function getValue();

}