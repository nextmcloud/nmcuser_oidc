<?php

namespace OCA\NextMagentaCloud\AppInfo;

use OCP\AppFramework\App;

class Application extends App {

	const APP_NAME = 'nmcuser_oidc';

	public function __construct (array $urlParams = []) {
		parent::__construct(self::APP_NAME, $urlParams);
	}

}
