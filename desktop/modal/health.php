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

if (!isConnect('admin')) {
    throw new Exception(__('401 - Accès non autorisé', __FILE__));
}

/**
 * Échappe une valeur destinée à la modale Santé.
 *
 * @param mixed $value Valeur à afficher.
 * @return string Valeur échappée en UTF-8.
 */
function palazzettiHealthEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$checks = Palazzetti::health();
$eqLogics = eqLogic::byType('Palazzetti');
$enabled = 0;
$online = 0;
$gatewayOffline = 0;
$stoveOffline = 0;
foreach ($eqLogics as $eqLogic) {
    if (!$eqLogic->getIsEnable()) {
        continue;
    }
    $enabled++;
    $communication = $eqLogic->getCommunicationHealth();
    if ($communication['offline']) {
        $gatewayOffline++;
    } elseif ($communication['stoveOffline']) {
        $stoveOffline++;
    } elseif ($eqLogic->getStatus('lastCommunication') !== '') {
        $online++;
    }
}
?>

<div id="div_healthPalazzetti">
    <div class="clearfix" style="margin-bottom:10px;">
        <button type="button" class="btn btn-default pull-right" id="bt_refreshPalazzettiHealth">
            <i class="fas fa-sync"></i> {{Rafraîchir}}
        </button>
        <span class="label label-success" style="font-size:1em;"><?php echo (int) $online; ?> {{En ligne}}</span>
        <span class="label label-danger" style="font-size:1em;"><?php echo (int) $gatewayOffline; ?> {{Passerelle(s) hors ligne}}</span>
        <span class="label label-warning" style="font-size:1em;"><?php echo (int) $stoveOffline; ?> {{Poêle(s) indisponible(s)}}</span>
        <span class="label label-info" style="font-size:1em;"><?php echo count($eqLogics); ?> {{Équipements}}, <?php echo (int) $enabled; ?> {{actifs}}</span>
    </div>

    <legend><i class="fas fa-heartbeat"></i> {{État du plugin}}</legend>
    <div class="table-responsive">
        <table class="table table-condensed table-bordered">
            <thead>
                <tr>
                    <th>{{Contrôle}}</th>
                    <th>{{État}}</th>
                    <th>{{Détail}}</th>
                    <th>{{Conseil}}</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($checks as $check) { ?>
                    <tr>
                        <td><?php echo palazzettiHealthEscape($check['test']); ?></td>
                        <td>
                            <span class="label label-<?php echo !empty($check['state']) ? 'success' : 'danger'; ?>">
                                <?php echo !empty($check['state']) ? 'OK' : 'NOK'; ?>
                            </span>
                        </td>
                        <td><?php echo palazzettiHealthEscape($check['result']); ?></td>
                        <td><?php echo palazzettiHealthEscape($check['advice']); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <legend><i class="fas fa-fire"></i> {{Équipements}}</legend>
    <div class="table-responsive">
        <table class="table table-condensed table-bordered table-striped">
            <thead>
                <tr>
                    <th>{{Nom}}</th>
                    <th>{{Passerelle}}</th>
                    <th>{{Adresse IP}}</th>
                    <th>{{Modèle}}</th>
                    <th>{{Numéro de série}}</th>
                    <th>{{État}}</th>
                    <th>{{Dernière communication}}</th>
                    <th>{{Dernière erreur}}</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($eqLogics as $eqLogic) {
                    $communication = $eqLogic->getCommunicationHealth();
                    $lastCommunication = (string) $eqLogic->getStatus('lastCommunication');
                    $gatewayType = trim((string) $eqLogic->getConfiguration('gatewayType', ''));
                    if ($gatewayType === '') {
                        $gatewayType = in_array($eqLogic->getConfiguration('isWirelessPalaControl'), array(true, 1, '1', 'true'), true)
                            ? 'WPalaControl'
                            : 'Connection Box';
                    }
                    $address = trim((string) $eqLogic->getConfiguration('addressip'));
                    $isOnline = $eqLogic->getIsEnable() && !$communication['offline'] && !$communication['stoveOffline'] && $lastCommunication !== '';
                ?>
                    <tr<?php echo $eqLogic->getIsEnable() ? '' : ' style="opacity:.55;"'; ?>>
                        <td>
                            <a href="<?php echo palazzettiHealthEscape($eqLogic->getLinkToConfiguration()); ?>">
                                <?php echo $eqLogic->getSafeHumanNameHtml(); ?>
                            </a>
                        </td>
                        <td><?php echo palazzettiHealthEscape($gatewayType); ?></td>
                        <td>
                            <?php if ($address !== '' && Palazzetti::isLocalAddress($address)) { ?>
                                <a href="http://<?php echo palazzettiHealthEscape($address); ?>" target="_blank" rel="noopener noreferrer">
                                    <code><?php echo palazzettiHealthEscape($address); ?></code>
                                </a>
                            <?php } elseif ($address !== '') { ?>
                                <code class="text-danger"><?php echo palazzettiHealthEscape($address); ?></code>
                            <?php } else { ?>
                                <span class="text-muted">—</span>
                            <?php } ?>
                        </td>
                        <td><?php echo palazzettiHealthEscape($eqLogic->getConfiguration('model', '—')); ?></td>
                        <td><?php echo palazzettiHealthEscape($eqLogic->getConfiguration('serialNumber', '—')); ?></td>
                        <td>
                            <?php if (!$eqLogic->getIsEnable()) { ?>
                                <span class="label label-default">{{Désactivé}}</span>
                            <?php } elseif ($communication['offline']) { ?>
                                <span class="label label-danger">{{Passerelle hors ligne}}</span>
                            <?php } elseif ($communication['stoveOffline']) { ?>
                                <span class="label label-warning">{{Passerelle joignable, poêle indisponible}}</span>
                            <?php } elseif ($isOnline) { ?>
                                <span class="label label-success">{{En ligne}}</span>
                            <?php } else { ?>
                                <span class="label label-warning">{{Non testé}}</span>
                            <?php } ?>
                        </td>
                        <td><?php echo palazzettiHealthEscape($lastCommunication !== '' ? $lastCommunication : '—'); ?></td>
                        <td class="text-danger" style="max-width:320px;overflow-wrap:anywhere;">
                            <?php if ($communication['error'] !== '') { ?>
                                <?php echo palazzettiHealthEscape($communication['error']); ?>
                                <?php if ($communication['lastFailure'] > 0) { ?>
                                    <br><small><?php echo palazzettiHealthEscape(date('Y-m-d H:i:s', $communication['lastFailure'])); ?></small>
                                <?php } ?>
                                <?php if ($communication['failureCount'] > 0) { ?>
                                    <br><small><?php echo (int) $communication['failureCount']; ?> {{échec(s) consécutif(s)}}</small>
                                <?php } ?>
                                <?php if ($communication['lastRediscovery'] > 0) { ?>
                                    <br><small>{{Dernière redécouverte}} : <?php echo palazzettiHealthEscape(date('Y-m-d H:i:s', $communication['lastRediscovery'])); ?></small>
                                <?php } ?>
                                <?php if ($communication['rediscoveredAddress'] !== '') { ?>
                                    <br><small>{{Adresse candidate à confirmer}} : <code><?php echo palazzettiHealthEscape($communication['rediscoveredAddress']); ?></code></small>
                                <?php } ?>
                            <?php } else { ?>
                                <span class="text-muted">—</span>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                <?php if (count($eqLogics) === 0) { ?>
                    <tr><td colspan="8" class="text-center text-muted">{{Aucun équipement Palazzetti}}</td></tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        {{La découverte UDP nécessite que Jeedom et les passerelles soient sur le même réseau local. Les broadcasts sont généralement bloqués entre VLAN et avec le mode réseau bridge de Docker.}}
    </div>
</div>
