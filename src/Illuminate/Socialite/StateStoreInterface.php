<?php namespace Illuminate\Socialite;

interface StateStoreInterface {

	/**
	 * Get the state from storage.
	 *
	 * @return string
	 */
	public function getState();

	/**
	 * Set the state in storage.
	 *
	 * @param  string  $state
	 * @return void
	 */
	public function setState($state);

}