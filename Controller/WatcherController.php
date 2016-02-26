<?php
App::uses('AppController', 'Controller');

class WatcherController extends AppController {

	/**
	 * Watch for inactivity sessions
	 *
	 * Return the following vars:
	 * boolean 'logged' if user has session authenticated
	 * integer 'remainingTime' showing the time remaining before session expire, in minutes.
	 *
	 * @return json
	 */
	public function watcher() {
		$user = $this->Session->read('Auth.User');
		$data['logged'] = !empty($user);
		$data['remainingTime'] = round(($this->Session->read('Config.time') - time()) / 60);
		$this->autoRender = false;
		$this->response->type('json');
		$this->response->body(json_encode($data));
	}
}
