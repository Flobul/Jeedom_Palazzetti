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
	$eqLogicId = (int) init('id');
	$eqLogic = eqLogic::byId($eqLogicId);
    if (!is_object($eqLogic) || $eqLogic->getEqType_name() !== 'Palazzetti' || !($eqLogic instanceof Palazzetti)) {
        throw new Exception(__('Objet non trouvé', __FILE__));
    }
	$eqLogicJson = json_encode(
		utils::o2a($eqLogic),
		JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
	);
	if (!is_string($eqLogicJson)) {
		$eqLogicJson = '{}';
	}
	$parameterWritesEnabled = (int) config::byKey('allow_parameter_writes', 'Palazzetti', 0) === 1;
?>
<script>
var idHParamPala = <?php echo $eqLogicId; ?>;
var eqLogic = <?php echo $eqLogicJson; ?>;
var palazzettiParameterWritesEnabled = <?php echo $parameterWritesEnabled ? 'true' : 'false'; ?>;
</script>
<style>
  @media (max-width: 48em) {
    #table_param .param td:nth-child(3),
    #table_param > thead > tr > th:nth-child(3) {
        display: none;
    }
    #table_param .hideMe {
        display: none;
    }
  }
</style>
<div role="tabpanel" class="tab-pane" id="paramtab">
    <?php if ((int) config::byKey('allow_parameter_writes', 'Palazzetti', 0) !== 1) { ?>
        <div class="alert alert-info"><i class="fas fa-lock"></i> {{Consultation seule. Les écritures bas niveau doivent être activées explicitement dans la configuration du plugin.}}</div>
    <?php } ?>
    <span class="input-group pull-right">
        <a class="btn btn-success btn-sm paramAction roundedLeft" data-action="saveComments"><i class="fas fa-save"></i> {{Sauvegarder les commentaires}}</a>
        <a class="btn btn-success btn-sm paramAction roundedRight" data-action="refresh"><i class="fas fa-sync"></i> {{Rafraîchir les paramètres}}</a>
    </span>
    <table id="table_param" class="table table-bordered table-condensed">
        <span class="label label-primary">{{Cette page permet de voir et modifier les paramètres statiques du poêle.}}<br/>{{Il s'agit des informations récupérées par la commande GET+HPAR.}}<br/>{{Attention à ce que vous modifiez, vous risquez de changer des paramètres usine et rendre votre poêle inutilisable.}}
        </span>
        <thead>
            <tr>
                <th style="width:100px">{{ID}}</th>
                <th>{{Description}}</th>
                <th>{{Commentaire}}</th>
                <th>{{Valeur}}</th>
                <th>{{Action}}</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
<?php include_file('desktop', 'hidden.parametres', 'js', 'Palazzetti'); ?>
