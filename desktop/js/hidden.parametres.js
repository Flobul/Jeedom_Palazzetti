
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
    for (var i in palaHiddenParam) {
        addHiddenParamToTable({id:i, description: palaHiddenParam[i]})
    }
    getHiddenParamValue(id);
    for (var j in eqLogic.configuration.commentaire_caches[0]) {
        $('#table_param tbody tr').find('.eqLogicAttr[data-l1key=configuration][data-l2key=commentaire_caches][data-l3key=' + j + ']').value(eqLogic.configuration.commentaire_caches[0][j])
    }
    modifyWithoutSave = false;
    $.hideLoading();
});

function getHiddenParamValue(_id) {
    $('.paramAction[data-action=refresh]').removeClass('btn-success').addClass('btn-warning').addClass('disabled');
    $('.paramAction[data-action=refresh]').html('<i class="fas fa-sync fa-spin"></i> {{Rafraîchissement en cours}}');
    $.ajax({
        type: "POST",
        url: "plugins/Palazzetti/core/ajax/Palazzetti.ajax.php",
        data: {
            async: true,
            action: "getHiddenParam",
            id: _id
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error,$('#div_alert'));
        },
        success: function (data) {
            $('.paramAction[data-action=refresh]').removeClass('btn-warning').addClass('btn-success').removeClass('disabled');
            $('.paramAction[data-action=refresh]').html('<i class="fas fa-sync"></i> {{Rafraîchir les paramètres}}');
            if (data.state == 'error' || data.result.HPAR.length == 0) {
			    $.fn.showAlert({message: 'Code: ' + data.code + ' - Result: ' + data.result, level: 'danger'});
                return;
            }
            for (var i = 0; i < data.result.HPAR.length; ++i) {
                $('#table_param tbody tr[data-param_id="' + i + '"]').find('.paramAttr[data-l1key="value"]').val(data.result.HPAR[i]);
                $('#table_param tbody tr[data-param_id="' + i + '"]').find('.paramAttr[data-l1key="value"]').data('value',data.result.HPAR[i]);

            }
        }
    });
}

$('.paramAction[data-action=refresh]').off('click').on('click', function () {
    getHiddenParamValue($('.eqLogicAttr[data-l1key=id]').value());
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
            action: "getHiddenParam",
            id: $('.eqLogicAttr[data-l1key=id]').value(),
            hidden_param_id: id
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
            if (data.result.DATA.HPAR && val != data.result.DATA.HPAR) {
                el.closest('tr').find('.paramAttr[data-l1key=value]').css({'font-weight': 'bold','font-style': 'oblique'});
                el.closest('tr').find('.paramAttr[data-l1key=value]').value(data.result.DATA.HPAR)
                el.closest('tr').find('.paramAttr[data-l1key=value]').data('value',data.result.DATA.HPAR);
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
                        action: "setHiddenParam",
                        id: $('.eqLogicAttr[data-l1key=id]').value(),
                        hidden_param_id: id,
                        hidden_param_value: val
                    },
                    dataType: 'json',
                    error: function (request, status, error) {
                        handleAjaxError(request, status, error,$('#div_alert'));
                    },
                    success: function (data) {
                        $.hideLoading();
                        if (data.state == 'error') {
                            $.fn.showAlert({message: 'Code: ' + data.code + ' - Result: ' + data.result, level: 'danger'});
                            return;
                        }
                        if (data.result.INFO.RSP != 'OK') {
                            $.fn.showAlert({message: 'Result: ' + data.result.INFO.RSP, level: 'danger'});
                            return;
                        }
                        if (data.result.DATA) {
                            if (data.result.DATA['HPAR'+id]) {
                                if (data.result.DATA['HPAR'+id] == val) {
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
    _eqLogic.configuration.commentaire_caches = [];
    var eqLogic = $('#paramtab').getValues('.eqLogicAttr');
    eqLogic = eqLogic[0];
    _eqLogic.configuration.commentaire_caches.push(eqLogic.configuration.commentaire_caches);
    return _eqLogic;
}

$('.paramAction[data-action=saveComments]').off('click').on('click', function () {
    $('.eqLogicAction[data-action=save]').click();
});

function addHiddenParamToTable(_param) {
    var tr = '<tr class="param" data-param_id="' + init(_param.id) + '">'
    tr += '<td>'
    tr += '    <input class="paramAttr form-control input-sm roundedLeft" disabled data-l1key="id" placeholder="{{Numéro du paramètre}}" style="width:100px">'
    tr += '</td>'

    tr += '<td>'
    tr += '    <span class="paramAttr roundedLeft" disabled data-l1key="description" title="{{Description du paramètre}}"></span>'
    tr += '</td>'

    tr += '<td>'
    tr += '    <input class="eqLogicAttr form-control input-sm" data-l1key="configuration" data-l2key="commentaire_caches" data-l3key=' + init(_param.id) + '>'
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