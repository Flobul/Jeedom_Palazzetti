<?php
	$eqLogic = eqLogic::byId($_GET['id']);
    if (!is_object($eqLogic)) {
        throw new Exception(__('Objet non trouvé', __FILE__));
    }
    sendVarToJS('eqPalaId', $_GET['id']);
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
  .paramAttr[data-l1key=id] {
      width: 40px;
  }
  #table_param .param td:nth-child(6) {
    display: inline-flex;
  }
  .paramAttr[data-l1key=unit] {
      word-break: normal;
      width: 65px;
  }
  .paramAttr[data-l1key=value] {
      width: 100px;
  }

</style>
<div role="tabpanel" class="tab-pane" id="paramtab">
    <span class="input-group pull-right">
        <a class="btn btn-success btn-sm paramAction roundedLeft" data-action="saveComments"><i class="fas fa-save"></i> {{Sauvegarder les commentaires}}</a>
        <a class="btn btn-success btn-sm paramAction roundedRight" data-action="refresh"><i class="fas fa-sync"></i> {{Rafraîchir les paramètres}}</a>
    </span>
    <table id="table_param" class="table table-bordered table-condensed">
        <span class="label label-primary">{{Cette page permet de voir et modifier les paramètres statiques du poêle.}}<br/>{{Il s'agit des informations récupérées par la commande GET+PARM.}}<br/>{{Attention à ce que vous modifiez, vous risquez de changer des paramètres usine et rendre votre poêle inutilisable.}}
        </span>
        <thead>
            <tr>
                <th style="width:100px">{{ID}}</th>
                <th>{{Description}}</th>
                <th>{{Commentaire}}</th>
                <th>{{Valeur}}</th>
                <th>{{Unité}}</th>
                <th>{{Action}}</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
<?php include_file('desktop', 'parametres', 'js', 'Palazzetti'); ?>
