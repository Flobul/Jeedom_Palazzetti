<?php
/*
 */
/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class Palazzetti extends eqLogic
{
    public static function cron()
    {
        $autorefresh = config::byKey('autorefresh', 'Palazzetti');
        if ($autorefresh != '') {
            try {
                $cron = new Cron\CronExpression(checkAndFixCron($autorefresh), new Cron\FieldFactory());
                if ($cron->isDue()) {
                    log::add(__CLASS__, 'debug', __("Démarrage du cron ", __FILE__). $autorefresh);
                    foreach (eqLogic::byType('Palazzetti') as $Palazzetti) {
                        /** seulement si activé **/
                        if ($Palazzetti->getIsEnable()) {
                            try {
                                $Palazzetti->getInformations();
                            } catch (Exception $exc) {
                            }
                        }
                    }
                    log::add(__CLASS__, 'debug', __("Fin d'exécution du cron ", __FILE__). $autorefresh);
                }
            } catch (Exception $exc) {
                log::add(__CLASS__, 'error', __("Erreur lors de l'exécution du cron ", __FILE__) . $exc->getMessage());
            }
        }
        log::add(__CLASS__, 'debug', __FUNCTION__ . __(' : fin', __FILE__));
    }

    // avant création équipement
    public function postInsert()
    {
        config::save("*/15 * * * *", 'autorefresh', 'Palazzetti');
    }

    public function preUpdate()
    {
        log::add(__CLASS__, 'debug', __('Début', __FILE__) . ' : ' . __FUNCTION__);

        /** refuser si l'adresse est vide lors de l'enregistrement **/
        if (empty($this->getConfiguration('addressip'))) {
            throw new Exception(__('L\'adresse IP ne peut pas être vide', __FILE__));
        }
        log::add(__CLASS__, 'debug', __('Fin', __FILE__) . ' : ' . __FUNCTION__);
    }

    public function postUpdate()
    {
        log::add(__CLASS__, 'debug', __('Début', __FILE__) . ' : ' . __FUNCTION__);

        /** si équipement actif, rafraichir les infos de cet équipement **/
        if ($this->getIsEnable()) {
            $this->getInformations();
        }
        log::add(__CLASS__, 'debug', __('Fin', __FILE__) . ' : ' . __FUNCTION__);
    }

    public function preSave()
    {
        log::add(__CLASS__, 'debug', __('Début', __FILE__) . ' : ' . __FUNCTION__);
        //$this->createCmd();
        log::add(__CLASS__, 'debug', __('Fin', __FILE__) . ' : ' . __FUNCTION__);
    }

    public function postSave()
    {
        log::add(__CLASS__, 'debug', __('Début', __FILE__) . ' : ' . __FUNCTION__);
        $this->createCmdFromConfig();
        log::add(__CLASS__, 'debug', __('Fin', __FILE__) . ' : ' . __FUNCTION__);
    }

    public static $_widgetPossibility = array('custom' => array(
        'visibility' => true,
        'displayName' => true,
        'displayObjectName' => true,
        'optionalParameters' => true,
        'background-color' => true,
        'text-color' => true,
        'border' => true,
        'border-radius' => true,
        'background-opacity' => true,
    ));

    private function createCmdFromConfig()
    {
        $cmdConfig = $this->loadCmdFromConf('palazzetti');
        if (!$cmdConfig) {
            return false;
        }

        foreach ($cmdConfig as $config) {
            $cmd = null;
            foreach ($this->getCmd() as $liste_cmd) {
                if ((isset($config['logicalId']) && $liste_cmd->getLogicalId() == $config['logicalId'])
                || (isset($config['name']) && $liste_cmd->getName() == $config['name'])) {
                    $cmd = $liste_cmd;
                    break;
                }
            }
            if ($cmd === null || !is_object($cmd)) {
                log::add(__CLASS__, 'debug', __('Création commande ', __FILE__) . ' : ' . __FUNCTION__ . $config['logicalId']);
                $cmd = new PalazzettiCmd();
                $cmd->setEqLogic_id($this->getId());
                utils::a2o($cmd, $config);
                $cmd->save();
            }
        }
    }

    /** méthode de récupération des fichiers de configuration **/
    public function loadCmdFromConf($type)
    {
        if (!is_file(dirname(__FILE__) . '/../../core/config/' . $type . '.json')) {
            log::add(__CLASS__, 'debug', 'Fichier introuvable : ' . dirname(__FILE__) . '/config/' . $type . '.json');
            return false;
        }
        $content = file_get_contents(dirname(__FILE__) . '/../../core/config/' . $type . '.json');
        if (!is_json($content)) {
            log::add(__CLASS__, 'debug', 'JSON invalide : ' . $type . '.json');
            return false;
        }
        $device = json_decode($content, true);
        if (!is_array($device) || !isset($device)) {
            log::add(__CLASS__, 'debug', 'Tableau incorrect : ' . $type . '.json');
            return false;
        }
        return $device;
    }

    // methode requete
    public function makeRequest($cmd)
    {
        $url = 'http://' . $this->getConfiguration('addressip') . '/cgi-bin/sendmsg.lua?cmd=' . $cmd;
        log::add(__CLASS__, 'debug', __FUNCTION__ . ' - ' . 'get URL ' . $url);

        try {
            $request_http = new com_http($url);
            $return = $request_http->exec(4, 1);
        } catch (Exception $e) {
            if ($e->getCode() == 404) {
                log::add(__CLASS__, 'debug', __FUNCTION__.' - '. $e->getCode() . ' erreur connexion : ' . $e->getMessage());
                throw $e;
            }
            log::add(__CLASS__, 'debug', __FUNCTION__.' - '. $e->getCode() . ' probleme connexion : ' . $e->getMessage());
            return false;
        }

        $return = json_decode($return);
        if ($return->INFO->RSP != 'OK') {
            log::add(__CLASS__, 'debug', __FUNCTION__.' - '. ' erreur résultat : ' . $cmd);
            return false;
        } else {
            log::add(__CLASS__, 'debug', __FUNCTION__ . ' - ' . 'get result ' . json_encode($return));
            return $return;
        }
    }

    // interpretation valeur ventilateur
    public function getFanState($num)
    {
        switch ($num) {
            case 0:
                $value = 'OFF';
                break;
            case 6:
                $value = 'AUTO';
                break;
            case 7:
                $value = 'HIGH';
                break;
            default:
                $value = $num;
        }
        return $value;
    }

    public static function getFanStateF3L($num)
    {
        switch ($num) {
            case 0:
                $value = 'OFF';
                break;
            case 1:
                $value = 'ON';
                break;
            default:
                $value = $num;
        }
        return $value;
    }

    public static function getFanStateF4L($num)
    {
        switch ($num) {
            case 0:
                $value = 'OFF';
                break;
            case 1:
                $value = 'ON';
                break;
            default:
                $value = $num;
        }
        return $value;
    }

    // interpretation valeur status poele
    public static function getStoveState($num)
    {
        $lib[0] = 'Eteint';
        $lib[1] = 'Arrêté';
        $lib[2] = 'Vérification';
        $lib[3] = 'Chargement granulés';
        $lib[4] = 'Allumage';
        $lib[5] = 'Contrôle combustion';
        $lib[6] = 'En chauffe';
        $lib[9] = 'Diffusion';
        $lib[10] = 'Extinction';
        $lib[11] = 'Nettoyage';
        $lib[12] = 'Refroidissement';
        $lib[241] = 'Erreur Nettoyage';
        $lib[243] = 'Erreur Grille';
        $lib[244] = 'NTC2 ALARM';
        $lib[245] = 'NTC3 ALARM';
        $lib[247] = 'Erreur Porte';
        $lib[248] = 'Erreur Dépression';
        $lib[249] = 'NTC1 ALARM';
        $lib[250] = 'TC1 ALARM';
        $lib[252] = 'Erreur évacuation Fumée';
        $lib[253] = 'Pas de pellets';
        if (isset($lib[$num])) {
            return $lib[$num];
        } else {
            return $num;
        }
    }

    // methode jour de la semaine
    public static function getWeekDay($num)
    {
        $lib[1] = 'Lundi';
        $lib[2] = 'Mardi';
        $lib[3] = 'Mercredi';
        $lib[4] = 'Jeudi';
        $lib[5] = 'Vendredi';
        $lib[6] = 'Samedi';
        $lib[7] = 'Dimanche';
        if (isset($lib[$num])) {
            return $lib[$num];
        } else {
            return 'Jour #' . $num;
        }
    }
    // methode traitement commande
    public function sendCommand($CMD, $_options)
    {
        // requete http
        $cmdString = $CMD->getConfiguration('actionCmd');
        // si option value ajout dans la requete
        if (isset($_options) && $_options != '') {
            if (is_array($_options)) {
                // cas ph
                if (isset($_options['jour']) && isset($_options['tranche']) && isset($_options['programme'])) {
                    $cmdString = $cmdString . $_options['jour'] . '+' . $_options['tranche'] . '+' . $_options['programme'];
                } elseif (isset($_options['numero']) && isset($_options['temperature']) && isset($_options['h1']) && isset($_options['m1']) && isset($_options['h2']) && isset($_options['m2'])) {
                    $cmdString = $cmdString . $_options['numero'] . '+' . $_options['temperature'] . '+' . $_options['h1'] . '+' . $_options['m1'] . '+' . $_options['h2'] . '+' . $_options['m2'];
                } elseif (isset($_options['slider'])) {
                    $cmdString = $cmdString . $_options['slider'];
                }
            } else {
                $cmdString = $cmdString . $_options;
            }
            log::add(__CLASS__, 'debug', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . ' commande ' . $cmdString);
            log::add(__CLASS__, 'debug', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . ' commande ' . json_encode($_options));
        }
        $DATA = $this->makeRequest($cmdString);

        if (!$DATA) {
            return 'ERROR';
        }
        // verification succes du traitement
        if ($DATA->INFO->RSP != 'OK') {
            log::add(__CLASS__, 'error', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . ' erreur ' . $CMD . ' : ' . $DATA->INFO->RSP);
            return false;
        }
        // definition patern de comparaison
        $expl = explode('+', $cmdString);
        $pattern = $expl[0] . '+' . $expl[1];

        // traitement suivant commande
        switch ($pattern) {
                // allumage, extinction, status
            case 'CMD+ON':
            case 'CMD+OFF':
            case 'GET+STAT':
                $value = $this->getStoveState($DATA->Status->STATUS);
                break;
                // nom poele
            case 'GET+LABL':
            case 'SET+LABL':
                $value = $DATA->StoveData->LABEL;
                break;
                // force du feu
            case 'SET+POWR':
                $value = $DATA->DATA->PWR;
                break;
                // température de consigne
            case 'GET+SETP':
            case 'SET+SETP':
                $value = $DATA->DATA->SETP;
                break;
                // force du ventilateur
            case 'GET+FAND':
                $value = $this->getFanState($DATA->Fans->FAN_FAN2LEVEL);
                break;
            case 'SET+RFAN':
                $value = $this->getFanState($DATA->DATA->F2L);
                break;
                // force ventilateur F3L
            case 'SET+FN3L':
                $value = $this->getFanState($DATA->DATA->F3L);
                break;
                // force ventilateur F4L
            case 'SET+FN4L':
                $value = $this->getFanState($DATA->DATA->F4L);
                break;
                // température ambiance
            case 'GET+TMPS':
                $value = $DATA->DATA->T1;
                break;
                // programmes horaires
            case 'GET+CHRD':
                $value = json_encode($DATA->DATA);
                break;
                // programmes horaires
            case 'SET+CSST':
                break;
                // affectation programme
                // options +JOUR+TRANCHE+PH
            case 'SET+CDAY':
                break;
                // informations automate
            case 'EXT+ADRD':
                $value = $DATA->DATA->{'ADDR_' . $expl[2]};
                log::add(__CLASS__, 'debug', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . 'reponse ' . $value);
                break;
        }

        // mise a jour variables info
        if ($CMD->getConfiguration('updateLogicalId')) {
            $INFO = $this->getCmd(null, $CMD->getConfiguration('updateLogicalId'));
            $INFO->event($value);
            $INFO->save();
            log::add(__CLASS__, 'debug', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . 'response ' . $value);
            log::add(__CLASS__, 'debug', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . 'updatelogicalId ' .  $CMD->getConfiguration('updateLogicalId') . ' = ' . $value);
        }
        // mise à jour lastvalue commande
        $CMD->setConfiguration('lastCmdValue', $value);
        $CMD->save();
        return 'OK';
    }

    public function toHtml($_version = 'dashboard')
    {
        if ($this->getConfiguration('widgetTemplate') != 1) {
            return parent::toHtml($_version);
        }
        $replace = $this->preToHtml($_version);
        if (!is_array($replace)) {
            return $replace;
        }
        $version = jeedom::versionAlias($_version);

        $heure = $this->getCmd(null, 'ITime');
        $replace['#heure#'] = is_object($heure) ? $heure->execCmd() : '';
        $replace['#heure_id#'] = is_object($heure) ? $heure->getId() : '';

        $temps = $this->getCmd(null, 'ITemp');
        $replace['#temperature#'] = is_object($temps) ? $temps->execCmd() : '';
        $replace['#temperature_id#'] = is_object($temps) ? $temps->getId() : '';
        $replace['#temperature_valueDate#'] = is_object($temps) ? $temps->getValueDate() : '';
        $replace['#temperature_collectDate#'] = is_object($temps) ? $temps->getCollectDate() : '';
        $replace['#temperature_unite#'] = is_object($temps) ? $temps->getUnite() : '';
        $replace['#temperature_display#'] = (is_object($temps) && $temps->getIsVisible()) ? '#temperature_display#' : 'none';

        $temps2 = $this->getCmd(null, 'ITemp2');
        $replace['#temperature2#'] = is_object($temps2) ? $temps2->execCmd() : '';
        $replace['#temperature2_id#'] = is_object($temps2) ? $temps2->getId() : '';
        $replace['#temperature2_valueDate#'] = is_object($temps2) ? $temps2->getValueDate() : '';
        $replace['#temperature2_collectDate#'] = is_object($temps2) ? $temps2->getCollectDate() : '';
        $replace['#temperature2_unite#'] = is_object($temps2) ? $temps2->getUnite() : '';
        $replace['#temperature2_display#'] = (is_object($temps) && $temps2->getIsVisible()) ? '#temperature2_display#' : 'none';

        $temps3 = $this->getCmd(null, 'ITemp3');
        $replace['#temperature3#'] = is_object($temps3) ? $temps3->execCmd() : '';
        $replace['#temperature3_id#'] = is_object($temps3) ? $temps3->getId() : '';
        $replace['#temperature3_valueDate#'] = is_object($temps3) ? $temps3->getValueDate() : '';
        $replace['#temperature3_collectDate#'] = is_object($temps3) ? $temps3->getCollectDate() : '';
        $replace['#temperature3_unite#'] = is_object($temps3) ? $temps3->getUnite() : '';
        $replace['#temperature3_display#'] = (is_object($temps) && $temps3->getIsVisible()) ? '#temperature3_display#' : 'none';

        $status = $this->getCmd(null, 'IStatus');
        $replace['#status#'] = is_object($status) ? $status->execCmd() : '';
        $replace['#status_id#'] = is_object($status) ? $status->getId() : '';

        $consigne = $this->getCmd(null, 'IConsigne');
        $replace['#consigne#'] = is_object($consigne) ? $consigne->execCmd() : '';
        $replace['#consigne_id#'] = is_object($consigne) ? $consigne->getId() : '';
        $replace['#consigne_valueDate#'] = is_object($consigne) ? $consigne->getValueDate() : '';
        $replace['#consigne_collectDate#'] = is_object($consigne) ? $consigne->getCollectDate() : '';
        $replace['#consigne_unite#'] = is_object($consigne) ? $consigne->getUnite() : '';
        $replace['#consigne_minValue#'] = is_object($consigne) ? $consigne->getConfiguration('minValue') : '';
        $replace['#consigne_maxValue#'] = is_object($consigne) ? $consigne->getConfiguration('maxValue') : '';
        $replace['#consigne_display#'] = (is_object($consigne) && $consigne->getIsVisible()) ? '#consigne_display#' : 'none';
        $Wconsigne = $this->getCmd(null, 'WConsigne');
        $replace['#consigneSet_id#'] = is_object($Wconsigne) ? $Wconsigne->getId() : '';
        if (is_array($Wconsigne->getDisplay('parameters'))) {
            foreach ($Wconsigne->getDisplay('parameters') as $key => $value) {
                $replace['#consigne_' . $key . '#'] = $value;
            }
        }

        $power = $this->getCmd(null, 'IPower');
        $replace['#power#'] = is_object($power) ? $power->execCmd() : '';
        $replace['#power_id#'] = is_object($power) ? $power->getId() : '';
        $replace['#power_valueDate#'] = is_object($power) ? $power->getValueDate() : '';
        $replace['#power_collectDate#'] = is_object($power) ? $power->getCollectDate() : '';
        $replace['#power_minValue#'] = is_object($power) ? $power->getConfiguration('minValue') : '';
        $replace['#power_maxValue#'] = is_object($power) ? $power->getConfiguration('maxValue') : '';
        $replace['#power_display#'] = (is_object($power) && $power->getIsVisible()) ? '#power_display#' : 'none';
        $Wpower = $this->getCmd(null, 'Wpower');
        $replace['#powerSet_id#'] = is_object($Wpower) ? $Wpower->getId() : '';
        if (is_array($Wpower->getDisplay('parameters'))) {
            foreach ($Wpower->getDisplay('parameters') as $key => $value) {
                $replace['#power_' . $key . '#'] = $value;
            }
        }

        $fan = $this->getCmd(null, 'IFan');
        $replace['#fan#'] = is_object($fan) ? $fan->execCmd() : '';
        $replace['#fan_id#'] = is_object($fan) ? $fan->getId() : '';
        $replace['#fan_valueDate#'] = is_object($fan) ? $fan->getValueDate() : '';
        $replace['#fan_collectDate#'] = is_object($fan) ? $fan->getCollectDate() : '';
        $replace['#fan_minValue#'] = is_object($fan) ? $fan->getConfiguration('minValue') : '';
        $replace['#fan_maxValue#'] = is_object($fan) ? $fan->getConfiguration('maxValue') : '';
        $replace['#fan_display#'] = (is_object($fan) && $fan->getIsVisible()) ? '#fan_display#' : 'none';
        $Wfan = $this->getCmd(null, 'WFan');
        $replace['#fanSet_id#'] = is_object($Wfan) ? $Wfan->getId() : '';
        if (is_array($Wfan->getDisplay('parameters'))) {
            foreach ($Wfan->getDisplay('parameters') as $key => $value) {
                $replace['#fan_' . $key . '#'] = $value;
            }
        }

        $nbAll = $this->getCmd(null, 'INbAllumage');
        $replace['#nbAll#'] = is_object($nbAll) ? $nbAll->execCmd() : '';
        $replace['#nbAll_id#'] = is_object($nbAll) ? $nbAll->getId() : '';
        $replace['#nbAll_unit#'] = is_object($nbAll) ? $nbAll->getUnite() : '';
        $replace['#nbAll_valueDate#'] = is_object($nbAll) ? $nbAll->getValueDate() : '';
        $replace['#nbAll_collectDate#'] = is_object($nbAll) ? $nbAll->getCollectDate() : '';
        $replace['#nbAll_display#'] = (is_object($nbAll) && $nbAll->getIsVisible()) ? '#nbAll_display#' : 'none';

        $hae = $this->getCmd(null, 'IHeuresAlimElec');
        $replace['#hae#'] = is_object($hae) ? $hae->execCmd() : '';
        $replace['#hae_id#'] = is_object($hae) ? $hae->getId() : '';
        $replace['#hae_unit#'] = is_object($hae) ? $hae->getUnite() : '';
        $replace['#hae_valueDate#'] = is_object($hae) ? $hae->getValueDate() : '';
        $replace['#hae_collectDate#'] = is_object($hae) ? $hae->getCollectDate() : '';
        $replace['#hae_display#'] = (is_object($hae) && $hae->getIsVisible()) ? '#hae_display#' : 'none';

        $hc = $this->getCmd(null, 'IHeuresChauffe');
        $replace['#hc#'] = is_object($hc) ? $hc->execCmd() : '';
        $replace['#hc_id#'] = is_object($hc) ? $hc->getId() : '';
        $replace['#hc_unit#'] = is_object($hc) ? $hc->getUnite() : '';
        $replace['#hc_valueDate#'] = is_object($hc) ? $hc->getValueDate() : '';
        $replace['#hc_collectDate#'] = is_object($hc) ? $hc->getCollectDate() : '';
        $replace['#hc_display#'] = (is_object($hc) && $hc->getIsVisible()) ? '#hc_display#' : 'none';

        $hsc = $this->getCmd(null, 'IHeuresSurChauffe');
        $replace['#hsc#'] = is_object($hsc) ? $hsc->execCmd() : '';
        $replace['#hsc_id#'] = is_object($hsc) ? $hsc->getId() : '';
        $replace['#hsc_unit#'] = is_object($hsc) ? $hsc->getUnite() : '';
        $replace['#hsc_valueDate#'] = is_object($hsc) ? $hsc->getValueDate() : '';
        $replace['#hsc_collectDate#'] = is_object($hsc) ? $hsc->getCollectDate() : '';
        $replace['#hsc_display#'] = (is_object($hsc) && $hsc->getIsVisible()) ? '#hsc_display#' : 'none';

        $hde = $this->getCmd(null, 'IHeuresDepuisEntretien');
        $replace['#hde#'] = is_object($hde) ? $hde->execCmd() : '';
        $replace['#hde_id#'] = is_object($hde) ? $hde->getId() : '';
        $replace['#hde_unit#'] = is_object($hde) ? $hde->getUnite() : '';
        $replace['#hde_valueDate#'] = is_object($hde) ? $hde->getValueDate() : '';
        $replace['#hde_collectDate#'] = is_object($hde) ? $hde->getCollectDate() : '';
        $replace['#hde_display#'] = (is_object($hde) && $hde->getIsVisible()) ? '#hde_display#' : 'none';

        $haf = $this->getCmd(null, 'INbAllumageFailed');
        $replace['#haf#'] = is_object($haf) ? $haf->execCmd() : '';
        $replace['#haf_id#'] = is_object($haf) ? $haf->getId() : '';
        $replace['#haf_unit#'] = is_object($haf) ? $haf->getUnite() : '';
        $replace['#haf_valueDate#'] = is_object($haf) ? $haf->getValueDate() : '';
        $replace['#haf_collectDate#'] = is_object($haf) ? $haf->getCollectDate() : '';
        $replace['#haf_display#'] = (is_object($haf) && $haf->getIsVisible()) ? '#haf_display#' : 'none';

        $pqt = $this->getCmd(null, 'IQuantite');
        $replace['#pqt#'] = is_object($pqt) ? $pqt->execCmd() : '';
        $replace['#pqt_id#'] = is_object($pqt) ? $pqt->getId() : '';
        $replace['#pqt_unit#'] = is_object($pqt) ? $pqt->getUnite() : '';
        $replace['#pqt_valueDate#'] = is_object($pqt) ? $pqt->getValueDate() : '';
        $replace['#pqt_collectDate#'] = is_object($pqt) ? $pqt->getCollectDate() : '';
        $replace['#pqt_display#'] = (is_object($pqt) && $pqt->getIsVisible()) ? '#pqt_display#' : 'none';

        $network = $this->getCmd(null, 'INetwork');
        $replace['#network#'] = is_object($network) ? $network->execCmd() : '';
        $replace['#network_id#'] = is_object($network) ? $network->getId() : '';
        $replace['#network_display#'] = (is_object($network) && $network->getIsVisible()) ? '#network_display#' : 'none';

        $WOn = $this->getCmd(null, 'WOn');
        $replace['#on_id#'] = is_object($WOn) ? $WOn->getId() : '';
        $WOff = $this->getCmd(null, 'WOff');
        $replace['#off_id#'] = is_object($WOff) ? $WOff->getId() : '';

        $fanF3L = $this->getCmd(null, 'IFanF3L');
        $replace['#fanF3L#'] = $this->getFanStateF3L($fanF3L->execCmd());
        $WfanF3L = $this->getCmd(null, 'WFanF3L');
        $replace['#fanF3L_id#'] = is_object($WfanF3L) ? $WfanF3L->getId() : '';

        $fanF4L = $this->getCmd(null, 'IFanF4L');
        $replace['#fanF4L#'] = $this->getFanStateF4L($fanF4L->execCmd());
        $WfanF4L = $this->getCmd(null, 'WFanF4L');
        $replace['#fanF4L_id#'] = is_object($WfanF4L) ? $WfanF4L->getId() : '';

        $snap = $this->getCmd(null, 'ISnap');
        $replace['#snap_id#'] = is_object($snap) ? $snap->getId() : '';
        $replace['#snap_display#'] = (is_object($snap) && $snap->getIsVisible()) ? '#snap_display#' : 'none';

        $refresh = $this->getCmd(null, 'refresh');
        $replace['#refresh_id#'] = is_object($refresh) ? $refresh->getId() : '';

        $html = template_replace($replace, getTemplate('core', $version, 'Palazzetti', 'Palazzetti'));
        cache::set('PalazzettiWidget' . $_version . $this->getId(), $html, 0);
        return $html;
    }

    // recuperation automatique des informations
    public function getInformations()
    {
        log::add(__CLASS__, 'debug', __('Début', __FILE__) . ' : ' . __FUNCTION__);

        // recuperation de l'heure
        $DATA = $this->makeRequest('GET+TIME');
        if ($DATA) {
            $this->checkAndUpdateCmd('ITime', json_encode($DATA->DATA));
        }

        // recuperation de toutes les informations réseau
        $DATA = $this->makeRequest('GET+STDT');
        if ($DATA) {
            $this->checkAndUpdateCmd('IName', $DATA->DATA->LABEL);
            $this->checkAndUpdateCmd('INetwork', json_encode($DATA->DATA));
        }

        // recuperation des programmes horaires
        $DATA = $this->makeRequest('GET+CHRD');
        if ($DATA) {
            $this->checkAndUpdateCmd('IPH', json_encode($DATA->DATA));
        }

        // recuperation des infos compteurs
        $DATA = $this->makeRequest('GET+CNTR');
        if ($DATA) {
            $this->checkAndUpdateCmd('INbAllumage', $DATA->DATA->IGN);
            $this->checkAndUpdateCmd('INbAllumageFailed', $DATA->DATA->IGNERRORS);
            $this->checkAndUpdateCmd('IHeuresAlimElec', str_replace(':', '.', $DATA->DATA->POWERTIME));
            $this->checkAndUpdateCmd('IHeuresChauffe', str_replace(':', '.', $DATA->DATA->HEATTIME));
            $this->checkAndUpdateCmd('IHeuresSurChauffe', str_replace(':', '.', $DATA->DATA->OVERTMPERRORS));
            $this->checkAndUpdateCmd('IHeuresDepuisEntretien', str_replace(':', '.', $DATA->DATA->SERVICETIME));
            $this->checkAndUpdateCmd('IQuantite', $DATA->DATA->PQT);
        } else {
            $DATA = $this->makeRequest('EXT+ADRD+2066+1');
            if ($DATA) {
                $this->checkAndUpdateCmd('INbAllumage', $DATA->DATA->ADDR_2066);
            }
            $DATA = $this->makeRequest('EXT+ADRD+207C+1');
            if ($DATA) {
                $this->checkAndUpdateCmd('INbAllumageFailed', $DATA->DATA->ADDR_207C);
            }
            $DATA = $this->makeRequest('EXT+ADRD+206A+1');
            if ($DATA) {
                $this->checkAndUpdateCmd('IHeuresAlimElec', str_replace(':', '.', $DATA->DATA->ADDR_206A));
            }
            $DATA = $this->makeRequest('EXT+ADRD+2070+1');
            if ($DATA) {
                $this->checkAndUpdateCmd('IHeuresChauffe', str_replace(':', '.', $DATA->DATA->ADDR_2070));
            }
            $DATA = $this->makeRequest('EXT+ADRD+207A+1');
            if ($DATA) {
                $this->checkAndUpdateCmd('IHeuresSurChauffe', str_replace(':', '.', $DATA->DATA->ADDR_207A));
            }
            $DATA = $this->makeRequest('EXT+ADRD+2076+1');
            if ($DATA) {
                $this->checkAndUpdateCmd('IHeuresDepuisEntretien', str_replace(':', '.', $DATA->DATA->ADDR_2076));
            }
        }

        // recuperation de toutes les informations
        $DATA = $this->makeRequest('GET+ALLS');
        if ($DATA) {
            // mise à jour force du feu
            $this->checkAndUpdateCmd('IPower', $DATA->DATA->PWR);
            $this->checkAndUpdateCmd('IConsigne', $DATA->DATA->SETP);
            $this->checkAndUpdateCmd('IFan', $DATA->DATA->F2L);
            $this->checkAndUpdateCmd('IFanF3L', $DATA->DATA->F3L);
            $this->checkAndUpdateCmd('IFanF4L', $DATA->DATA->F4L);
            $this->checkAndUpdateCmd('ITemp', round($DATA->DATA->T1, 2));
            $this->checkAndUpdateCmd('ITemp2', round($DATA->DATA->T2, 2));
            $this->checkAndUpdateCmd('ITemp3', round($DATA->DATA->T3, 2));
            $this->checkAndUpdateCmd('IStatus', $DATA->DATA->STATUS);
            $this->checkAndUpdateCmd('ISnap', json_encode($DATA));
        }
    }

    public function updateCmd($_logicalId, $_value)
    {
        $cmd = $this->getCmd(null, $_logicalId);
        if (is_object($cmd)) {
            $cmd->event($_value);
            $cmd->save();
        }
    }
}

class PalazzettiCmd extends cmd
{
    /*     * *************************Attributs******************************
    public static $_widgetPossibility = array('custom' => false);

/*     * *********************Methode d'instance************************* */

    public function dontRemoveCmd()
    {
        return true;
    }

    public function execute($_options = null)
    {
        $eqLogic = $this->getEqLogic();

        log::add('Palazzetti', 'debug', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . 'options ' . json_encode($this->getConfiguration('options')));
        log::add('Palazzetti', 'debug', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . '$_options ' . json_encode($_options));
        if ($this->getLogicalId('') == 'refresh') {
            $eqLogic->getInformations();
        } else {
            $eqLogic->sendCommand($this, $_options);
        }
    }
}
