<?php

/**
 * ownCloud - Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Pauli Järvinen <pauli.jarvinen@gmail.com>
 * @copyright Morris Jobke 2014
 * @copyright Pauli Järvinen 2017 - 2024
 */

namespace OCA\Music;

use OCA\Music\AppInfo\Application;

$app = \OC::$server->query(Application::class);

$app->registerRoutes($this, ['routes' => [
	// page
	['name' => 'page#index', 'url' => '/',			'verb' => 'GET'],
	// also the Ampache and Subsonic base URLs are directed to the front page, as several clients provide such links
	['name' => 'page#index', 'url' => '/subsonic',	'verb' => 'GET',	'postfix' => '_subsonic'],
	['name' => 'page#index', 'url' => '/ampache',	'verb' => 'GET',	'postfix' => '_ampache'],

	// log
	['name' => 'log#log', 'url' => '/api/log', 'verb' => 'POST'],

	// Music app proprietary API
	['name' => 'musicApi#prepareCollection','url' => '/api/prepare_collection',			'verb' => 'POST'],
	['name' => 'musicApi#collection',		'url' => '/api/collection',					'verb' => 'GET'],
	['name' => 'musicApi#folders',			'url' => '/api/folders',					'verb' => 'GET'],
	['name' => 'musicApi#genres',			'url' => '/api/genres',						'verb' => 'GET'],
	['name' => 'musicApi#trackByFileId',	'url' => '/api/file/{fileId}',				'verb' => 'GET'],
	['name' => 'musicApi#download',			'url' => '/api/file/{fileId}/download',		'verb' => 'GET'],
	['name' => 'musicApi#filePath',			'url' => '/api/file/{fileId}/path',			'verb' => 'GET'],
	['name' => 'musicApi#fileInfo',			'url' => '/api/file/{fileId}/info',			'verb' => 'GET'],
	['name' => 'musicApi#fileDetails',		'url' => '/api/file/{fileId}/details',		'verb' => 'GET'],
	['name' => 'musicApi#getScanState',		'url' => '/api/scanstate',					'verb' => 'GET'],
	['name' => 'musicApi#scan',				'url' => '/api/scan',						'verb' => 'POST'],
	['name' => 'musicApi#resetScanned'	,	'url' => '/api/resetscanned',				'verb' => 'POST'],
	['name' => 'musicApi#artistDetails',	'url' => '/api/artist/{artistId}/details',	'verb' => 'GET'],
	['name' => 'musicApi#similarArtists',	'url' => '/api/artist/{artistId}/similar',	'verb' => 'GET'],
	['name' => 'musicApi#albumDetails',		'url' => '/api/album/{albumId}/details',	'verb' => 'GET'],
	['name' => 'musicApi#scrobble',			'url' => '/api/track/{trackId}/scrobble',	'verb' => 'POST'],

	['name' => 'advSearch#search',		'url' => '/api/advanced_search',			'verb' => 'POST'],

	// Cover art API
	['name' => 'coverApi#cachedCover',	'url' => '/api/cover/{hash}',				'verb' => 'GET'],
	['name' => 'coverApi#artistCover',	'url' => '/api/artist/{artistId}/cover',	'verb' => 'GET'],
	['name' => 'coverApi#albumCover',	'url' => '/api/album/{albumId}/cover',		'verb' => 'GET'],

	// Shiva API https://shiva.readthedocs.io/en/latest/index.html
	['name' => 'shivaApi#artists',		'url' => '/api/artists',					'verb' => 'GET'],
	['name' => 'shivaApi#artist',		'url' => '/api/artist/{artistId}',			'verb' => 'GET'],
	//['name' => 'shivaApi#artistShows','url' => '/api/artist/{artistId}/shows',	'verb' => 'GET'],
	['name' => 'shivaApi#albums',		'url' => '/api/albums',						'verb' => 'GET'],
	['name' => 'shivaApi#album',		'url' => '/api/album/{albumId}',			'verb' => 'GET'],
	['name' => 'shivaApi#tracks',		'url' => '/api/tracks',						'verb' => 'GET'],
	['name' => 'shivaApi#track',		'url' => '/api/track/{trackId}',			'verb' => 'GET'],
	//['name' => 'shivaApi#trackLyrics','url' => '/api/track/{trackId}/lyrics',		'verb' => 'GET'],

	['name' => 'share#fileInfo',		'url' => '/api/share/{token}/{fileId}/info',	'verb' => 'GET'],
	['name' => 'share#parsePlaylist',	'url' => '/api/share/{token}/{fileId}/parse',	'verb' => 'GET'],

	// playlist API
	['name' => 'playlistApi#getAll',		'url' => '/api/playlists',				'verb' => 'GET'],
	['name' => 'playlistApi#create',		'url' => '/api/playlists',				'verb' => 'POST'],
	['name' => 'playlistApi#generate',		'url' => '/api/playlists/generate',		'verb' => 'GET'],
	['name' => 'playlistApi#get',			'url' => '/api/playlists/{id}',			'verb' => 'GET'],
	['name' => 'playlistApi#delete',		'url' => '/api/playlists/{id}',			'verb' => 'DELETE'],
	['name' => 'playlistApi#update',		'url' => '/api/playlists/{id}',			'verb' => 'PUT'],
	['name' => 'playlistApi#addTracks',		'url' => '/api/playlists/{id}/add',		'verb' => 'POST'],
	['name' => 'playlistApi#removeTracks',	'url' => '/api/playlists/{id}/remove',	'verb' => 'POST'],
	['name' => 'playlistApi#reorder',		'url' => '/api/playlists/{id}/reorder',	'verb' => 'POST'],
	['name' => 'playlistApi#exportToFile',	'url' => '/api/playlists/{id}/export',	'verb' => 'POST'],
	['name' => 'playlistApi#importFromFile','url' => '/api/playlists/{id}/import',	'verb' => 'POST'],
	['name' => 'playlistApi#getCover',		'url' => '/api/playlists/{id}/cover',	'verb' => 'GET'],
	['name' => 'playlistApi#parseFile',		'url' => '/api/playlists/file/{fileId}','verb' => 'GET'],

	// radio API
	['name' => 'radioApi#getAll',			'url' => '/api/radio',					'verb' => 'GET'],
	['name' => 'radioApi#create',			'url' => '/api/radio',					'verb' => 'POST'],
	['name' => 'radioApi#exportAllToFile',	'url' => '/api/radio/export',			'verb' => 'POST'],
	['name' => 'radioApi#importFromFile',	'url' => '/api/radio/import',			'verb' => 'POST'],
	['name' => 'radioApi#resetAll',			'url' => '/api/radio/reset',			'verb' => 'POST'],
	['name' => 'radioApi#resolveStreamUrl',	'url' => '/api/radio/streamurl',		'verb' => 'GET'],
	['name' => 'radioApi#hlsManifest',		'url' => '/api/radio/hls/manifest',		'verb' => 'GET'],
	['name' => 'radioApi#hlsSegment',		'url' => '/api/radio/hls/segment',		'verb' => 'GET'],
	['name' => 'radioApi#get',				'url' => '/api/radio/{id}',				'verb' => 'GET'],
	['name' => 'radioApi#delete',			'url' => '/api/radio/{id}',				'verb' => 'DELETE'],
	['name' => 'radioApi#update',			'url' => '/api/radio/{id}',				'verb' => 'PUT'],
	['name' => 'radioApi#getChannelInfo',	'url' => '/api/radio/{id}/info',		'verb' => 'GET'],
	['name' => 'radioApi#stationStreamUrl',	'url' => '/api/radio/{id}/streamurl',	'verb' => 'GET'],

	// podcast API
	['name' => 'podcastApi#getAll',			'url' => '/api/podcasts',						'verb' => 'GET'],
	['name' => 'podcastApi#subscribe',		'url' => '/api/podcasts',						'verb' => 'POST'],
	['name' => 'podcastApi#get',			'url' => '/api/podcasts/{id}',					'verb' => 'GET'],
	['name' => 'podcastApi#channelDetails',	'url' => '/api/podcasts/{id}/details',			'verb' => 'GET'],
	['name' => 'podcastApi#episodeDetails',	'url' => '/api/podcasts/episodes/{id}/details',	'verb' => 'GET'],
	['name' => 'podcastApi#unsubscribe',	'url' => '/api/podcasts/{id}',					'verb' => 'DELETE'],
	['name' => 'podcastApi#updateChannel',	'url' => '/api/podcasts/{id}/update',			'verb' => 'POST'],
	['name' => 'podcastApi#resetAll',		'url' => '/api/podcasts/reset',					'verb' => 'POST'],

	// favorites API
	['name' => 'favorites#favorites',			'url' => '/api/favorites',						'verb' => 'GET'],
	['name' => 'favorites#setFavoriteTrack',	'url' => '/api/track/{id}/favorite',			'verb' => 'PUT'],
	['name' => 'favorites#setFavoriteAlbum',	'url' => '/api/album/{id}/favorite',			'verb' => 'PUT'],
	['name' => 'favorites#setFavoriteArtist',	'url' => '/api/artist/{id}/favorite',			'verb' => 'PUT'],
	['name' => 'favorites#setFavoritePlaylist',	'url' => '/api/playlists/{id}/favorite',		'verb' => 'PUT'],
	['name' => 'favorites#setFavoriteChannel',	'url' => '/api/podcasts/{id}/favorite',			'verb' => 'PUT'],
	['name' => 'favorites#setFavoriteEpisode',	'url' => '/api/podcasts/episodes/{id}/favorite','verb' => 'PUT'],

	// settings API
	['name' => 'setting#getAll',			'url' => '/api/settings',							'verb' => 'GET'],
	['name' => 'setting#userPath',			'url' => '/api/settings/user/path',					'verb' => 'POST'],
	['name' => 'setting#userExcludedPaths',	'url' => '/api/settings/user/exclude_paths',		'verb' => 'POST'],
	['name' => 'setting#enableScanMetadata','url' => '/api/settings/user/enable_scan_metadata',	'verb' => 'POST'],
	['name' => 'setting#ignoredArticles',	'url' => '/api/settings/user/ignored_articles',		'verb' => 'POST'],
	['name' => 'setting#getUserKeys',		'url' => '/api/settings/user/keys',					'verb' => 'GET'],
	['name' => 'setting#createUserKey',		'url' => '/api/settings/user/keys',					'verb' => 'POST'],
	['name' => 'setting#removeUserKey',		'url' => '/api/settings/user/keys/{id}',			'verb' => 'DELETE'],
	['name' => 'setting#createUserKeyCors',	'url' => '/api/settings/userkey/generate',			'verb' => 'POST'], # external API, keep inconsistent url to maintain compatibility

	// Ampache API https://github.com/ampache/ampache/wiki/Ampache-API
	['name' => 'ampache#xmlApi',			'url' => '/ampache/server/xml.server.php',	'verb' => 'GET'],
	['name' => 'ampache#jsonApi',			'url' => '/ampache/server/json.server.php',	'verb' => 'GET'],
	// Ampache API - POST version for JustPlayer. Defining 'postfix' allows binding two routes to the same handler.
	['name' => 'ampache#xmlApi',			'url' => '/ampache/server/xml.server.php',	'verb' => 'POST',		'postfix' => '_post'],
	['name' => 'ampache#jsonApi',			'url' => '/ampache/server/json.server.php',	'verb' => 'POST',		'postfix' => '_post'],
	// Ampache API - Workaround for AmpacheAlbumPlayer
	['name' => 'ampache#xmlApi',			'url' => '/ampache/server/xml.server.php/',	'verb' => 'GET',		'postfix' => '_aap'],
	// Ampache API - Allow CORS pre-flight for web clients from different domains
	['name' => 'ampache#preflightedCors',	'url' => '/ampache/server/xml.server.php',	'verb' => 'OPTIONS'],
	['name' => 'ampache#preflightedCors',	'url' => '/ampache/server/json.server.php',	'verb' => 'OPTIONS',	'postfix' => '_json'],
	// Ampache image API
	['name' => 'ampacheImage#image',		'url' => '/ampache/image.php',				'verb' => 'GET'],

	// Subsonic API http://www.subsonic.org/pages/api.jsp
	// Some clients use POST while others use GET. Defining 'postfix' allows binding two routes to the same handler.
	['name' => 'subsonic#handleRequest',	'url' => '/subsonic/rest/{method}',	'verb' => 'GET',	'requirements' => ['method' => '[a-zA-Z0-9\.]+']],
	['name' => 'subsonic#handleRequest',	'url' => '/subsonic/rest/{method}',	'verb' => 'POST',	'requirements' => ['method' => '[a-zA-Z0-9\.]+'],	'postfix' => '_post'],

]]);
