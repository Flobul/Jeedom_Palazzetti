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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

    ajax::init();

	if (init('action') == 'getParam') {
		$eqLogic = Palazzetti::byId(init('id'));
		if (!is_object($eqLogic)) {
			throw new Exception(__('Equipement inconnu : ', __FILE__) . init('id'), 9999);
		}
        if (init('param_id') != '') {
            if (init('param_value') != '') {
                $result = $eqLogic->makeRequest('GET+PARM+' . init('param_id') . '+' . init('param_value'),3);
            } else {
                $result = $eqLogic->makeRequest('GET+PARM+' . init('param_id'),3);
            }
        } else {
            $result = $eqLogic->makeRequest('BKP+PARM+JSON',10);
        }
        ajax::success($result);
    }


	if (init('action') == 'setParam') {
		$eqLogic = Palazzetti::byId(init('id'));
		if (!is_object($eqLogic)) {
			throw new Exception(__('Equipement inconnu : ', __FILE__) . init('id'), 9999);
		}
        if (init('hidden_param_id') != '') {
            if (init('hidden_param_value') != '') {
                $result = $eqLogic->makeRequest('SET+PARM+' . init('hidden_param_id') . '+' . init('hidden_param_value'),3);
            }
        }
        ajax::success($result);
    }

	if (init('action') == 'getHiddenParam') {
		$eqLogic = Palazzetti::byId(init('id'));
		if (!is_object($eqLogic)) {
			throw new Exception(__('Equipement inconnu : ', __FILE__) . init('id'), 9999);
		}
        if (init('hidden_param_id') != '') {
            if (init('hidden_param_value') != '') {
                $result = $eqLogic->makeRequest('GET+HPAR+' . init('hidden_param_id') . '+' . init('hidden_param_value'),3);
            } else {
                $result = $eqLogic->makeRequest('GET+HPAR+' . init('hidden_param_id'),3);
            }
        } else {
            $result = $eqLogic->makeRequest('BKP+HPAR+JSON',10);
        }
        ajax::success($result);
    }

	if (init('action') == 'setHiddenParam') {
		$eqLogic = Palazzetti::byId(init('id'));
		if (!is_object($eqLogic)) {
			throw new Exception(__('Equipement inconnu : ', __FILE__) . init('id'), 9999);
		}
        if (init('hidden_param_id') != '') {
            if (init('hidden_param_value') != '') {
                $result = $eqLogic->makeRequest('SET+HPAR+' . init('hidden_param_id') . '+' . init('hidden_param_value'),3);
            }
        }
        ajax::success($result);
    }

    throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayExeption($e), $e->getCode());
}
