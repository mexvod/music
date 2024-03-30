<div class="view-container playlist-area" id="adv-search-area">
	<h1 translate>Advanced search</h1>

	<div id="adv-search-controls">
		<div id="adv-search-common-parameters">
			<span translate>Search for</span>
			<select id="adv-search-type" ng-model="entityType" ng-change="onEntityTypeChanged()">
				<option value="track" translate>tracks</option>
				<option value="album" translate>albums</option>
			</select>
			<select id="adv-search-conjunction" ng-model="conjunction">
				<option value="and" translate>matching all rules</option>
				<option value="or" translate>matching any rule</option>
			</select>
			<span translate>limiting results to</span>
			<select id="adv-search-limit" ng-model="maxResults">
				<option value="" translate>unlimited</option>
				<option value="10" translate>10 matches</option>
				<option value="30" translate>30 matches</option>
				<option value="100" translate>100 matches</option>
				<option value="500" translate>500 matches</option>
			</select>
			<span translate>ordering</span>
			<select id="adv-search-order" ng-model="order">
				<option value="name" translate>by name</option>
				<option value="parent" translate>by artist</option>
				<option value="newest" translate>by add time</option>
				<option value="play_count" ng-if="entityType == 'track'" translate>by play count</option>
				<option value="last_played" ng-if="entityType == 'track'" translate>by recent play</option>
				<option value="rating" translate>by rating</option>
				<option value="random" translate>randomly</option>
			</select>
		</div>
		<div id="adv-search-rules">
			<div class="adv-search-rule-row" ng-repeat="rule in searchRules" on-enter="search()">
				<select ng-model="rule.rule" ng-change="onRuleChanged(rule)">
					<option ng-if="!searchRuleTypes[entityType][0].label" ng-repeat="ruleType in searchRuleTypes[entityType][0].options" value="{{ ruleType.key }}">{{ ruleType.name }}</option>
					<optgroup ng-repeat="category in searchRuleTypes[entityType]" label="{{ category.label }}" ng-if="category.label">
						<option ng-repeat="ruleType in category.options" value="{{ ruleType.key }}">{{ ruleType.name }}</option>
					</optgroup>
				</select>

				<select ng-model="rule.operator">
					<option ng-repeat="ruleOp in operatorsForRule(rule.rule)" value="{{ ruleOp.key }}">{{ ruleOp.name }}</option>
				</select>

				<input ng-if="ruleType(rule.rule) == 'text'" type="text" ng-model="rule.input"/>
				<input ng-if="['numeric', 'numeric_limit'].includes(ruleType(rule.rule))" type="number" ng-model="rule.input"/>
				<input ng-if="ruleType(rule.rule) == 'date'" type="date" ng-model="rule.input"/>
				<select ng-if="ruleType(rule.rule) == 'numeric_rating'" ng-model="rule.input">
					<option ng-repeat="val in [0,1,2,3,4,5]" value="{{ val }}">{{ val }} Stars</option>
				</select>
				<select ng-if="ruleType(rule.rule) == 'playlist'" ng-model="rule.input">
					<option ng-repeat="pl in playlists" value="{{ pl.id }}">{{ pl.name }}</option>
				</select>

				<a class="icon icon-close" ng-click="removeSearchRule($index)"></a>
			</div>
			<div class="add-row clickable" ng-click="addSearchRule()">
				<a class="icon icon-add"></a>
			</div>
		</div>
		<button ng-click="search()" translate>Search</button><span style="color:red" ng-show="errorDescription" translate>{{ errorDescription }}</span>
	</div>

	<div ng-if="resultList.tracks" class="flat-list-view">
		<h2 ui-draggable="true" drag="getHeaderDraggable()">
			<span ng-class="{ clickable: resultCount() }" ng-click="onHeaderClick()">
				<span translate translate-n="resultCount()" translate-plural="{{ resultCount() }} results">1 result</span>
				<img ng-if="resultCount()" class="play svg" alt="{{ 'Play' | translate }}"
					src="<?php \OCA\Music\Utility\HtmlUtil::printSvgPath('play-big') ?>"/>
			</span>
		</h2>
		<track-list
			tracks="resultList.tracks"
			get-track-data="getTrackData"
			play-track="onTrackClick"
			show-track-details="showTrackDetails"
			get-draggable="getTrackDraggable"
		>
		</track-list>
		<track-list
			tracks="resultList.albums"
			get-track-data="getAlbumData"
			play-track="onAlbumClick"
			show-track-details="showAlbumDetails"
			get-draggable="getAlbumDraggable"
		>
		</track-list>
	</div>
</div>
