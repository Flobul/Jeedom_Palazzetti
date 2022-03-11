
var palaParamComment = {
    51: 'only in config 2,5 (Room temp)',
    53: '0: OFF 0x0C : ECO',
    76: 'config 1-5',
    84: 'only in config 1,3,4 (Water temp)'
};
var palaParam = {
    0: '{{Durée phase d\'allumage (Fuel Ignition)}}',
    1: 'Durée phase FireCheck',
    /*2: '',*/
    3: 'Pause du moteur d\'allimentation en phase HeatUp',
    4: 'Durée travail de fonctionnement de l\'écluse en phase HeatUp',
    5: 'Pause du moteur d\'alimentation en phase Fuel Ignition',
    6: 'Durée travail de fonctionnement de l\'écluse en phase Fuel Ignition',
    7: 'Pause de l\'écluse en phase Fuel Ignition',
    8: 'Durée fonctionnement de l\'écluse en phase Fire Check',
    /*9: '',*/
    10: 'Durée fonctionnement de l\'écluse en puissance 1',
    /*11: '',*/
    /*12: '',*/
    /*13: '',*/
    /*14: '',*/
    /*15: '',*/
    /*16: '',*/
    /*17: '',*/
    18: 'Durée fonctionnement de l\'écluse en puissance 5',
    19: 'Débit du ventilateur d\'extraction de fumées en StopFire',
    20: 'Débit du ventilateur d\'extraction de fumées en TestFire',
    21: 'Débit du ventilateur d\'extraction de fumées en HeatUp',
    22: 'Débit du ventilateur d\'extraction de fumées en Fuel Ignition',
    23: 'Débit du ventilateur d\'extraction de fumées en Fire Check',
    24: 'Débit du ventilateur d\'extraction de fumées en puissance 1',
    25: 'Débit du ventilateur d\'extraction de fumées en puissance 5',
    /*26: '',*/
    /*27: '',*/
    /*28: '',*/
    29: 'Valeur ventilateur en phase TestFire',
    30: 'Valeur ventilateur en phase StopFire',
    31: 'Valeur ventilateur en phase HeatUp',
    32: 'Valeur ventilateur en phase Fuel Ignition',
    33: 'Valeur ventilateur en phase FireCheck',
    34: 'Valeur ventilateur en puissance 1',
    35: 'Valeur ventilateur en puissance 2',
    36: 'Valeur ventilateur en puissance 3',
    37: 'Valeur ventilateur en puissance 4',
    38: 'Valeur ventilateur en puissance 5',
    39: 'Valeur ventilateur en Over Boost',
    40: 'Vitesse de l\'extracteur des fumées en StopFire (x11,74)',
    41: 'Vitesse de l\'extracteur des fumées en TestFire (x11,74)',
    42: 'Vitesse de l\'extracteur des fumées en HeatUp (x11,74)',
    43: 'Vitesse de l\'extracteur des fumées en Fuel Ignition (x11,74)',
    44: 'Vitesse de l\'extracteur des fumées en Fire Check (x11,74)',
    45: 'Vitesse de l\'extracteur des fumées en puissance 1 (x11,74)',
    /*46: '',*/
    /*47: '',*/
    /*48: '',*/
    49: 'Vitesse de l\'extracteur des fumées en puissance 5 (x11,74)',
    50: 'Delta T pour sortir du CoolFluid',
    51: 'Water storage set point temperature',
    52: 'Water Modulation Temp setup menu [6]',
    53: 'Delta T Cool Fluid',
    54: 'Température des fumées pour la sortie de la phase FireCheck',
    55: 'MAX température des fumées pour la modulation',
    56: 'Température des fumées pour la sortie de la phase StopFire',
    57: 'Température des fumées MAX',
    58: 'Température pour l\'arrêt du ventilateur d\'air',
    59: 'MIN température des fumées pendant la phase de travail',
    60: 'Temps entre 2 cycles de nettoyage',
    61: 'Temps total pour la cycle de nettoyage',
    62: 'Débit du ventilateur des fumées en phase de nettoyage 1/%',
    /*63: '',*/
    64: 'Valeur MIN de pression',
    65: 'Retard valeur MIN de pression',
    66: 'Type de pellet par défaut',
    67: 'Average Temp water/bwater for anti-cond/work pump phase',
    68: 'Température moyenne de l\'eau de la pompe en fonctionnement',
    69: 'delta Temp water/bwater MAX for pump modulation',
    70: 'Durée phase de HeatUp (*5)',
    71: 'Durée test (gradient) (*5)',
    72: 'Delta T (gradient)',
    /*73: '',*/
    /*74: '',*/
    /*75: '',*/
    76: 'Installation configuration',
    /*77: '',*/
    /*78: '',*/
    79: 'Variation (%) sur la pression souhaitée',
    80: 'Période de vérification obtention pression souhaitée',
    /*81: '',*/
    82: 'Pression MIN au démarrage',
    83: 'Délai avant la vérification du PAR82',
    84: 'Water storage set point temperature',
    85: 'Delta T MIN entre départ et retour d\'eau pour démarrage de la pompe',
    /*86: '',*/
    /*87: '',*/
    /*88: '',*/
    /*89: '',*/
    90: 'Timer overrun pompe et FAN1',
    /*91: '',*/
    92: 'Type de pellets',
    /*93: '',*/
    94: 'Jours avant avis de faire la manutention',
    /*95: '',*/
    /*96: '',*/
    /*97: '',*/
    98: 'Leveltronic niveau PLEIN',
    99: 'Leveltronic niveau BAS',
    100: 'Leveltronic niveau VIDE',
    101: 'Blow out time',
    102: 'Frost protection temperature'
};
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

$(function() {
    for (var i in palaParam) {
        addParamToTable({id:i, description: palaParam[i]})
    }
    getParamValue(id);
    for (var j in eqLogic.configuration.commentaire[0]) {
        $('#table_param tbody tr').find('.eqLogicAttr[data-l1key=configuration][data-l2key=commentaire][data-l3key=' + j + ']').value(eqLogic.configuration.commentaire[0][j])
    }
    modifyWithoutSave = false;
    $.hideLoading();
});

function getParamValue(_id) {
    $('.paramAction[data-action=refresh]').removeClass('btn-success').addClass('btn-warning').addClass('disabled');
    $('.paramAction[data-action=refresh]').html('<i class="fas fa-sync fa-spin"></i> {{Rafraîchissement en cours}}');
    $.ajax({
        type: "POST",
        url: "plugins/Palazzetti/core/ajax/Palazzetti.ajax.php",
        data: {
            async: true,
            action: "getParam",
            id: _id
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error,$('#div_alert'));
        },
        success: function (data) {
            $('.paramAction[data-action=refresh]').removeClass('btn-warning').addClass('btn-success').removeClass('disabled');
            $('.paramAction[data-action=refresh]').html('<i class="fas fa-sync"></i> {{Rafraîchir les paramètres}}');
            if (data.state == 'error' || data.result.PARM.length == 0) {
			    $.fn.showAlert({message: 'Code: ' + data.code + ' - Result: ' + data.result, level: 'danger'});
                return;
            }
            for (var i = 0; i < data.result.PARM.length; ++i) {
                $('#table_param tbody tr[data-param_id="' + i + '"]').find('.paramAttr[data-l1key="value"]').val(data.result.PARM[i]);
                $('#table_param tbody tr[data-param_id="' + i + '"]').find('.paramAttr[data-l1key="value"]').data('value',data.result.PARM[i]);

            }
        }
    });
}

$('.paramAction[data-action=refresh]').off('click').on('click', function () {
    getParamValue($('.eqLogicAttr[data-l1key=id]').value());
});

$('.paramAction[data-action=getStaticComment]').off('click').on('click', function () {
    $('#table_param tbody tr').setValues({ configuration: { commentaire: palaParamComment } }, '.eqLogicAttr');
    $('.paramAction[data-action=getStaticComment]').removeClass('btn-info').addClass('btn-success').addClass('disabled');
    $('.paramAction[data-action=getStaticComment]').html('<i class="fas fa-check-double"></i> {{Commentaires récupérés}}');
});

$("#table_param").delegate('.paramAction[data-action=update]', 'click', function() {
    var el = $(this)
    el.removeClass('btn-success').addClass('btn-warning').addClass('disabled');
    el.html('<i class="fas fa-sync fa-spin"></i>');
    var id = el.closest('tr').find('.paramAttr[data-l1key=id]').value();
    var val = el.closest('tr').find('.paramAttr[data-l1key=value]').value();
    var oldVal = el.closest('tr').find('.paramAttr[data-l1key=value]').data('value');
    $.hideLoading();
    $.ajax({
        type: "POST",
        url: "plugins/Palazzetti/core/ajax/Palazzetti.ajax.php",
        data: {
            async: false,
            action: "getParam",
            id: $('.eqLogicAttr[data-l1key=id]').value(),
            param_id: id
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error,$('#div_alert'));
        },
        success: function (data) {
            if (data.state == 'error') {
                $.hideLoading();
			    $.fn.showAlert({message: 'Code: ' + data.code + ' - Result: ' + data.result, level: 'danger'});
                return;
            }
            if (data.result.INFO.RSP != 'OK') {
                $.hideLoading();
			    $.fn.showAlert({message: 'Result: ' + data.result.INFO.RSP, level: 'danger'});
                return;
            }
            if (data.result.DATA.PAR && val != data.result.DATA.PAR) {
                el.closest('tr').find('.paramAttr[data-l1key=value]').css({'font-weight': 'bold','font-style': 'oblique'});
                el.closest('tr').find('.paramAttr[data-l1key=value]').value(data.result.DATA.PAR)
                el.closest('tr').find('.paramAttr[data-l1key=value]').data('value',data.result.DATA.PAR);
            }
        }
    });
    el.removeClass('btn-warning').addClass('btn-success').removeClass('disabled');
    el.html('<i class="fas fa-sync"></i>');
});

$("#table_param").delegate('.paramAction[data-action=modify]', 'click', function() {
    var id = $(this).closest('tr').find('.paramAttr[data-l1key=id]').value();
    var description = $(this).closest('tr').find('.paramAttr[data-l1key=description]').value();
    var val = $(this).closest('tr').find('.paramAttr[data-l1key=value]').value();
    var oldVal = $(this).closest('tr').find('.paramAttr[data-l1key=value]').data('value');
    if (val != '') {
        var text = '{{Êtes-vous sûr de vouloir modifier le paramètre}} ' + id + ' : <i>' + description + '</i> ?<br/>';
        text += '{{De}} : ' + oldVal + ' {{à}} ' + val;
        bootbox.confirm(text, function(result) {
            if (result) {
                $.ajax({
                    type: "POST",
                    url: "plugins/Palazzetti/core/ajax/Palazzetti.ajax.php",
                    data: {
                        async: true,
                        action: "setParam",
                        id: $('.eqLogicAttr[data-l1key=id]').value(),
                        param_id: id,
                        param_value: val
                    },
                    dataType: 'json',
                    error: function (request, status, error) {
                        handleAjaxError(request, status, error,$('#div_alert'));
                    },
                    success: function (data) {
                        $.hideLoading();
                        if (data.state == 'error' || data.result.length == 0) {
                            $.fn.showAlert({message: 'Code: ' + data.code + ' - Result: ' + data.result, level: 'danger'});
                            return;
                        }
                        if (data.result.INFO.RSP != 'OK') {
                            $.fn.showAlert({message: 'Result: ' + data.result.INFO.RSP, level: 'danger'});
                            return;
                        }
                        if (data.result.DATA) {
                            if (data.result.DATA['PAR'+id]) {
                                if (data.result.DATA['PAR'+id] == val) {
                                    $.fn.showAlert({message: '{{Valeur}} ' + val + ' {{envoyée avec succès dans le paramètre}} ' + id, level: 'success'});
                                    return;
                                }
                            }
                        }
                        $.fn.showAlert({message: 'Result: ' + data.result, level: 'danger'});
                    }
                });
            }
        })
    } else {
        $.fn.showAlert({
            message: '{{Veuillez entrer une valeur}}',
            level: 'danger'
        })
    }
});

function saveEqLogic(_eqLogic) {
    if (!isset(_eqLogic.configuration)) {
        _eqLogic.configuration = {};
    }
    _eqLogic.configuration.commentaire = [];
    var eqLogic = $('#paramtab').getValues('.eqLogicAttr');
    eqLogic = eqLogic[0];
    _eqLogic.configuration.commentaire.push(eqLogic.configuration.commentaire);
    return _eqLogic;
}

$('.paramAction[data-action=saveComments]').off('click').on('click', function () {
    $('.eqLogicAction[data-action=save]').click();
});

function addParamToTable(_param) {
    var tr = '<tr class="param" data-param_id="' + init(_param.id) + '">'
    tr += '<td>'
    tr += '    <input class="paramAttr form-control input-sm roundedLeft" disabled data-l1key="id" placeholder="{{Numéro du paramètre}}" style="width:100px">'
    tr += '</td>'

    tr += '<td>'
    tr += '    <span class="paramAttr roundedLeft" disabled data-l1key="description" title="{{Description du paramètre}}"></span>'
    tr += '</td>'

    tr += '<td>'
    tr += '    <input class="eqLogicAttr form-control input-sm" data-l1key="configuration" data-l2key="commentaire" data-l3key=' + init(_param.id) + '>'
    tr += '</td>'

    tr += '<td>'
    tr += '    <input class="paramAttr form-control input-sm roundedLeft" data-l1key="value" title="{{Valeur du paramètre}}" placeholder="{{Valeur du paramètre}}" style="width:100px">'
    tr += '</td>'

    tr += '<td>'
    tr += '    <a class="btn btn-success btn-xs paramAction" data-action="update" title="{{Rafraîchir le paramètre}}"><i class="fas fa-sync"></i> </a>';
    tr += '    <a class="btn btn-warning btn-xs paramAction" data-action="modify" title="{{Modifier le paramètre}}"><i class="fas fa-rss"></i> {{Modifier}}</a>';
    tr += '</td>'
    tr += '</tr>';

    $('#table_param tbody').append(tr);
    $('#table_param tbody tr:last').setValues(_param, '.paramAttr');
}