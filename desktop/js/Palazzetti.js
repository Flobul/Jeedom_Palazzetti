/* 
 *
 */

$("#table_cmd").sortable({
    axis: "y",
    cursor: "move",
    items: ".cmd",
    placeholder: "ui-state-highlight",
    tolerance: "intersect",
    forcePlaceholderSize: true
});

function printEqLogic(_eqLogic) {
    $('#buttonParam').hide();
    for (var i in _eqLogic.cmd) {
        if (_eqLogic.cmd[i].logicalId == 'IName') {
            jeedom.cmd.execute({
                id: _eqLogic.cmd[i].id,
                async: false,
                cache: 1,
                notify: false,
                success: function(result) {
                    if (result == 'WPalaControl') {
                        $('#buttonParam').show();
                    }
                }
            });
        }
    }
}

$('body').delegate('.cmdAction[data-action=parametres]', 'click', function () {
  $('#md_modal2').dialog({title: "{{Paramètres du poêle}}"});
  $('#md_modal2').load('index.php?v=d&plugin=Palazzetti&modal=parametres&id=' + $('.eqLogicAttr[data-l1key=id]').value()).dialog('open');
});

$('body').delegate('.cmdAction[data-action=hiddenParametres]', 'click', function () {
  $('#md_modal2').dialog({title: "{{Paramètres cachés du poêle}}"});
  $('#md_modal2').load('index.php?v=d&plugin=Palazzetti&modal=hidden.parametres&id=' + $('.eqLogicAttr[data-l1key=id]').value()).dialog('open');
});

function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {
            configuration: {}
        };
    }

    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td class="hidden-xs">';
    tr += '<span class="cmdAttr" data-l1key="id"></span>';
    tr += '</td>';

    tr += '<td>';
    tr += '<div class="input-group">';
    tr += '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">';
    tr += '<span class="input-group-btn"><a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a></span>';
    tr += '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>';
    tr += '</div>';
    tr += '</td>';

    tr += '<td>';
    tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
    tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
    tr += '</td>';

    tr += '<td>';
    tr += '    <input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="actionCmd" title="{{Commande}}">';
    tr += '</td>';
  
    tr += '<td>';
    tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
    tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
    tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label></span> ';
    if (init(_cmd.subType) == 'numeric') {
        tr += '<div style="margin-top:7px;">';
        tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">';
        tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">';
        tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">';
        tr += '</div>';
    }
    tr += '</td>';

    tr += '</td>';
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fas fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> Tester</a>';
    }
    tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
    tr += '</tr>';
    $('#table_cmd tbody').append(tr);
    var tr = $('#table_cmd tbody tr:last');
    jeedom.eqLogic.builSelectCmd({
        id: $(".li_eqLogic.active").attr('data-eqLogic_id'),
        filter: {
            type: 'info'
        },
        error: function (error) {
            $('#div_alert').showAlert({
                message: error.message,
                level: 'danger'
            });
        },
        success: function (result) {
            tr.setValues(_cmd, '.cmdAttr');
            jeedom.cmd.changeType(tr, init(_cmd.subType));
        }
    });
}