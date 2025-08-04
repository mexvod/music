<?php declare(strict_types=1);

/**
 * ownCloud - Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Pauli Järvinen <pauli.jarvinen@gmail.com>
 * @copyright Morris Jobke 2013, 2014
 * @copyright Pauli Järvinen 2020 - 2025
 */

namespace OCA\Music\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

use OCA\Music\AppFramework\Core\Logger;

class LogController extends Controller {
	private Logger $logger;

	public function __construct(string $appName,
								IRequest $request,
								Logger $logger) {
		parent::__construct($appName, $request);
		$this->logger = $logger;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function log(?string $message) : JSONResponse {
		$this->logger->debug('JS: ' . $message);
		return new JSONResponse(['success' => true]);
	}
}
