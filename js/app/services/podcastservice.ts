/**
 * ownCloud - Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Pauli Järvinen <pauli.jarvinen@gmail.com>
 * @copyright Pauli Järvinen 2021 - 2025
 */

import * as ng from 'angular';
import { IService } from 'restangular';
import { gettextCatalog } from 'angular-gettext';
import { MusicRootScope } from 'app/config/musicrootscope'
import { LibraryService, PodcastChannel } from './libraryservice';

ng.module('Music').service('podcastService', [
'$rootScope', '$timeout', '$q', 'libraryService', 'gettextCatalog', 'Restangular',
function($rootScope : MusicRootScope, $timeout : ng.ITimeoutService, $q : ng.IQService, libraryService : LibraryService, gettextCatalog : gettextCatalog, Restangular : IService) {

	// Private functions
	function reloadChannel(channel : PodcastChannel) : ng.IPromise<any> {
		let deferred = $q.defer();

		Restangular.one('podcasts', channel.id).all('update').post({prevHash: channel.hash}).then(
			(result) => {
				if (!result.success) {
					OC.Notification.showTemporary(
							gettextCatalog.getString('Could not update the channel "{{ title }}" from the source', { title: channel.title }));
				} else if (result.updated) {
					libraryService.replacePodcastChannel(result.channel);
					$timeout(() => $rootScope.$emit('viewContentChanged'));
				}
				deferred.resolve(result);
			},
			(_error) => {
				OC.Notification.showTemporary(
						gettextCatalog.getString('Unexpected error when updating the channel "{{ title }}"', { title: channel.title }));
				deferred.resolve(); // resolve even on failure so that callers need to define only one callback
			}
		);

		return deferred.promise;
	}

	/** return true if the operation can be retried */
	function handleExportError(httpError : number) : boolean {
		switch (httpError) {
		case 409: // conflict
			return true;
		case 404: // not found
			OC.Notification.showTemporary(
				gettextCatalog.getString('Playlist or folder not found'));
			return false;
		case 403: // forbidden
			OC.Notification.showTemporary(
				gettextCatalog.getString('Writing to the file is not allowed'));
			return false;
		default: // unexpected
			OC.Notification.showTemporary(
				gettextCatalog.getString('Unexpected error'));
			return false;
		}
	}

	function queryOverwrite(path : string, onSelection : CallableFunction) {
		const fileName = path.split('/').pop();

		OC.dialogs.confirm(
			gettextCatalog.getString('The folder already has a file named "{{ filename }}". Select "Yes" to overwrite it.'+
									' Select "No" to save with another name.',
									{ filename: fileName }),
			gettextCatalog.getString('Overwrite existing file'),
			onSelection,
			true // modal
		);
	}

	function queryFileName(defaultName : string, onNameGiven : CallableFunction) : void {
		const title = gettextCatalog.getString('File name');
		const promptText = gettextCatalog.getString('Save with given file name');
		OCA.Music.Dialogs.prompt(
			title,
			promptText,
			(accept : boolean, name : string) => {
				if (accept) {
					onNameGiven(name);
				}
			},
			defaultName
		);
	}

	function subscribePodcastChannel (url : string) {
		const deferred = $q.defer();

		Restangular.all('podcasts').post({url: url}).then(
			(result) => {
				libraryService.addPodcastChannel(result);
				OC.Notification.showTemporary(
					gettextCatalog.getString('Podcast channel "{{ title }}" added', { title: result.title }));
				if ($rootScope.currentView === '#/podcasts') {
					$timeout(() => $rootScope.$emit('viewContentChanged'));
				}
				deferred.resolve();
			},
			(error) => {
				let errMsg;
				if (error.status === 400) {
					errMsg = gettextCatalog.getString('Invalid RSS feed URL');
				} else if (error.status === 409) {
					errMsg = gettextCatalog.getString('This channel is already subscribed');
				} else {
					errMsg = gettextCatalog.getString('Failed to add the podcast channel');
				}
				OC.Notification.showTemporary(errMsg);
				deferred.reject();
			}
		);

		return deferred.promise;
	}

	// Service API
	return {

		// Show a popup dialog to add a new podcast channel from an RSS feed
		showAddPodcastDialog() : ng.IPromise<any> {
			const deferred = $q.defer();

			OC.dialogs.prompt(
					gettextCatalog.getString('Add a new podcast channel from an RSS feed'),
					gettextCatalog.getString('Add channel'),
					(confirmed : boolean, url : string) => {
						if (confirmed) {
							deferred.notify('started');
							subscribePodcastChannel(url).then(
								() => deferred.resolve(),
								() => deferred.reject()
							)
						} else {
							deferred.reject();
						}
					},
					true, // modal
					gettextCatalog.getString('URL'),
					false // password
			);

			return deferred.promise;
		},

		// Export podcast channels to an OPML file
		exportToFile() : ng.IPromise<any> {
			let deferred = $q.defer();

			let selPath : string = null;

			OCA.Music.Dialogs.folderPicker(
				gettextCatalog.getString('Export podcasts to an OPML file in the selected folder'),
				(path : string) => {
					selPath = path;
					queryFileName(gettextCatalog.getString('Podcasts') + '.opml', onFileNameGiven);
				}
			);

			function onFileNameGiven(name : string, onCollision = 'abort') {
				deferred.notify('started');
				let args = { path: selPath, name: name, oncollision: onCollision };
				Restangular.all('podcasts/export').post(args).then(
					(result) => {
						OC.Notification.showTemporary(
							gettextCatalog.getString('Podcast channels exported to file {{ path }}', { path: result.wrote_to_file }));
						deferred.resolve();
					},
					(error) => {
						deferred.notify('stopped');
						let retry = handleExportError(error.status);
						if (retry) {
							queryOverwrite(error.data.path, (overwrite : boolean) => {
								if (overwrite) {
									onFileNameGiven(name, 'overwrite');
								} else {
									queryFileName(error.data.suggested_name, onFileNameGiven);
								}
							});
						}
					}
				);
			}

			return deferred.promise;
		},

		// Import podcast channels from an OPML file
		importFromFile() : ng.IPromise<any> {
			let deferred = $q.defer();

			OCA.Music.Dialogs.filePicker(
				gettextCatalog.getString('Import podcast channels from the selected OPML file'),
				onFileSelected,
				null
			);

			function onFileSelected(file : string) {
				deferred.notify('started');

				Restangular.one('podcasts/parse').get({filePath: file}).then(
					function(rssUrls) {
						// Got a list of RSS URLs. Now we need to asynchronously subscribe them one by one
						function processNext() {
							if (rssUrls.length > 0) {
								const url = rssUrls.shift();
								subscribePodcastChannel(url).finally(processNext);
							} else {
								deferred.resolve();
							}
						}
						processNext();
					},
					function(_error) {
						OC.Notification.showTemporary(
								gettextCatalog.getString('Failed to import podcasts from the file {{ file }}', { file: file }));
						deferred.reject();
					}
				);
			};

			return deferred.promise;
		},

		// Refresh the contents of the given podcast channel
		reloadPodcastChannel(channel : PodcastChannel) : ng.IPromise<any> {
			return reloadChannel(channel).then((result) => {
				if (result?.updated) {
					OC.Notification.showTemporary(
							gettextCatalog.getString('The channel was updated from the source'));
				} else if (result?.success) {
					OC.Notification.showTemporary(
							gettextCatalog.getString('The channel was already up-to-date'));
				} else {
					// nothing to do, error has already been shown by the reloadChannel function
				}
			});
		},

		// Refresh the contents of all the subscribed podcast channels
		reloadAllPodcasts() : ng.IPromise<any> {
			const deferred = $q.defer();
			const channels = libraryService.getAllPodcastChannels();
			let index = 0;
			let changeCount = 0;

			const processNextChannel = function() {
				if (index < channels.length) {
					reloadChannel(channels[index]).then((result) => {
						if (result?.updated) {
							changeCount++;
						}
						index++;
						processNextChannel();
					});
				}
				else {
					if (changeCount === 0) {
						OC.Notification.showTemporary(
							gettextCatalog.getString('All channels were already up-to-date'));
					} else {
						OC.Notification.showTemporary(
							gettextCatalog.getPlural(changeCount,
								'Changes were loaded for one channel',
								'Changes were loaded for {{ count }} channels', { count: changeCount })
						);
					}

					deferred.resolve();
				}
			};
			processNextChannel();

			return deferred.promise;
		},

		// Remove a single previously subscribed podcast channel
		removePodcastChannel(channel : PodcastChannel) : ng.IPromise<any> {
			const deferred = $q.defer();

			const doDelete = function() {
				deferred.notify('started');
				Restangular.one('podcasts', channel.id).remove().then(
					(result) => {
						if (!result.success) {
							OC.Notification.showTemporary(
									gettextCatalog.getString('Could not remove the channel "{{ title }}"', { title: channel.title }));
							deferred.reject();
						} else {
							libraryService.removePodcastChannel(channel);
							$timeout(() => $rootScope.$emit('viewContentChanged'));
							deferred.resolve();
						}
					},
					(_error) => {
						OC.Notification.showTemporary(
								gettextCatalog.getString('Could not remove the channel "{{ title }}"', { title: channel.title }));
						deferred.reject();
					}
				);
			};

			OC.dialogs.confirm(
					gettextCatalog.getString('Are you sure to remove the podcast channel "{{ title }}"?', { title: channel.title }),
					gettextCatalog.getString('Remove channel'),
					(confirmed : boolean) => {
						if (confirmed) {
							doDelete();
						}
					},
					true
			);

			return deferred.promise;
		}
	};
}]);
