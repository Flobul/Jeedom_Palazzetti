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

/**
 * Charge et valide un équipement Palazzetti depuis un identifiant AJAX.
 *
 * @param mixed $id Identifiant brut.
 * @return Palazzetti Équipement validé.
 * @throws Exception Si l'équipement est inconnu ou d'un autre type.
 */
function palazzettiAjaxEquipment($id)
{
    $id = filter_var($id, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));
    $eqLogic = $id === false ? null : eqLogic::byId($id);
    if (!is_object($eqLogic) || $eqLogic->getEqType_name() !== 'Palazzetti' || !($eqLogic instanceof Palazzetti)) {
        throw new Exception(__('Équipement Palazzetti inconnu.', __FILE__), 9999);
    }
    return $eqLogic;
}

/**
 * Indique si les écritures expertes PARM/HPAR sont autorisées.
 *
 * @return bool État de l'option de sécurité.
 */
function palazzettiAjaxParameterWritesEnabled()
{
    return (int) config::byKey('allow_parameter_writes', 'Palazzetti', 0) === 1;
}

/**
 * Extrait une valeur de paramètre dans une réponse de passerelle.
 *
 * @param mixed $response Réponse JSON décodée.
 * @param string $prefix Préfixe PAR ou HPAR.
 * @param int $id Identifiant du paramètre.
 * @return string Valeur trouvée ou point d'interrogation.
 */
function palazzettiAjaxParameterValueFromResponse($response, $prefix, $id)
{
    if (!is_object($response)) {
        return '?';
    }
    $key = $prefix . $id;
    if (isset($response->DATA) && is_object($response->DATA) && property_exists($response->DATA, $key)) {
        return (string) $response->DATA->{$key};
    }
    return '?';
}

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

    ajax::init();
    $action = (string) init('action');

    if ($action === 'discover') {
        $mode = trim((string) init('mode', 'preview'));
        if (!in_array($mode, array('preview', 'overwrite', 'replace'), true)) {
            throw new Exception(__('Mode de découverte invalide.', __FILE__), 9999);
        }
        ajax::success(Palazzetti::discover($mode));
    }

    if ($action === 'getHeatingHistory') {
        $eqLogic = palazzettiAjaxEquipment(init('id'));
        ajax::success($eqLogic->getHeatingHistory(init('date_start'), init('date_end')));
    }

    if ($action === 'getParam') {
        $eqLogic = palazzettiAjaxEquipment(init('id'));
        if (trim((string) init('param_id')) === '') {
            $result = $eqLogic->makeRequest('BKP+PARM+JSON', 10);
        } else {
            $id = Palazzetti::normalizeParameterId(init('param_id'), 105);
            $result = $eqLogic->makeRequest('GET+PARM+' . $id, 3);
        }
        if ($result === false) {
            throw new Exception(__('Le poêle n\'a renvoyé aucune réponse exploitable pour les paramètres.', __FILE__));
        }
        ajax::success($result);
    }

    if ($action === 'setParam') {
        if (!palazzettiAjaxParameterWritesEnabled()) {
            throw new Exception(__('Les écritures PARM/HPAR sont désactivées dans la configuration du plugin.', __FILE__));
        }
        $eqLogic = palazzettiAjaxEquipment(init('id'));
        $id = Palazzetti::normalizeParameterId(init('param_id'), 105);
        $value = Palazzetti::normalizeParameterValue(init('param_value'));
        $previous = $eqLogic->makeRequest('GET+PARM+' . $id, 3);
        $result = $eqLogic->makeRequest('SET+PARM+' . $id . '+' . $value, 3);
        if ($result === false) {
            throw new Exception(__('L\'écriture du paramètre a échoué.', __FILE__));
        }
        log::add('Palazzetti', 'warning', sprintf(
            'Modification PARM sur %s : %d, %s -> %d',
            $eqLogic->getHumanName(),
            $id,
            palazzettiAjaxParameterValueFromResponse($previous, 'PAR', $id),
            $value
        ));
        ajax::success($result);
    }

    if ($action === 'getHiddenParam') {
        $eqLogic = palazzettiAjaxEquipment(init('id'));
        if (trim((string) init('hidden_param_id')) === '') {
            $result = $eqLogic->makeRequest('BKP+HPAR+JSON', 10);
        } else {
            $id = Palazzetti::normalizeParameterId(init('hidden_param_id'), 110);
            $result = $eqLogic->makeRequest('GET+HPAR+' . $id, 3);
        }
        if ($result === false) {
            throw new Exception(__('Le poêle n\'a renvoyé aucune réponse exploitable pour les paramètres cachés.', __FILE__));
        }
        ajax::success($result);
    }

    if ($action === 'setHiddenParam') {
        if (!palazzettiAjaxParameterWritesEnabled()) {
            throw new Exception(__('Les écritures PARM/HPAR sont désactivées dans la configuration du plugin.', __FILE__));
        }
        $eqLogic = palazzettiAjaxEquipment(init('id'));
        $id = Palazzetti::normalizeParameterId(init('hidden_param_id'), 110);
        $value = Palazzetti::normalizeParameterValue(init('hidden_param_value'));
        $previous = $eqLogic->makeRequest('GET+HPAR+' . $id, 3);
        $result = $eqLogic->makeRequest('SET+HPAR+' . $id . '+' . $value, 3);
        if ($result === false) {
            throw new Exception(__('L\'écriture du paramètre caché a échoué.', __FILE__));
        }
        log::add('Palazzetti', 'warning', sprintf(
            'Modification HPAR sur %s : %d, %s -> %d',
            $eqLogic->getHumanName(),
            $id,
            palazzettiAjaxParameterValueFromResponse($previous, 'HPAR', $id),
            $value
        ));
        ajax::success($result);
    }

    throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . $action);
} catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
