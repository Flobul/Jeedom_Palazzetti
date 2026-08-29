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

/**
 * Installe les tâches et valeurs de configuration du plugin.
 *
 * @return void
 */
function Palazzetti_install() {

    $cron = cron::byClassAndFunction('Palazzetti', 'pull');
    if (!is_object($cron)) {
        $cron = new cron();
        $cron->setClass('Palazzetti');
        $cron->setFunction('pull');
        $cron->setEnable(1);
        $cron->setDeamon(0);
        $cron->setSchedule('* * * * *');
        $cron->setTimeout(180);
        $cron->save();
    }

    config::save('auto_discovery_interval', '', 'Palazzetti');
    config::save('auto_discovery_safe_migrated', 1, 'Palazzetti');
    if (config::byKey('allow_parameter_writes', 'Palazzetti', null) === null) {
        config::save('allow_parameter_writes', 0, 'Palazzetti');
    }
    Palazzetti::configureAutoDiscoveryCron();
    Palazzetti::migrateEquipmentCommands();
}

/**
 * Met à niveau les tâches, la configuration et les commandes du plugin.
 *
 * @return void
 */
function Palazzetti_update() {

    $cron = cron::byClassAndFunction('Palazzetti', 'pull');
    if (!is_object($cron)) {
        $cron = new cron();
    }
    $cron->setClass('Palazzetti');
    $cron->setFunction('pull');
    $cron->setEnable(1);
    $cron->setDeamon(0);
    $cron->setSchedule('* * * * *');
    $cron->setTimeout(180);
    $cron->save();
    $cron->stop();

    if (config::byKey('auto_discovery_safe_migrated', 'Palazzetti', null) === null) {
        config::save('auto_discovery_interval', '', 'Palazzetti');
        config::save('auto_discovery_safe_migrated', 1, 'Palazzetti');
    }
    if (config::byKey('allow_parameter_writes', 'Palazzetti', null) === null) {
        config::save('allow_parameter_writes', 0, 'Palazzetti');
    }
    Palazzetti::configureAutoDiscoveryCron();
    Palazzetti::migrateEquipmentCommands();
}

/**
 * Supprime les tâches planifiées appartenant au plugin.
 *
 * @return void
 */
function Palazzetti_remove() {

    $cron = cron::byClassAndFunction('Palazzetti', 'pull');
    if (is_object($cron)) {
        $cron->remove();
    }

    $cronDiscover = cron::byClassAndFunction('Palazzetti', 'cronAutoDiscover');
    if (is_object($cronDiscover)) {
        $cronDiscover->stop();
        $cronDiscover->remove();
    }
}
?>
