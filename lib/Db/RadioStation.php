<?php declare(strict_types=1);

/**
 * ownCloud - Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Pauli Järvinen <pauli.jarvinen@gmail.com>
 * @copyright Pauli Järvinen 2020 - 2025
 */

namespace OCA\Music\Db;

/**
 * @method ?string getName()
 * @method void setName(?string $name)
 * @method string getStreamUrl()
 * @method void setStreamUrl(string $url)
 * @method ?string getHomeUrl()
 * @method void setHomeUrl(?string $url)
 */
class RadioStation extends Entity {
	public ?string $name = null;
	public string $streamUrl = '';
	public ?string $homeUrl = null;

	public function toApi() : array {
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
			'stream_url' => $this->getStreamUrl(),
			'home_url' => $this->getHomeUrl(),
			'created' => $this->getCreated(),
			'updated' => $this->getUpdated()
		];
	}

	public function toAmpacheApi(callable $createImageUrl) : array {
		return [
			'id' => $this->getId(),
			'name' => $this->getName() ?? $this->getStreamUrl(),
			'url' => $this->getStreamUrl(),
			'site_url' => $this->getHomeUrl(),
			'art' => $createImageUrl($this),
			'has_art' => false, // art is always a placeholder on radio stations
		];
	}
}
