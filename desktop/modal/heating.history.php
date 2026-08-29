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

$eqLogics = eqLogic::byType('Palazzetti');
$selectedEquipmentId = (int) init('id');
$today = date('Y-m-d');
$start = date('Y-m-d', strtotime('-6 days'));
?>

<div id="div_palazzettiHeatingHistory">
    <div class="panel panel-default palazzetti-history-controls">
        <div class="panel-body">
            <div class="row">
                <div class="col-sm-4">
                    <label for="sel_palazzettiHistoryEquipment">{{Équipement}}</label>
                    <select id="sel_palazzettiHistoryEquipment" class="form-control">
                        <?php foreach ($eqLogics as $eqLogic) {
                            $object = $eqLogic->getObject();
                            $label = is_object($object) ? $object->getName() . ' — ' . $eqLogic->getName() : $eqLogic->getName();
                        ?>
                            <option value="<?php echo (int) $eqLogic->getId(); ?>"<?php echo (int) $eqLogic->getId() === $selectedEquipmentId ? ' selected' : ''; ?>>
                                <?php echo htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-sm-3">
                    <label for="in_palazzettiHistoryStart">{{Du}}</label>
                    <input id="in_palazzettiHistoryStart" class="form-control" type="date" value="<?php echo $start; ?>">
                </div>
                <div class="col-sm-3">
                    <label for="in_palazzettiHistoryEnd">{{Au}}</label>
                    <input id="in_palazzettiHistoryEnd" class="form-control" type="date" value="<?php echo $today; ?>">
                </div>
                <div class="col-sm-2">
                    <label>&nbsp;</label>
                    <button type="button" id="bt_loadPalazzettiHeatingHistory" class="btn btn-success btn-block">
                        <i class="fas fa-chart-area"></i> {{Afficher}}
                    </button>
                </div>
            </div>
            <div class="btn-group btn-group-sm palazzetti-history-presets" role="group" aria-label="{{Périodes rapides}}">
                <button type="button" class="btn btn-default" data-history-days="1">{{Aujourd’hui}}</button>
                <button type="button" class="btn btn-default" data-history-days="3">{{3 jours}}</button>
                <button type="button" class="btn btn-default active" data-history-days="7">{{7 jours}}</button>
                <button type="button" class="btn btn-default" data-history-days="14">{{14 jours}}</button>
                <button type="button" class="btn btn-default" data-history-days="30">{{30 jours}}</button>
            </div>
        </div>
    </div>

    <?php if (count($eqLogics) === 0) { ?>
        <div class="alert alert-warning">{{Aucun équipement Palazzetti n’est configuré.}}</div>
    <?php } else { ?>
        <div id="div_palazzettiHistoryAlert"></div>

        <div id="div_palazzettiHistorySummary" class="row palazzetti-history-summary" hidden>
            <div class="col-sm-4">
                <div class="well well-sm"><strong id="span_palazzettiHistoryDuration">—</strong><small>{{Durée totale de chauffe}}</small></div>
            </div>
            <div class="col-sm-4">
                <div class="well well-sm"><strong id="span_palazzettiHistoryCycles">—</strong><small>{{Cycles détectés}}</small></div>
            </div>
            <div class="col-sm-4">
                <div class="well well-sm"><strong id="span_palazzettiHistoryDailyAverage">—</strong><small>{{Moyenne par jour}}</small></div>
            </div>
        </div>

        <legend><i class="fas fa-fire"></i> {{Chronogramme}}</legend>
        <p class="text-muted">
            {{Les zones orangées représentent les cycles du poêle, de la vérification initiale jusqu’au refroidissement. Cliquez sur une légende pour masquer ou afficher une mesure.}}
        </p>
        <div id="div_palazzettiHeatingChart" class="palazzetti-history-chart"></div>

        <legend><i class="fas fa-calendar-alt"></i> {{Comparaison quotidienne}}</legend>
        <div id="div_palazzettiDailyChart" class="palazzetti-history-daily-chart"></div>
        <div class="table-responsive">
            <table class="table table-condensed table-bordered table-striped" id="table_palazzettiDailyHistory">
                <thead>
                    <tr>
                        <th>{{Jour}}</th>
                        <th>{{Durée de chauffe}}</th>
                        <th>{{Cycles}}</th>
                        <th>{{Pellets consommés}}</th>
                        <th>{{Température min.}}</th>
                        <th>{{Température moyenne}}</th>
                        <th>{{Température max.}}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <p class="text-muted">
            <i class="fas fa-info-circle"></i>
            {{Les données disponibles dépendent de la durée de conservation et de l’archivage configurés dans Jeedom. La température moyenne est calculée à partir des relevés enregistrés.}}
        </p>
    <?php } ?>
</div>

<style>
    #div_palazzettiHeatingHistory .palazzetti-history-controls {
        margin-bottom: 12px;
    }
    #div_palazzettiHeatingHistory .palazzetti-history-presets {
        margin-top: 10px;
    }
    #div_palazzettiHeatingHistory .palazzetti-history-summary .well {
        text-align: center;
    }
    #div_palazzettiHeatingHistory .palazzetti-history-summary strong,
    #div_palazzettiHeatingHistory .palazzetti-history-summary small {
        display: block;
    }
    #div_palazzettiHeatingHistory .palazzetti-history-summary strong {
        font-size: 1.65em;
    }
    #div_palazzettiHeatingHistory .palazzetti-history-chart {
        min-height: 430px;
    }
    #div_palazzettiHeatingHistory .palazzetti-history-daily-chart {
        min-height: 260px;
    }
    #table_palazzettiDailyHistory th,
    #table_palazzettiDailyHistory td {
        text-align: center;
        vertical-align: middle;
    }
    #div_palazzettiHeatingChart .highcharts-axis.highcharts-yaxis {
        font-size: 18px;
    }
    #in_palazzettiHistoryStart, #in_palazzettiHistoryEnd {
        line-height: 20px;
    }
</style>

<?php include_file('desktop', 'heating.history', 'js', 'Palazzetti'); ?>
