<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_Sharing\Controllers;

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Http\Client\IClientService;
use OCP\AppFramework\Http\DataResponse;

/**
 * Class ExternalSharesController
 *
 * @package OCA\Files_Sharing\Controllers
 */
class ExternalSharesController extends Controller {

	/** @var \OCA\Files_Sharing\External\Manager */
	private $externalManager;
	/** @var IClientService */
	private $clientService;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param bool $incomingShareEnabled
	 * @param \OCA\Files_Sharing\External\Manager $externalManager
	 * @param IClientService $clientService
	 */
	public function __construct($appName,
								IRequest $request,
								\OCA\Files_Sharing\External\Manager $externalManager,
								IClientService $clientService) {
		parent::__construct($appName, $request);
		$this->externalManager = $externalManager;
		$this->clientService = $clientService;
	}

	/**
	 * @NoAdminRequired
	 * @NoOutgoingFederatedSharingRequired
	 *
	 * @return JSONResponse
	 */
	public function index() {
		return new JSONResponse($this->externalManager->getOpenShares());
	}

	/**
	 * @NoAdminRequired
	 * @NoOutgoingFederatedSharingRequired
	 *
	 * @param int $id
	 * @return JSONResponse
	 */
	public function create($id) {
		$this->externalManager->acceptShare($id);
		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 * @NoOutgoingFederatedSharingRequired
	 *
	 * @param $id
	 * @return JSONResponse
	 */
	public function destroy($id) {
		$this->externalManager->declineShare($id);
		return new JSONResponse();
	}

	/**
	 * Test whether the specified remote is accessible
	 *
	 * @param string $remote
	 * @return bool
	 */
	protected function testUrl($remote) {
		try {
			$client = $this->clientService->newClient();
			$response = json_decode($client->get(
				$remote,
				[
					'timeout' => 3,
					'connect_timeout' => 3,
				]
			)->getBody());

			return !empty($response->version) && version_compare($response->version, '7.0.0', '>=');
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * @PublicPage
	 * @NoOutgoingFederatedSharingRequired
	 * @NoIncomingFederatedSharingRequired
	 *
	 * @param string $remote
	 * @return DataResponse
	 */
	public function testRemote($remote) {
		if ($this->testUrl('https://' . $remote . '/status.php')) {
			return new DataResponse('https');
		} elseif ($this->testUrl('http://' . $remote . '/status.php')) {
			return new DataResponse('http');
		} else {
			return new DataResponse(false);
		}
	}

}