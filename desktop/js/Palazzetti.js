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

	const palazzettiCommandBody = document.querySelector('#table_cmd tbody');
	if (palazzettiCommandBody && typeof Sortable !== 'undefined') {
	    new Sortable(palazzettiCommandBody, {draggable: '.cmd', animation: 150});
	}

	const palazzettiHealthButton = document.getElementById('bt_healthPalazzetti');
	palazzettiHealthButton?.addEventListener('click', function() {
	    jeeDialog.dialog({
	        id: 'md_palazzettiHealth',
	        title: '{{Santé Palazzetti}}',
	        contentUrl: 'index.php?v=d&plugin=Palazzetti&modal=health'
	    });
	});

	const palazzettiHeatingHistoryButton = document.getElementById('bt_heatingHistoryPalazzetti');
	palazzettiHeatingHistoryButton?.addEventListener('click', function() {
	    jeeDialog.dialog({
	        id: 'md_palazzettiHeatingHistory',
	        title: '{{Historique de chauffe Palazzetti}}',
	        size: 'large',
	        contentUrl: 'index.php?v=d&plugin=Palazzetti&modal=heating.history'
	    });
	});
	document.body.addEventListener('click', function(event) {
	    if (!event.target.closest('#bt_refreshPalazzettiHealth')) return;
	    jeeDialog.dialog({
	        id: 'md_palazzettiHealth',
	        title: '{{Santé Palazzetti}}',
	        contentUrl: 'index.php?v=d&plugin=Palazzetti&modal=health'
	    });
	});

	const palazzettiDiscoveryButton = document.getElementById('bt_discoverPalazzetti');
	function setPalazzettiDiscoveryBusy(busy) {
	    if (!palazzettiDiscoveryButton) return;
	    palazzettiDiscoveryButton.dataset.busy = busy ? '1' : '0';
	    palazzettiDiscoveryButton.classList.toggle('disabled', busy);
	    palazzettiDiscoveryButton.innerHTML = busy
	        ? '<i class="fas fa-broadcast-tower fa-spin"></i><br><span>{{Découverte en cours}}</span>'
	        : '<i class="fas fa-broadcast-tower"></i><br><span>{{Découvrir}}</span>';
	}

	function palazzettiEscapeHtml(value) {
	    const element = document.createElement('span');
	    element.textContent = value == null ? '' : String(value);
	    return element.innerHTML;
	}

	function palazzettiDiscoveryDetails(result) {
	    const devices = Array.isArray(result.devices) ? result.devices : [];
	    const rows = devices.map(function(device) {
	        const existing = device.id
	            ? '<strong>' + palazzettiEscapeHtml(device.existingName) + '</strong><br><code>'
	                + palazzettiEscapeHtml(device.existingIp) + '</code> — '
	                + (device.existingEnabled ? '{{actif}}' : '{{désactivé}}')
	            : '<span class="text-muted">{{Aucun équipement correspondant}}</span>';
	        const versions = device.versions
	            ? '<br><small class="text-muted">' + palazzettiEscapeHtml(device.versions) + '</small>'
	            : '';
	        return '<tr>'
	            + '<td><strong>' + palazzettiEscapeHtml(device.name) + '</strong>' + versions + '</td>'
	            + '<td>' + palazzettiEscapeHtml(device.gatewayType) + '</td>'
	            + '<td><code>' + palazzettiEscapeHtml(device.ip) + '</code></td>'
	            + '<td><code>' + palazzettiEscapeHtml(device.mac || '—') + '</code></td>'
	            + '<td>' + palazzettiEscapeHtml(device.serial || '—') + '</td>'
	            + '<td>' + palazzettiEscapeHtml(device.model || '—') + '</td>'
	            + '<td>' + (device.isApplianceConnected ? '{{Connecté}}' : '{{Non connecté}}') + '</td>'
	            + '<td>' + existing + '</td>'
	            + '</tr>';
	    }).join('');

	    return '<p>' + result.found + ' {{passerelle(s) trouvée(s)}}. '
	        + '{{Choisissez comment appliquer la découverte. Les appareils et équipements concernés sont détaillés ci-dessous.}}'
	        + '</p><div class="table-responsive"><table class="table table-condensed table-bordered table-striped">'
	        + '<thead><tr><th>{{Appareil découvert}}</th><th>{{Passerelle}}</th><th>{{Adresse IP}}</th>'
	        + '<th>{{MAC}}</th><th>{{Numéro de série}}</th><th>{{Modèle}}</th><th>{{Poêle}}</th><th>{{Équipement existant}}</th></tr></thead>'
	        + '<tbody>' + rows + '</tbody></table></div>'
	        + '<div class="alert alert-info"><strong>{{Écraser l’équipement existant}}</strong> : '
	        + '{{conserve son nom, son objet, sa visibilité, ses commandes et son historique, puis actualise son adresse et les informations détectées.}}<br>'
	        + '<strong>{{Créer un nouvel équipement et désactiver l’ancien}}</strong> : '
	        + '{{conserve l’ancien équipement désactivé et crée son remplaçant avec le même état et le même objet. Les appareils sans correspondance sont créés désactivés.}}'
	        + '</div>';
	}

	function showPalazzettiDiscoveryDialog(result) {
	    const devices = Array.isArray(result.devices) ? result.devices : [];
	    const hasExistingEquipment = devices.some(function(device) { return Number(device.id) > 0; });
	    const buttons = {
	        cancel: {
	            label: '{{Annuler}}',
	            className: 'btn-default',
	            callback: {click: function() { jeeDialog.get('#md_palazzettiDiscovery').close(); }}
	        }
	    };
	    if (hasExistingEquipment) {
	        buttons.replace = {
	            label: '{{Créer un nouvel équipement et désactiver l’ancien}}',
	            className: 'btn-warning',
	            callback: {click: function() {
	                jeeDialog.get('#md_palazzettiDiscovery').close();
	                discoverPalazzetti('replace');
	            }}
	        };
	    }
	    buttons.overwrite = {
	        label: hasExistingEquipment ? '{{Écraser l’équipement existant}}' : '{{Créer les nouveaux équipements}}',
	        className: 'btn-success',
	        callback: {click: function() {
	            jeeDialog.get('#md_palazzettiDiscovery').close();
	            discoverPalazzetti('overwrite');
	        }}
	    };

	    jeeDialog.dialog({
	        id: 'md_palazzettiDiscovery',
	        title: '{{Découverte Palazzetti}}',
	        message: palazzettiDiscoveryDetails(result),
	        size: 'large',
	        buttons: buttons
	    });
	}

	function discoverPalazzetti(mode) {
	    mode = mode || 'preview';
	    setPalazzettiDiscoveryBusy(true);
	    if (mode === 'preview') {
	        jeedomUtils.showAlert({message: '{{Recherche des passerelles Palazzetti sur le réseau local…}}', level: 'info'});
	    }

	    domUtils.ajax({
	        type: 'POST',
	        url: 'plugins/Palazzetti/core/ajax/Palazzetti.ajax.php',
	        dataType: 'json',
	        timeout: 60000,
	        data: {action: 'discover', mode: mode},
	        error: function(request, status, error) {
	            setPalazzettiDiscoveryBusy(false);
	            handleAjaxError(request, status, error);
	        },
	        success: function(data) {
	            setPalazzettiDiscoveryBusy(false);
	            if (data.state !== 'ok') {
	                jeedomUtils.showAlert({message: palazzettiEscapeHtml(data.result || '{{La découverte a échoué.}}'), level: 'danger'});
	                return;
	            }

	            const result = data.result || {};
	            if (!result.found) {
	                jeedomUtils.showAlert({
	                    message: '{{Aucune passerelle trouvée. Vérifiez que Jeedom et la passerelle sont sur le même réseau local et que les broadcasts UDP sont autorisés.}}',
	                    level: 'warning'
	                });
	                return;
	            }

	            if (mode === 'preview') {
	                showPalazzettiDiscoveryDialog(result);
	                return;
	            }

	            jeedomUtils.showAlert({
	                message: result.found + ' {{passerelle(s) traitée(s)}} : '
	                    + result.created + ' {{créée(s)}}, '
	                    + result.updated + ' {{mise(s) à jour}}, '
	                    + (result.replaced || 0) + ' {{remplacée(s)}} et '
	                    + result.unchanged + ' {{déjà à jour}}.',
	                level: 'success'
	            });
	            if (result.created || result.updated || result.replaced) {
	                window.setTimeout(function() { window.location.reload(); }, 1500);
	            }
	        }
	    });
	}

	palazzettiDiscoveryButton?.addEventListener('click', function() {
	    if (palazzettiDiscoveryButton.dataset.busy === '1') return;
	    discoverPalazzetti('preview');
	});

function printEqLogic(_eqLogic) {
	    const parameterButton = document.getElementById('buttonParam');
	    const palaControl = document.getElementById('showWPalaControl');
	    if (parameterButton) parameterButton.style.display = 'none';
	    if ([true, 1, '1', 'true'].includes(_eqLogic.configuration.isWirelessPalaControl)) {
	        if (parameterButton) parameterButton.style.display = '';
	        if (palaControl) palaControl.style.display = '';
	    }
	}

	document.body.addEventListener('click', function(event) {
	  const button = event.target.closest('.cmdAction[data-action="parametres"], .cmdAction[data-action="hiddenParametres"]');
	  if (!button) return;
	  const hidden = button.dataset.action === 'hiddenParametres';
	  const equipmentId = document.querySelector('.eqLogicAttr[data-l1key="id"]').jeeValue();
	  jeeDialog.dialog({
	    id: hidden ? 'md_palazzettiHiddenParameters' : 'md_palazzettiParameters',
	    title: hidden ? '{{Paramètres cachés du poêle}}' : '{{Paramètres du poêle}}',
	    contentUrl: 'index.php?v=d&plugin=Palazzetti&modal=' + (hidden ? 'hidden.parametres' : 'parametres') + '&id=' + encodeURIComponent(equipmentId)
	  });
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
    tr += '<span class="cmdAttr" data-l1key="logicalId" style="display:none;"></span>';
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
        tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="{{Unité}}" title="{{Unité}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">';
        tr += '</div>';
    }
    if (['select', 'slider', 'color', 'other'].includes(init(_cmd.subType)) || init(_cmd.configuration.updateLogicalId) != '') {
        tr += '    <select class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="updateLogicalId" title="{{Commande d\'information à mettre à jour}}">';
        tr += '        <option value="">{{Aucune}}</option>';
        tr += '    </select>';
    }
    tr += '</td>';
    tr += '<td>';
    tr += '<span class="cmdAttr" data-l1key="htmlstate"><span class="cmdTableState tooltipstered" data-cmd_id="' + init(_cmd.id) + '"> <span></span></span></span>';
    tr += '</td>';
    tr += '<td style="min-width:100px;width:150px;">';
    tr += '<div class="input-group">';
    if (is_numeric(_cmd.id) && _cmd.id != '') {
        tr += '<a class="btn btn-default btn-xs cmdAction roundedLeft" data-action="configure" title="{{Configuration de la commande}} ' + _cmd.type + '"><i class="fa fa-cogs"></i></a>';
        tr += '<a class="btn btn-success btn-xs cmdAction" data-action="test" title="{{Tester}}"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
    tr += '<a class="btn btn-danger btn-xs cmdAction roundedRight" data-action="remove" title="{{Suppression de la commande}} ' + _cmd.type + '"><i class="fas fa-minus-circle"></i></a>';
    tr += '</div>';
    tr += '</td>';
    tr += '</tr>';
	    if (!palazzettiCommandBody) return;
	    palazzettiCommandBody.insertAdjacentHTML('beforeend', tr);
	    const newRow = palazzettiCommandBody.lastElementChild;
	    buildPalaSelectCmd({
	        id: document.querySelector('.eqLogicAttr[data-l1key="id"]').jeeValue(),
	        filter: {
            type: 'info'
        },
        error: function (error) {
	            jeedomUtils.showAlert({
                message: palazzettiEscapeHtml(error.message || '{{Impossible de charger la liste des commandes.}}'),
                level: 'danger'
            });
        },
        success: function (result) {
	            newRow.querySelector('.cmdAttr[data-l1key="value"]')?.insertAdjacentHTML('beforeend', result);
	            newRow.querySelector('.cmdAttr[data-l1key="configuration"][data-l2key="updateLogicalId"]')?.insertAdjacentHTML('beforeend', result);
	            newRow.setJeeValues(_cmd, '.cmdAttr');
	            jeedom.cmd.changeType(newRow, init(_cmd.subType));
        }
    });
}

buildPalaSelectCmd = function(_params) {
  if (!isset(_params.filter)) {
    _params.filter = {}
  }
  jeedom.eqLogic.getCmd({
    id: _params.id,
	    success: function(cmds) {
      var result = ''
      for (var i in cmds) {
        if ((init(_params.filter.type, 'all') == 'all' || cmds[i].type == _params.filter.type) &&
          (init(_params.filter.subType, 'all') == 'all' || cmds[i].subType == _params.filter.subType) &&
          (init(_params.filter.isHistorized, 'all') == 'all' || cmds[i].isHistorized == _params.filter.isHistorized)
        ) {
	          const option = document.createElement('option');
	          option.value = cmds[i].logicalId;
	          option.dataset.type = cmds[i].type;
	          option.dataset.subtype = cmds[i].subType;
	          option.textContent = cmds[i].name;
	          result += option.outerHTML;
        }
      }
      if ('function' == typeof (_params.success)) {
        _params.success(result)
      }
    }
  })
}

saveEqLogic = function(_eqLogic) {
    _eqLogic.cmd.forEach(cmd => {
      if (cmd.logicalId == '') {
	    cmd.logicalId = cmd.configuration.actionCmd;
      }
    });
	return _eqLogic;
}
