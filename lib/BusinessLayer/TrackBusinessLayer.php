<?php declare(strict_types=1);

/**
 * ownCloud - Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Pauli Järvinen <pauli.jarvinen@gmail.com>
 * @copyright Morris Jobke 2013
 * @copyright Pauli Järvinen 2016 - 2025
 */

namespace OCA\Music\BusinessLayer;

use OCA\Music\AppFramework\BusinessLayer\BusinessLayer;
use OCA\Music\AppFramework\BusinessLayer\BusinessLayerException;
use OCA\Music\AppFramework\Core\Logger;

use OCA\Music\Db\MatchMode;
use OCA\Music\Db\SortBy;
use OCA\Music\Db\TrackMapper;
use OCA\Music\Db\Track;
use OCA\Music\Utility\ArrayUtil;
use OCA\Music\Utility\StringUtil;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Files\FileInfo;
use OCP\Files\Folder;

/**
 * Base class functions with the actually used inherited types to help IDE and Scrutinizer:
 * @method Track find(int $trackId, string $userId)
 * @method Track[] findAll(string $userId, int $sortBy=SortBy::Name, ?int $limit=null, ?int $offset=null)
 * @method Track[] findAllByName(string $name, string $userId, int $matchMode=MatchMode::Exact, ?int $limit=null, ?int $offset=null)
 * @property TrackMapper $mapper
 * @phpstan-extends BusinessLayer<Track>
 */
class TrackBusinessLayer extends BusinessLayer {
	private Logger $logger;

	public function __construct(TrackMapper $trackMapper, Logger $logger) {
		parent::__construct($trackMapper);
		$this->logger = $logger;
	}

	/**
	 * Returns all tracks filtered by artist (both album and track artists are considered)
	 * @param int|int[] $artistId
	 * @return Track[]
	 */
	public function findAllByArtist(/*mixed*/ $artistId, string $userId, ?int $limit=null, ?int $offset=null) : array {
		if (empty($artistId)) {
			return [];
		} else {
			if (!\is_array($artistId)) {
				$artistId = [$artistId];
			}
			return $this->mapper->findAllByArtist($artistId, $userId, $limit, $offset);
		}
	}

	/**
	 * Returns all tracks filtered by album. Optionally, filter also by the performing artist.
	 * @param int|int[] $albumId
	 * @return Track[]
	 */
	public function findAllByAlbum(/*mixed*/ $albumId, string $userId, ?int $artistId=null, ?int $limit=null, ?int $offset=null) : array {
		if (empty($albumId)) {
			return [];
		} else {
			if (!\is_array($albumId)) {
				$albumId = [$albumId];
			}
			return $this->mapper->findAllByAlbum($albumId, $userId, $artistId, $limit, $offset);
		}
	}

	/**
	 * Returns all tracks filtered by parent folder
	 * @return Track[]
	 */
	public function findAllByFolder(int $folderId, string $userId, ?int $limit=null, ?int $offset=null) : array {
		return $this->mapper->findAllByFolder($folderId, $userId, $limit, $offset);
	}

	/**
	 * Returns all tracks filtered by genre
	 * @return Track[]
	 */
	public function findAllByGenre(int $genreId, string $userId, ?int $limit=null, ?int $offset=null) : array {
		return $this->mapper->findAllByGenre($genreId, $userId, $limit, $offset);
	}

	/**
	 * Returns all tracks filtered by name (of track/album/artist)
	 * @param string $name the name of the track/album/artist
	 * @param string $userId the name of the user
	 * @return Track[]
	 */
	public function findAllByNameRecursive(string $name, string $userId, ?int $limit=null, ?int $offset=null) : array {
		$name = \trim($name);
		return $this->mapper->findAllByNameRecursive($name, $userId, $limit, $offset);
	}

	/**
	 * Returns all tracks specified by name, artist name, and/or album name
	 * @return Track[] Tracks matching the criteria
	 */
	public function findAllByNameArtistOrAlbum(?string $name, ?string $artistName, ?string $albumName, string $userId) : array {
		if ($name !== null) {
			$name = \trim($name);
		}
		if ($artistName !== null) {
			$artistName = \trim($artistName);
		}

		return $this->mapper->findAllByNameArtistOrAlbum($name, $artistName, $albumName, $userId);
	}

	/**
	 * Find most frequently played tracks
	 * @return Track[]
	 */
	public function findFrequentPlay(string $userId, ?int $limit=null, ?int $offset=null) : array {
		return $this->mapper->findFrequentPlay($userId, $limit, $offset);
	}

	/**
	 * Find most recently played tracks
	 * @return Track[]
	 */
	public function findRecentPlay(string $userId, ?int $limit=null, ?int $offset=null) : array {
		return $this->mapper->findRecentPlay($userId, $limit, $offset);
	}

	/**
	 * Find least recently played tracks
	 * @return Track[]
	 */
	public function findNotRecentPlay(string $userId, ?int $limit=null, ?int $offset=null) : array {
		return $this->mapper->findNotRecentPlay($userId, $limit, $offset);
	}

	/**
	 * Returns the track for a file id
	 * @return Track|null
	 */
	public function findByFileId(int $fileId, string $userId) : ?Track {
		try {
			return $this->mapper->findByFileId($fileId, $userId);
		} catch (DoesNotExistException $e) {
			return null;
		}
	}

	/**
	 * Returns file IDs of all indexed tracks of the user
	 * @return int[]
	 */
	public function findAllFileIds(string $userId) : array {
		return $this->mapper->findAllFileIds($userId);
	}

	/**
	 * Returns file IDs of all indexed tracks of the user which should be rescanned to ensure that the library details are up-to-date.
	 * The track may be considered "dirty" for one of two reasons:
	 * - its 'modified' time in the file system (actually in the cloud's file cache) is later than the 'updated' field of the entity in the database
	 * - it has been specifically marked as dirty, maybe in response to being moved to another directory
	 * @return int[]
	 */
	public function findDirtyFileIds(string $userId) : array {
		return $this->mapper->findDirtyFileIds($userId);
	}

	/**
	 * Returns all folders of the user containing indexed tracks, along with the contained track IDs
	 * @return array of entries like {id: int, name: string, parent: ?int, trackIds: int[]}
	 */
	public function findAllFolders(string $userId, Folder $musicFolder) : array {
		// All tracks of the user, grouped by their parent folders. Some of the parent folders
		// may be owned by other users and are invisible to this user (in case of shared files).
		$trackIdsByFolder = $this->mapper->findTrackAndFolderIds($userId);
		$foldersLut = $this->getFoldersLut($trackIdsByFolder, $userId, $musicFolder);
		return \array_map(
			fn($id, $folderInfo) => \array_merge($folderInfo, ['id' => $id]),
			\array_keys($foldersLut), $foldersLut
		);
	}

	/**
	 * @param Track[] $tracks (in|out)
	 */
	public function injectFolderPathsToTracks(array $tracks, string $userId, Folder $musicFolder) : void {
		$folderIds = \array_map(fn($t) => $t->getFolderId(), $tracks);
		$folderIds = \array_unique($folderIds);
		$trackIdsByFolder = \array_fill_keys($folderIds, []); // track IDs are not actually used here so we can use empty arrays

		$foldersLut = $this->getFoldersLut($trackIdsByFolder, $userId, $musicFolder);

		// recursive helper to get folder's path and cache all parent paths on the way
		$getFolderPath = function(int $id, array &$foldersLut) use (&$getFolderPath) : string {
			// setup the path if not cached already
			if (!isset($foldersLut[$id]['path'])) {
				$parentId = $foldersLut[$id]['parent'];
				if ($parentId === null) {
					$foldersLut[$id]['path'] = '';
				} else {
					$foldersLut[$id]['path'] = $getFolderPath($parentId, $foldersLut) . '/' . $foldersLut[$id]['name'];
				}
			}
			return $foldersLut[$id]['path'];
		};

		foreach ($tracks as $track) {
			$track->setFolderPath($getFolderPath($track->getFolderId(), $foldersLut));
		}
	}

	/**
	 * Get folder info lookup table, for the given tracks. The table will contain all the predecessor folders
	 * between those tracks and the root music folder (inclusive).
	 * 
	 * @param array $trackIdsByFolder Keys are folder IDs and values are arrays of track IDs
	 * @return array Keys are folder IDs and values are arrays like ['name' : string, 'parent' : int, 'trackIds' : int[]]
	 */
	private function getFoldersLut(array $trackIdsByFolder, string $userId, Folder $musicFolder) : array {
		// Get the folder names and direct parent folder IDs directly from the DB.
		// This is significantly more efficient than using the Files API because we need to
		// run only single DB query instead of one per folder.
		$folderNamesAndParents = $this->mapper->findNodeNamesAndParents(\array_keys($trackIdsByFolder));

		// Compile the look-up-table entries from our two intermediary arrays
		$lut = [];
		foreach ($trackIdsByFolder as $folderId => $trackIds) {
			// $folderId is not found from $folderNamesAndParents if it's a dummy ID created as placeholder on a malformed playlist
			$nameAndParent = $folderNamesAndParents[$folderId] ?? ['name' => '', 'parent' => null];
			$lut[$folderId] = \array_merge($nameAndParent, ['trackIds' => $trackIds]);
		}

		// the root folder should have null parent; here we also ensure it's included
		$rootFolderId = $musicFolder->getId();
		$rootTracks = $lut[$rootFolderId]['trackIds'] ?? [];
		$lut[$rootFolderId] = ['name' => '', 'parent' => null, 'trackIds' => $rootTracks];

		// External mounts and shared files/folders need some special handling. But if there are any, they should be found
		// right under the top-level folder.
		$this->addExternalMountsToFoldersLut($lut, $userId, $musicFolder);

		// Add the intermediate folders which do not directly contain any tracks
		$this->addMissingParentsToFoldersLut($lut);

		return $lut;
	}

	/**
	 * Add externally mounted folders and shared files and folders to the folder LUT if there are any under the $musicFolder
	 * 
	 * @param array $lut (in|out) Keys are folder IDs and values are arrays like ['name' : string, 'parent' : int, 'trackIds' : int[]]
	 */
	private function addExternalMountsToFoldersLut(array &$lut, string $userId, Folder $musicFolder) : void {
		$nodesUnderRoot = $musicFolder->getDirectoryListing();
		$homeStorageId = $musicFolder->getStorage()->getId();
		$rootFolderId = $musicFolder->getId();

		foreach ($nodesUnderRoot as $node) {
			if ($node->getStorage()->getId() != $homeStorageId) {
				// shared file/folder or external mount
				if ($node->getType() == FileInfo::TYPE_FOLDER) {
					// The mount point folders are always included in the result. At this time, we don't know if
					// they actually contain any tracks, unless they have direct track children. If there are direct tracks,
					// then the parent ID is incorrectly set and needs to be overridden.
					$trackIds = $lut[$node->getId()]['trackIds'] ?? [];
					$lut[$node->getId()] = ['name' => $node->getName(), 'parent' => $rootFolderId, 'trackIds' => $trackIds];

				} else if ($node->getMimePart() == 'audio') {
					// shared audio file, check if it's actually a scanned file in our library
					$sharedTrack = $this->findByFileId($node->getId(), $userId);
					if ($sharedTrack !== null) {

						$trackId = $sharedTrack->getId();
						foreach ($lut as $folderId => &$entry) {
							$trackIdIdx = \array_search($trackId, $entry['trackIds']);
							if ($trackIdIdx !== false) {
								// move the track from it's actual parent (in other user's storage) to our root
								unset($entry['trackIds'][$trackIdIdx]);
								$lut[$rootFolderId]['trackIds'][] = $trackId;

								// remove the former parent folder if it has no more tracks and it's not one of the mount point folders
								if (\count($entry['trackIds']) == 0 && empty(\array_filter($nodesUnderRoot, fn($n) => $n->getId() == $folderId))) {
									unset($lut[$folderId]);
								}
								break;
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Add any missing intermediary folder to the LUT. For this function to work correctly, the pre-condition is that the LUT contains
	 * a root node which is predecessor of all other contained nodes and has 'parent' set as null.
	 * 
	 * @param array $lut (in|out) Keys are folder IDs and values are arrays like ['name' : string, 'parent' : int, 'trackIds' : int[]]
	 */
	private function addMissingParentsToFoldersLut(array &$lut) : void {
		$foldersToProcess = $lut;

		while (\count($foldersToProcess)) {
			$parentIds = \array_unique(\array_column($foldersToProcess, 'parent'));
			// do not process root even if it's included in $foldersToProcess
			$parentIds = \array_filter($parentIds, fn($i) => $i !== null);
			$parentIds = ArrayUtil::diff($parentIds, \array_keys($lut));
			$parentFolders = $this->mapper->findNodeNamesAndParents($parentIds);

			$foldersToProcess = [];
			foreach ($parentFolders as $folderId => $nameAndParent) {
				$foldersToProcess[] = $lut[$folderId] = \array_merge($nameAndParent, ['trackIds' => []]);
			}
		}
	}

	/**
	 * Returns all genre IDs associated with the given artist
	 * @return int[]
	 */
	public function getGenresByArtistId(int $artistId, string $userId) : array {
		return $this->mapper->getGenresByArtistId($artistId, $userId);
	}

	/**
	 * Returns file IDs of the tracks which do not have genre scanned. This is not the same
	 * thing as unknown genre, which is stored as empty string and means that the genre has
	 * been scanned but was not found from the track metadata.
	 * @return int[]
	 */
	public function findFilesWithoutScannedGenre(string $userId) : array {
		return $this->mapper->findFilesWithoutScannedGenre($userId);
	}

	public function countByArtist(int $artistId) : int {
		return $this->mapper->countByArtist($artistId);
	}

	public function countByAlbum(int $albumId) : int {
		return $this->mapper->countByAlbum($albumId);
	}

	/**
	 * @return integer Duration in seconds
	 */
	public function totalDurationOfAlbum(int $albumId) : int {
		return $this->mapper->totalDurationOfAlbum($albumId);
	}

	/**
	 * @return integer Duration in seconds
	 */
	public function totalDurationByArtist(int $artistId) : int {
		return $this->mapper->totalDurationByArtist($artistId);
	}

	/**
	 * Update "last played" timestamp and increment the total play count of the track.
	 */
	public function recordTrackPlayed(int $trackId, string $userId, ?\DateTime $timeOfPlay = null) : void {
		$timeOfPlay = $timeOfPlay ?? new \DateTime();

		if (!$this->mapper->recordTrackPlayed($trackId, $userId, $timeOfPlay)) {
			throw new BusinessLayerException("Track with ID $trackId was not found");
		}
	}

	/**
	 * Adds a track if it does not exist already or updates an existing track
	 * @param string $title the title of the track
	 * @param int|null $number the number of the track
	 * @param int|null $discNumber the number of the disc
	 * @param int|null $year the year of the release
	 * @param int $genreId the genre id of the track
	 * @param int $artistId the artist id of the track
	 * @param int $albumId the album id of the track
	 * @param int $fileId the file id of the track
	 * @param string $mimetype the mimetype of the track
	 * @param string $userId the name of the user
	 * @param int $length track length in seconds
	 * @param int $bitrate track bitrate in bits (not kbits)
	 * @return Track The added/updated track
	 */
	public function addOrUpdateTrack(
			string $title, ?int $number, ?int $discNumber, ?int $year, int $genreId, int $artistId, int $albumId,
			int $fileId, string $mimetype, string $userId, ?int $length=null, ?int $bitrate=null) : Track {
		$track = new Track();
		$track->setTitle(StringUtil::truncate($title, 256)); // some DB setups can't truncate automatically to column max size
		$track->setNumber($number);
		$track->setDisk($discNumber);
		$track->setYear($year);
		$track->setGenreId($genreId);
		$track->setArtistId($artistId);
		$track->setAlbumId($albumId);
		$track->setFileId($fileId);
		$track->setMimetype($mimetype);
		$track->setUserId($userId);
		$track->setLength($length);
		$track->setBitrate($bitrate);
		$track->setDirty(0);
		return $this->mapper->insertOrUpdate($track);
	}

	/**
	 * Deletes tracks
	 * @param int[] $fileIds file IDs of the tracks to delete
	 * @param string[]|null $userIds the target users; if omitted, the tracks matching the
	 *                      $fileIds are deleted from all users
	 * @return array|false  False is returned if no such track was found; otherwise array of six arrays
	 *         (named 'deletedTracks', 'remainingAlbums', 'remainingArtists', 'obsoleteAlbums',
	 *         'obsoleteArtists', and 'affectedUsers'). These contain the track, album, artist, and
	 *         user IDs of the deleted tracks. The 'obsolete' entities are such which no longer
	 *         have any tracks while 'remaining' entities have some left.
	 */
	public function deleteTracks(array $fileIds, ?array $userIds=null) {
		$tracks = ($userIds !== null)
			? $this->mapper->findByFileIds($fileIds, $userIds)
			: $this->mapper->findAllByFileIds($fileIds);

		if (\count($tracks) === 0) {
			$result = false;
		} else {
			// delete all the matching tracks
			$trackIds = ArrayUtil::extractIds($tracks);
			$this->deleteById($trackIds);

			// find all distinct albums, artists, and users of the deleted tracks
			$artists = [];
			$albums = [];
			$users = [];
			foreach ($tracks as $track) {
				$artists[$track->getArtistId()] = 1;
				$albums[$track->getAlbumId()] = 1;
				$users[$track->getUserId()] = 1;
			}
			$artists = \array_keys($artists);
			$albums = \array_keys($albums);
			$users = \array_keys($users);

			// categorize each artist as 'remaining' or 'obsolete'
			$remainingArtists = [];
			$obsoleteArtists = [];
			foreach ($artists as $artistId) {
				if ($this->mapper->countByArtist($artistId) === 0) {
					$obsoleteArtists[] = $artistId;
				} else {
					$remainingArtists[] = $artistId;
				}
			}

			// categorize each album as 'remaining' or 'obsolete'
			$remainingAlbums = [];
			$obsoleteAlbums = [];
			foreach ($albums as $albumId) {
				if ($this->mapper->countByAlbum($albumId) === 0) {
					$obsoleteAlbums[] = $albumId;
				} else {
					$remainingAlbums[] = $albumId;
				}
			}

			$result = [
				'deletedTracks'    => $trackIds,
				'remainingAlbums'  => $remainingAlbums,
				'remainingArtists' => $remainingArtists,
				'obsoleteAlbums'   => $obsoleteAlbums,
				'obsoleteArtists'  => $obsoleteArtists,
				'affectedUsers'    => $users
			];
		}

		return $result;
	}

	/**
	 * Marks tracks as dirty, ultimately requesting the user to rescan them
	 * @param int[] $fileIds file IDs of the tracks to mark as dirty
	 * @param string[]|null $userIds the target users; if omitted, the tracks matching the
	 *                      $fileIds are marked for all users
	 */
	public function markTracksDirty(array $fileIds, ?array $userIds=null) : void {
		// be prepared for huge number of file IDs
		$chunkMaxSize = self::MAX_SQL_ARGS - \count($userIds ?? []);
		$idChunks = \array_chunk($fileIds, $chunkMaxSize);
		foreach ($idChunks as $idChunk) {
			$this->mapper->markTracksDirty($idChunk, $userIds);
		}
	}
}
