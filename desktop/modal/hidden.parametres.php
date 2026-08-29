<?php
	if (!isConnect('admin')) {
		throw new Exception('401 Unauthorized');
	}
	$eqLogicId = (int) init('id');
	$eqLogic = eqLogic::byId($eqLogicId);
    if (!is_object($eqLogic)) {
        throw new Exception(__('Objet non trouvé', __FILE__));
    }
    sendVarToJS('idHParamPala', $eqLogicId);
    sendVarToJS('eqLogic', utils::o2a($eqLogic));
?>
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
