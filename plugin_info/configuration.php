<?php
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect('admin')) {
    include_file('desktop', '404', 'php');
    die();
}
$plugin = plugin::byId('Palazzetti');
$update = $plugin->getUpdate();

?>
<form class="form-horizontal" id="configuration_plugin_palazzetti">
    <fieldset>
		<legend><i class="fas fa-info-circle"></i> {{Général}}</legend>
		<div class="form-group">
			<div class="col-lg-4">
				<?php if (is_object($update)) { ?>
					<div><label>{{Branche}} :</label> <span class="label label-info"><?php echo htmlspecialchars($update->getConfiguration('version', 'stable')); ?></span></div>
					<div><label>{{Source}} :</label> <?php echo htmlspecialchars($update->getSource()); ?></div>
					<div><label>{{Version}} :</label> <?php echo htmlspecialchars($update->getLocalVersion()); ?></div>
				<?php } ?>
			</div>
			<div class="col-lg-6">
				<a class="btn btn-success btn-sm" target="_blank" rel="noopener noreferrer" href="<?php echo htmlspecialchars($plugin->getDocumentation()); ?>"><i class="fas fa-book"></i> {{Documentation}}</a>
				<a class="btn btn-default btn-sm" target="_blank" rel="noopener noreferrer" href="<?php echo htmlspecialchars($plugin->getChangelog()); ?>"><i class="fas fa-list"></i> {{Changelog}}</a>
			</div>
		</div>
		<legend><i class="fas fa-clock"></i> {{Rafraîchissement}}</legend>
		<div class="form-group">
			<label class="col-lg-4 control-label">{{Intervalle de rafraîchissement des informations (cron)}}<sup>
				<i class="fa fa-question-circle tooltips" title="{{Sélectionnez l'intervalle de récupération des informations}}.</br>{{Par défaut : 15 minutes.}}"></i>
						</sup></label>
			<div class="col-lg-4">
				<select class="configKey form-control" data-l1key="autorefresh" >
					<option value="* * * * *">{{Toutes les minutes}}</option>
					<option value="*/2 * * * *">{{Toutes les 2 minutes}}</option>
					<option value="*/3 * * * *">{{Toutes les 3 minutes}}</option>
					<option value="*/5 * * * *">{{Toutes les 5 minutes}}</option>
					<option value="*/10 * * * *">{{Toutes les 10 minutes}}</option>
					<option value="*/15 * * * *">{{Toutes les 15 minutes}}</option>
					<option value="*/30 * * * *">{{Toutes les 30 minutes}}</option>
					<option value="*/45 * * * *">{{Toutes les 45 minutes}}</option>
					<option value="">{{Jamais}}</option>
				</select>
			</div>
		</div>
	</fieldset>
</form>
