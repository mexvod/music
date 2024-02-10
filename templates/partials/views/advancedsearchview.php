<div class="view-container playlist-area" id="advanced-search-area">
	<h1 translate>Advanced search</h1>

	<div id="adv-search-controls">
		<table id="adv-search-rules">
			<tr class="adv-search-rule-row" ng-repeat="rule in searchRules">
				<td><select ng-model="rule.rule"><option ng-repeat="ruleType in searchRuleTypes" value="{{ ruleType.key }}">{{ ruleType.name }}</option></select></td>
				<td><select ng-model="rule.operator"><option ng-repeat="ruleOp in operatorsForRule(rule.rule)" value="{{ ruleOp.key }}">{{ ruleOp.name }}</option></select></td>
				<td><input type="text" ng-model="rule.input"/></td>
				<td><a class="icon icon-close" ng-click="removeSearchRule($index)"></a></td>
			</tr>
			<tr class="add-row clickable" ng-click="addSearchRule()">
				<td><a class="icon icon-add"></a></td>
				<td></td>
				<td></td>
				<td></td>
			</tr>
		</table>
		<button ng-click="search()" translate>Search</button><span style="color:red" ng-show="errorDescription" translate>{{ errorDescription }}</span>
	</div>

	<div ng-if="resultList.tracks" class="flat-list-view">
		<h2>
			<span ng-class="{ clickable: resultList.tracks.length }" ng-click="onHeaderClick()">
				<span translate translate-n="resultList.tracks.length" translate-plural="{{ resultList.tracks.length }} results">1 result</span>
				<img ng-if="resultList.tracks.length" class="play svg" alt="{{ 'Play' | translate }}"
					src="<?php \OCA\Music\Utility\HtmlUtil::printSvgPath('play-big') ?>"/>
			</span>
		</h2>
		<track-list
			tracks="resultList.tracks"
			get-track-data="getTrackData"
			play-track="onTrackClick"
			show-track-details="showTrackDetails"
			get-draggable="getDraggable"
		>
		</track-list>
	</div>
</div>
