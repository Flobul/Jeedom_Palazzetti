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

var palaHiddenParam = {
    0: 'WaterPIDScanSet',
    1: 'LowerGassesTemperatureOffset',
    2: 'MBVersion',
    3: 'UIVersion',
    4: 'ATechCode',
    5: 'DateDay',
    6: 'DateMonth',
    7: 'DateYear',
    8: 'SystemMode1',
    9: 'SystemMode2',
    10: 'CheckFirePeriod',
    11: 'FeederStartRegulation',
    12: 'FeederRegulationScanTime',
    13: 'FeederToFANSlopeAdjust',
    14: 'FeederToFAN2SlopeAdjust',
    15: 'MaxFeederPeriod',
    16: 'ModulationStartAir',
    17: 'UiConfiguration1',
    18: 'UiConfiguration2',
    19: 'UiConfiguration3',
    20: 'UiConfiguration4',
    21: 'PressureRegulatorScanTime',
    22: 'PowerChangeDelayStepRegulation',
    23: 'Reserved',
    24: 'Reserved',
    25: 'SpeedFeedbackPulsesPerRotation',
    26: 'FeederPauseDivider',
    27: 'Configuration1L',
    28: 'Configuration1H',
    29: 'Configuration2L',
    30: 'Configuration2H',
    31: 'Configuration3L',
    32: 'Configuration3H',
    33: 'Configuration4L',
    34: 'Configuration4H',
    35: 'Configuration5L',
    36: 'Configuration5H',
    37: 'Configuration6L',
    38: 'Configuration6H',
    39: 'Configuration7L',
    40: 'Configuration7H',
    41: 'Configuration8L',
    42: 'Configuration8H',
    43: 'Configuration9L',
    44: 'Configuration9H',
    45: 'Configuration10L',
    46: 'Configuration10H',
    47: 'Configuration11L',
    48: 'Configuration11H',
    49: 'Configuration12L',
    50: 'Configuration12H',
    51: 'Configuration13L',
    52: 'Configuration13H',
    53: 'FeederFactorPelletsType1',
    54: 'FAN1FactorPelletsType1',
    55: 'FeederFactorPelletsType2',
    56: 'FAN1FactorPelletsType2',
    57: 'FeederFactorPelletsType3',
    58: 'FAN1FactorPelletsType3',
    59: 'FAN1WoodType1',
    60: 'FAN1WoodType2',
    61: 'FAN1WoodType3',
    62: 'Reserved',
    63: 'Reserved',
    64: 'Reserved',
    65: 'FullMagazineFeeder',
    66: 'WarningMagazineFeeder',
    67: 'EmptyMagazineFeeder',
    68: 'PressureToPulseFactor',
    69: 'CalibrationFanSpeed',
    70: 'MaxWaterPumpSpeed',
    71: 'MinWaterPumpSpeed',
    72: 'PIDWaterKp',
    73: 'PIDWaterKi',
    74: 'PIDWaterScale',
    75: 'MaxFAN1Voltage',
    76: 'MinFAN1Voltage',
    77: 'AccumulatorPumpPIDScanTime',
    78: 'LeveltronicCmPerPulse',
    79: 'PIDWaterLimit',
    80: 'PIDAirKp',
    81: 'PIDAirKi',
    82: 'PIDAirScale',
    83: 'PIDAirLimit',
    84: 'PIDAirFlowKp',
    85: 'PIDAirFlowKi',
    86: 'PIDAirFlowScale',
    87: 'PIDAirFlowLimit',
    88: 'SafetyFANStopFire',
    89: 'SafetyFANTestFire',
    90: 'SafetyFANHeatUp',
    91: 'SafetyFANFuelIgnition',
    92: 'SafetyFANFireCheck',
    93: 'SafetyFANPower1',
    94: 'SafetyFANPower2',
    95: 'SafetyFANPower3',
    96: 'SafetyFANPower4',
    97: 'SafetyFANPower5',
    98: 'SafetyFANCleaning',
    99: 'SafetyKeepFire',
    100: 'FeederRotationsPerMinute',
    101: 'FeederGramsPerTurn',
    102: 'Par50WaterLimits',
    103: 'Par51WaterLimits',
    104: 'Par50AirLimits',
    105: 'Par51AirLimits',
    106: 'Reserved',
    107: 'Reserved',
    108: 'MaxWaterTemperature',
    109: 'Reserved',
    110: 'FactoryReload'
};

function addHiddenParamToTable(_param) {
    var tr = '<tr class="param" data-param_id="' + init(_param.id) + '">'
    tr += '<td>'
    tr += '    <input class="paramAttr form-control input-sm roundedLeft" disabled data-l1key="id" placeholder="{{Numéro du paramètre}}">'
    tr += '</td>'

    tr += '<td>'
    tr += '    <span class="paramAttr roundedLeft" disabled data-l1key="description"></span>'
    tr += '</td>'

    tr += '<td>'
    tr += '    <input class="eqLogicAttr form-control input-sm" data-l1key="configuration" data-l2key="commentaire_caches" data-l3key=' + init(_param.id) + '>'
    tr += '</td>'

    tr += '<td>'
    tr += '    <input class="paramAttr form-control input-sm roundedLeft" data-l1key="value" title="{{Valeur}}" placeholder="{{Valeur}}" style="width:100px">'
    tr += '</td>'

    tr += '<td>'
    tr += '    <a class="btn btn-success btn-xs paramAction" data-action="update" title="{{Rafraîchir le paramètre}}"><i class="fas fa-sync"></i> </a>';
    tr += '    <a class="btn btn-warning btn-xs paramAction" data-action="modify" title="{{Modifier le paramètre}}"><i class="fas fa-rss"></i> {{Modifier}}</a>';
    tr += '</td>'
    tr += '</tr>';

	    const body = document.querySelector('#table_param tbody');
	    body.insertAdjacentHTML('beforeend', tr);
	    body.lastElementChild.setJeeValues(_param, '.paramAttr');
}

Object.keys(palaHiddenParam).forEach(function(id) { addHiddenParamToTable({id: id, description: palaHiddenParam[id]}); });

const hiddenParameterTable = document.getElementById('table_param');
const hiddenRefreshButton = document.querySelector('.paramAction[data-action="refresh"]');
const hiddenSaveButton = document.querySelector('.paramAction[data-action="saveComments"]');

function setHiddenButtonState(button, busy, busyLabel, idleLabel) {
    if (!button) return;
    button.classList.toggle('btn-warning', busy);
    button.classList.toggle('btn-success', !busy);
    button.classList.toggle('disabled', busy);
    button.innerHTML = '<i class="fas fa-' + (button === hiddenSaveButton ? 'save' : 'sync') + (busy ? ' fa-spin' : '') + '"></i> ' + (busy ? busyLabel : idleLabel);
}

function getHiddenParamValue(id, paramId) {
    if (paramId === undefined) setHiddenButtonState(hiddenRefreshButton, true, '{{Rafraîchissement en cours}}', '{{Rafraîchir les paramètres}}');
    domUtils.ajax({
        type: 'POST', url: 'plugins/Palazzetti/core/ajax/Palazzetti.ajax.php', dataType: 'json',
        data: {action: 'getHiddenParam', id: id, hidden_param_id: paramId},
        error: function(request, status, error) {
            if (paramId === undefined) setHiddenButtonState(hiddenRefreshButton, false, '', '{{Rafraîchir les paramètres}}');
            handleAjaxError(request, status, error);
        },
        success: function(data) {
            if (paramId === undefined) setHiddenButtonState(hiddenRefreshButton, false, '', '{{Rafraîchir les paramètres}}');
            if (data.state !== 'ok' || !data.result || (!data.result.HPAR && !data.result.DATA)) {
                jeedomUtils.showAlert({message: 'Code: ' + (data.code || '') + ' - Result: ' + JSON.stringify(data.result), level: 'danger'});
                return;
            }
            if (Array.isArray(data.result.HPAR)) {
                data.result.HPAR.forEach(function(value, index) {
                    const input = hiddenParameterTable.querySelector('tr[data-param_id="' + index + '"] .paramAttr[data-l1key="value"]');
                    if (input) { input.value = value; input.dataset.value = value; }
                });
                return;
            }
            const key = 'HPAR' + paramId;
            const input = hiddenParameterTable.querySelector('tr[data-param_id="' + paramId + '"] .paramAttr[data-l1key="value"]');
            if (input && Object.prototype.hasOwnProperty.call(data.result.DATA || {}, key)) {
                input.value = data.result.DATA[key];
                input.dataset.value = data.result.DATA[key];
                input.style.fontWeight = 'bold';
                input.style.fontStyle = 'oblique';
            }
        }
    });
}

hiddenRefreshButton?.addEventListener('click', function() { getHiddenParamValue(idHParamPala); });
hiddenParameterTable?.addEventListener('click', function(event) {
    const button = event.target.closest('.paramAction[data-action]');
    if (!button) return;
    const row = button.closest('tr');
    const id = row.querySelector('.paramAttr[data-l1key="id"]').value;
    const input = row.querySelector('.paramAttr[data-l1key="value"]');
    if (button.dataset.action === 'update') {
        getHiddenParamValue(idHParamPala, id);
        return;
    }
    if (button.dataset.action !== 'modify') return;
    if (input.value === '') {
        jeedomUtils.showAlert({message: '{{Veuillez entrer une valeur}}', level: 'danger'});
        return;
    }
    const description = row.querySelector('.paramAttr[data-l1key="description"]').textContent;
    const oldValue = input.dataset.value || '';
    jeeDialog.confirm('{{Êtes-vous sûr de vouloir modifier le paramètre}} ' + id + ' : ' + description + ' ? {{De}} ' + oldValue + ' {{à}} ' + input.value, function(confirmed) {
        if (!confirmed) return;
        domUtils.ajax({
            type: 'POST', url: 'plugins/Palazzetti/core/ajax/Palazzetti.ajax.php', dataType: 'json',
            data: {action: 'setHiddenParam', id: idHParamPala, hidden_param_id: id, hidden_param_value: input.value},
            error: function(request, status, error) { handleAjaxError(request, status, error); },
            success: function(data) {
                if (data.state === 'ok' && data.result?.INFO?.RSP === 'OK') {
                    input.dataset.value = input.value;
                    jeedomUtils.showAlert({message: '{{Valeur}} ' + input.value + ' {{envoyée avec succès dans le paramètre}} ' + id, level: 'success'});
                } else {
                    jeedomUtils.showAlert({message: 'Result: ' + JSON.stringify(data.result), level: 'danger'});
                }
            }
        });
    });
});

if (eqLogic.configuration.commentaire_caches && Array.isArray(eqLogic.configuration.commentaire_caches)) {
    const comments = eqLogic.configuration.commentaire_caches[0] || {};
    Object.keys(comments).forEach(function(id) {
        const input = hiddenParameterTable.querySelector('.eqLogicAttr[data-l2key="commentaire_caches"][data-l3key="' + id + '"]');
        if (input) input.value = comments[id];
    });
}

hiddenSaveButton?.addEventListener('click', function() {
    setHiddenButtonState(hiddenSaveButton, true, '{{Sauvegarde en cours}}', '{{Sauvegarder les commentaires}}');
    eqLogic.configuration = eqLogic.configuration || {};
    const values = document.getElementById('paramtab').getJeeValues('.eqLogicAttr')[0];
    eqLogic.configuration.commentaire_caches = [values.configuration.commentaire_caches || {}];
    jeedom.eqLogic.save({
        type: 'Palazzetti', eqLogics: [eqLogic],
        error: function(error) {
            setHiddenButtonState(hiddenSaveButton, false, '', '{{Sauvegarder les commentaires}}');
            jeedomUtils.showAlert({message: error.message, level: 'danger'});
        },
        success: function() {
            setHiddenButtonState(hiddenSaveButton, false, '', '{{Sauvegarder les commentaires}}');
            jeedomUtils.showAlert({message: '{{Commentaires sauvegardés avec succès}}', level: 'success'});
        }
    });
});

jeeFrontEnd.modifyWithoutSave = false;
getHiddenParamValue(idHParamPala);
