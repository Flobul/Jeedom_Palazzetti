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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class Palazzetti extends eqLogic
{
    public static $_pluginVersion = '1.23';
    private const REQUEST_ERROR_LOG_INTERVAL = 3600;
    private const DISCOVERY_PORT = 54549;
    private const DISCOVERY_TIMEOUT = 3;
    private const DISCOVERY_MESSAGE = 'bridge?';
    private const DISCOVERY_MAX_UNICAST_HOSTS = 1024;

    public static function pull()
    {
        log::add(__CLASS__, 'debug', __FUNCTION__ . ' : ' . __('Démarrage du cron', __FILE__));
        $autorefresh = config::byKey('autorefresh', __CLASS__, '');
        $eqLogics = eqLogic::byType(__CLASS__);
        if ($autorefresh != '') {
            try {
                $c = new Cron\CronExpression($autorefresh, new Cron\FieldFactory());
                if ($c->isDue()) {
                    try {
                        foreach ($eqLogics as $eqLogic) {
                            if ($eqLogic->getIsEnable()) {
                                try {
                                    $eqLogic->getInformations();
                                    $eqLogic->refreshWidget();
                                } catch (Exception $exc) {
                                }
                            }
                        }
                    } catch (Exception $exc) {
                        log::add(__CLASS__, 'error', __('Erreur : ', __FILE__) . $exc->getMessage());
                    }
                }
            } catch (Exception $exc) {
                log::add(__CLASS__, 'error', __('Expression cron non valide : ', __FILE__) . $autorefresh);
            }
        }
        log::add(__CLASS__, 'debug', __FUNCTION__ . ' : ' . __('fin', __FILE__));
    }

    /**
     * Crée, met à jour ou désactive la tâche de découverte automatique.
     */
    public static function configureAutoDiscoveryCron()
    {
        $schedule = trim((string) config::byKey('auto_discovery_interval', __CLASS__, ''));
        $cron = cron::byClassAndFunction(__CLASS__, 'cronAutoDiscover');

        if ($schedule === '') {
            if (is_object($cron)) {
                $cron->setEnable(0);
                $cron->save();
                $cron->stop();
            }
            return;
        }

        if (!is_object($cron)) {
            $cron = new cron();
        }
        $cron->setClass(__CLASS__);
        $cron->setFunction('cronAutoDiscover');
        $cron->setEnable(1);
        $cron->setDeamon(0);
        $cron->setSchedule($schedule);
        $cron->setTimeout(60);
        $cron->save();
    }

    /**
     * Lance la découverte depuis la tâche Jeedom dédiée.
     */
    public static function cronAutoDiscover()
    {
        if (trim((string) config::byKey('auto_discovery_interval', __CLASS__, '')) === '') {
            return;
        }

        try {
            self::discover();
        } catch (Exception $e) {
            log::add(__CLASS__, 'error', __FUNCTION__ . __(' - échec de la découverte automatique : ', __FILE__) . $e->getMessage());
        }
    }

    /**
     * Retourne les contrôles affichés dans les pages de santé Jeedom.
     */
    public static function health()
    {
        $return = array();
        $cron = cron::byClassAndFunction(__CLASS__, 'pull');
        $cronEnabled = is_object($cron) && (int) $cron->getEnable(0) === 1;
        $return[] = array(
            'test' => __('Tâche de rafraîchissement', __FILE__),
            'result' => $cronEnabled ? __('OK', __FILE__) : __('NOK', __FILE__),
            'advice' => $cronEnabled ? '' : __('Vérifiez la tâche Palazzetti::pull dans le moteur des tâches.', __FILE__),
            'state' => $cronEnabled
        );

        $socketAvailable = function_exists('socket_create') && function_exists('socket_sendto');
        $return[] = array(
            'test' => __('Découverte UDP', __FILE__),
            'result' => $socketAvailable ? __('Disponible', __FILE__) : __('Indisponible', __FILE__),
            'advice' => $socketAvailable ? '' : __('Installez ou activez l\'extension PHP sockets pour utiliser la découverte.', __FILE__),
            'state' => $socketAvailable
        );

        $eqLogics = eqLogic::byType(__CLASS__);
        $enabled = 0;
        $offline = 0;
        $invalidAddresses = 0;
        foreach ($eqLogics as $eqLogic) {
            if (!$eqLogic->getIsEnable()) {
                continue;
            }
            $enabled++;
            if (filter_var($eqLogic->getConfiguration('addressip'), FILTER_VALIDATE_IP) === false
                && preg_match('/^[A-Za-z0-9.-]+$/', (string) $eqLogic->getConfiguration('addressip')) !== 1) {
                $invalidAddresses++;
            }
            if ($eqLogic->getCommunicationHealth()['offline']) {
                $offline++;
            }
        }

        $equipmentState = $invalidAddresses === 0 && $offline === 0;
        $return[] = array(
            'test' => __('Équipements', __FILE__),
            'result' => sprintf(__('%d configuré(s), %d actif(s), %d hors ligne', __FILE__), count($eqLogics), $enabled, $offline),
            'advice' => $invalidAddresses > 0
                ? sprintf(__('%d adresse(s) invalide(s) sont configurées.', __FILE__), $invalidAddresses)
                : ($offline > 0 ? __('Consultez les équipements en erreur ci-dessous.', __FILE__) : ''),
            'state' => $equipmentState
        );

        return $return;
    }

    /**
     * Recherche les passerelles Palazzetti par broadcast UDP et les enregistre.
     */
    public static function discover()
    {
        if (!function_exists('socket_create')) {
            throw new Exception(__('L\'extension PHP sockets est nécessaire pour la découverte UDP.', __FILE__));
        }

        $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($socket === false) {
            throw new Exception(__('Impossible de créer le socket de découverte UDP.', __FILE__));
        }

        try {
            if (!@socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1)
                || !@socket_bind($socket, '0.0.0.0', 0)) {
                throw new Exception(__('Impossible d\'initialiser le socket de découverte UDP : ', __FILE__)
                    . socket_strerror(socket_last_error($socket)));
            }

            $targets = self::getDiscoveryTargetAddresses();
            $sentTargets = 0;
            foreach ($targets as $targetAddress) {
                $length = strlen(self::DISCOVERY_MESSAGE);
                if (@socket_sendto($socket, self::DISCOVERY_MESSAGE, $length, 0, $targetAddress, self::DISCOVERY_PORT) === $length) {
                    $sentTargets++;
                }
            }
            if ($sentTargets === 0) {
                throw new Exception(__('Aucune requête de découverte UDP n\'a pu être envoyée.', __FILE__));
            }
            log::add(__CLASS__, 'debug', __FUNCTION__ . ' - ' . sprintf(
                __('%d cible(s) UDP contactée(s) sur %d', __FILE__),
                $sentTargets,
                count($targets)
            ));

            $devices = array();
            $deadline = microtime(true) + self::DISCOVERY_TIMEOUT;
            while (microtime(true) < $deadline) {
                $remaining = max(0, $deadline - microtime(true));
                $seconds = (int) floor($remaining);
                $microseconds = (int) (($remaining - $seconds) * 1000000);
                $read = array($socket);
                $write = null;
                $except = null;
                $selected = @socket_select($read, $write, $except, $seconds, $microseconds);
                if ($selected === false || $selected === 0) {
                    break;
                }

                $payload = '';
                $sourceAddress = '';
                $sourcePort = 0;
                $received = @socket_recvfrom($socket, $payload, 65535, 0, $sourceAddress, $sourcePort);
                if ($received === false || $received === 0) {
                    continue;
                }
                $device = self::parseDiscoveryResponse($payload, $sourceAddress);
                if ($device === null) {
                    continue;
                }
                $identity = $device['mac'] !== '' ? $device['mac'] : $device['ip'];
                $devices[$identity] = $device;
            }
        } finally {
            socket_close($socket);
        }

        foreach ($devices as $identity => $device) {
            $devices[$identity] = self::detectDiscoveredGatewayType($device);
        }
        $result = self::saveDiscoveredDevices(array_values($devices));
        log::add(__CLASS__, 'info', __FUNCTION__ . ' - ' . sprintf(
            __('%d passerelle(s) trouvée(s), %d créée(s), %d mise(s) à jour', __FILE__),
            $result['found'],
            $result['created'],
            $result['updated']
        ));
        return $result;
    }

    /**
     * Construit la liste des adresses de broadcast IPv4 locales.
     */
    private static function getDiscoveryBroadcastAddresses()
    {
        $addresses = array('255.255.255.255');
        if (!function_exists('net_get_interfaces')) {
            return $addresses;
        }

        $interfaces = @net_get_interfaces();
        if (!is_array($interfaces)) {
            return $addresses;
        }
        foreach ($interfaces as $interface) {
            if (!isset($interface['unicast']) || !is_array($interface['unicast'])) {
                continue;
            }
            foreach ($interface['unicast'] as $unicast) {
                if (!isset($unicast['family'], $unicast['address'], $unicast['netmask'])
                    || (int) $unicast['family'] !== AF_INET
                    || filter_var($unicast['address'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false
                    || filter_var($unicast['netmask'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false
                    || strpos($unicast['address'], '127.') === 0) {
                    continue;
                }
                $address = ip2long($unicast['address']);
                $netmask = ip2long($unicast['netmask']);
                if ($address === false || $netmask === false) {
                    continue;
                }
                $broadcast = long2ip(($address & $netmask) | ((~$netmask) & 0xffffffff));
                if ($broadcast !== false) {
                    $addresses[] = $broadcast;
                }
            }
        }
        return array_values(array_unique($addresses));
    }

    /**
     * Ajoute aux broadcasts locaux les sous-réseaux CIDR configurés.
     *
     * Le broadcast dirigé suffit sur les réseaux qui l'autorisent. Pour les
     * environnements Docker/VLAN où il est souvent filtré, les petites plages
     * sont également parcourues en unicast UDP.
     */
    private static function getDiscoveryTargetAddresses()
    {
        $targets = array_fill_keys(self::getDiscoveryBroadcastAddresses(), true);
        $rawNetworks = (string) config::byKey('discovery_networks', __CLASS__, '');
        $networks = array_filter(array_map('trim', preg_split('/[\r\n,;]+/', $rawNetworks)));

        foreach ($networks as $cidr) {
            $range = self::parseDiscoveryCidr($cidr);
            if ($range === null) {
                log::add(__CLASS__, 'warning', __FUNCTION__ . ' - ' . sprintf(
                    __('Sous-réseau CIDR privé invalide ignoré : %s', __FILE__),
                    $cidr
                ));
                continue;
            }

            $targets[long2ip($range['broadcast'])] = true;
            if ($range['hostCount'] > self::DISCOVERY_MAX_UNICAST_HOSTS) {
                log::add(__CLASS__, 'warning', __FUNCTION__ . ' - ' . sprintf(
                    __('Scan unicast ignoré pour %s : %d hôtes dépassent la limite de %d. Le broadcast du sous-réseau reste utilisé.', __FILE__),
                    $cidr,
                    $range['hostCount'],
                    self::DISCOVERY_MAX_UNICAST_HOSTS
                ));
                continue;
            }

            for ($address = $range['firstHost']; $address <= $range['lastHost']; $address++) {
                $targets[long2ip($address)] = true;
            }
        }

        return array_keys($targets);
    }

    /**
     * Valide un CIDR IPv4 privé et retourne ses bornes sous forme d'entiers.
     */
    private static function parseDiscoveryCidr($cidr)
    {
        if (preg_match('/^([^\/]+)\/(\d{1,2})$/', trim((string) $cidr), $matches) !== 1) {
            return null;
        }

        $address = $matches[1];
        $prefix = (int) $matches[2];
        if ($prefix < 16 || $prefix > 32
            || filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false
            || !self::isPrivateDiscoveryAddress($address)) {
            return null;
        }

        $addressLong = ip2long($address);
        if ($addressLong === false) {
            return null;
        }
        if ($addressLong < 0) {
            $addressLong += 4294967296;
        }

        $hostBits = 32 - $prefix;
        $hostMask = $hostBits === 0 ? 0 : (2 ** $hostBits) - 1;
        $network = $addressLong & (0xffffffff ^ $hostMask);
        $broadcast = $network | $hostMask;
        $firstHost = $prefix >= 31 ? $network : $network + 1;
        $lastHost = $prefix >= 31 ? $broadcast : $broadcast - 1;

        return array(
            'network' => $network,
            'broadcast' => $broadcast,
            'firstHost' => $firstHost,
            'lastHost' => $lastHost,
            'hostCount' => max(0, $lastHost - $firstHost + 1)
        );
    }

    private static function isPrivateDiscoveryAddress($address)
    {
        $long = ip2long($address);
        if ($long === false) {
            return false;
        }
        if ($long < 0) {
            $long += 4294967296;
        }

        return ($long >= ip2long('10.0.0.0') && $long <= ip2long('10.255.255.255'))
            || ($long >= ip2long('172.16.0.0') && $long <= ip2long('172.31.255.255'))
            || ($long >= ip2long('192.168.0.0') && $long <= ip2long('192.168.255.255'));
    }

    /**
     * Valide et normalise une réponse UDP GET STDT.
     */
    private static function parseDiscoveryResponse($payload, $sourceAddress)
    {
        if (filter_var($sourceAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return null;
        }
        $response = json_decode(trim((string) $payload), true);
        if (!is_array($response) || !isset($response['DATA']) || !is_array($response['DATA'])) {
            return null;
        }
        if (isset($response['INFO']['RSP']) && strtoupper((string) $response['INFO']['RSP']) !== 'OK') {
            return null;
        }

        $data = $response['DATA'];
        if (!isset($data['LABEL']) && !isset($data['MAC']) && !isset($data['WMAC']) && !isset($data['SN'])) {
            return null;
        }
        $name = isset($data['LABEL']) ? trim(strip_tags((string) $data['LABEL'])) : '';
        $cleanName = preg_replace('/[\x00-\x1F\x7F]/', '', $name);
        $name = is_string($cleanName) ? $cleanName : '';
        if ($name === '') {
            $name = 'Palazzetti ' . $sourceAddress;
        }
        if (function_exists('mb_substr')) {
            $name = mb_substr($name, 0, 80);
        } else {
            $name = substr($name, 0, 80);
        }

        $mac = self::normalizeMac(isset($data['WMAC']) ? $data['WMAC'] : (isset($data['MAC']) ? $data['MAC'] : ''));
        $versions = array();
        foreach (array('SYSTEM', 'plzbridge', 'sendmsg') as $versionKey) {
            if (isset($data[$versionKey]) && is_scalar($data[$versionKey]) && trim((string) $data[$versionKey]) !== '') {
                $versions[] = $versionKey . ': ' . trim((string) $data[$versionKey]);
            }
        }

        return array(
            'ip' => $sourceAddress,
            'name' => $name,
            'mac' => $mac,
            'serial' => isset($data['SN']) && is_scalar($data['SN']) ? trim((string) $data['SN']) : '',
            'model' => isset($data['MOD']) && is_scalar($data['MOD']) ? trim((string) $data['MOD']) : '',
            'versions' => implode(' | ', $versions),
            'gatewayType' => __('Connection Box / WPalaControl', __FILE__),
            'isWirelessPalaControl' => false,
            'stoveSerial' => isset($data['SN']) && is_scalar($data['SN']) ? trim((string) $data['SN']) : '',
            'stoveModel' => isset($data['MOD']) && is_scalar($data['MOD']) ? trim((string) $data['MOD']) : ''
        );
    }

    /**
     * Identifie WPalaControl grâce à ses points d'état HTTP propres.
     */
    private static function detectDiscoveredGatewayType($device)
    {
        $endpoints = array(
            '/gs0' => array('serial' => 'sn', 'model' => 'model', 'version' => 'version'),
            '/ffffffff' => array('serial' => 'sn', 'model' => 'm', 'version' => 'v')
        );
        foreach ($endpoints as $endpoint => $fields) {
            try {
                $request = new com_http('http://' . $device['ip'] . $endpoint);
                $rawResponse = $request->exec(2, 1);
            } catch (Exception $e) {
                continue;
            }
            $response = json_decode((string) $rawResponse, true);
            if (!is_array($response)) {
                continue;
            }

            $model = isset($response[$fields['model']]) && is_scalar($response[$fields['model']])
                ? trim((string) $response[$fields['model']])
                : '';
            $isWPalaControl = $endpoint === '/ffffffff'
                || stripos($model, 'palacontrol') !== false
                || (isset($response['manufacturer']) && stripos((string) $response['manufacturer'], 'domochip') !== false);
            if (!$isWPalaControl) {
                continue;
            }

            $device['gatewayType'] = 'WPalaControl';
            $device['isWirelessPalaControl'] = true;
            if (isset($response[$fields['serial']]) && is_scalar($response[$fields['serial']])) {
                $device['serial'] = trim((string) $response[$fields['serial']]);
            }
            if ($model !== '') {
                $device['model'] = $model;
            }
            if (isset($response[$fields['version']]) && is_scalar($response[$fields['version']])) {
                $device['versions'] = trim((string) $response[$fields['version']]);
            }
            return $device;
        }

        return $device;
    }

    private static function normalizeMac($mac)
    {
        $normalized = preg_replace('/[^0-9A-F]/i', '', (string) $mac);
        $compact = is_string($normalized) ? strtoupper($normalized) : '';
        return strlen($compact) === 12 ? implode(':', str_split($compact, 2)) : '';
    }

    /**
     * Crée les nouveaux équipements et actualise ceux déjà connus.
     */
    private static function saveDiscoveredDevices($devices)
    {
        $byIp = array();
        $byMac = array();
        $bySerial = array();
        foreach (eqLogic::byType(__CLASS__) as $eqLogic) {
            $ip = trim((string) $eqLogic->getConfiguration('addressip'));
            $mac = self::normalizeMac($eqLogic->getConfiguration('discoveryMac'));
            $serial = trim((string) $eqLogic->getConfiguration('serialNumber'));
            if ($ip !== '') {
                $byIp[$ip] = $eqLogic;
            }
            if ($mac !== '') {
                $byMac[$mac] = $eqLogic;
            }
            if ($serial !== '') {
                $bySerial[$serial] = $eqLogic;
            }
        }

        $result = array('found' => count($devices), 'created' => 0, 'updated' => 0, 'unchanged' => 0, 'devices' => array());
        foreach ($devices as $device) {
            $eqLogic = null;
            if ($device['mac'] !== '' && isset($byMac[$device['mac']])) {
                $eqLogic = $byMac[$device['mac']];
            } elseif ($device['serial'] !== '' && isset($bySerial[$device['serial']])) {
                $eqLogic = $bySerial[$device['serial']];
            } elseif (isset($byIp[$device['ip']])) {
                $eqLogic = $byIp[$device['ip']];
            }

            $isNew = !is_object($eqLogic);
            if ($isNew) {
                $eqLogic = new self();
                $eqLogic->setEqType_name(__CLASS__);
                $identity = $device['mac'] !== '' ? $device['mac'] : $device['ip'];
                $eqLogic->setLogicalId('discovered_' . substr(sha1($identity), 0, 24));
                $eqLogic->setName($device['name']);
                $eqLogic->setIsEnable(1);
                $eqLogic->setIsVisible(1);
            }

            $changed = $isNew;
            $configuration = array(
                'addressip' => $device['ip'],
                'discoveryMac' => $device['mac'],
                'stoveSerialNumber' => $device['stoveSerial'],
                'stoveModel' => $device['stoveModel'],
                'discoveredByUdp' => 1
            );
            $knownAsWPalaControl = in_array(
                $eqLogic->getConfiguration('isWirelessPalaControl'),
                array(true, 1, '1', 'true'),
                true
            );
            if ($device['isWirelessPalaControl'] || !$knownAsWPalaControl) {
                $configuration['serialNumber'] = $device['serial'];
                $configuration['model'] = $device['model'];
                $configuration['versions'] = $device['versions'];
                $configuration['gatewayType'] = $device['gatewayType'];
            }
            if ($device['isWirelessPalaControl']) {
                $configuration['isWirelessPalaControl'] = 1;
            }
            foreach ($configuration as $key => $value) {
                if ($value === '' && !$isNew) {
                    continue;
                }
                if ((string) $eqLogic->getConfiguration($key, '') !== (string) $value) {
                    $eqLogic->setConfiguration($key, $value);
                    $changed = true;
                }
            }

            if ($changed) {
                $eqLogic->save();
                if ($isNew) {
                    $result['created']++;
                } else {
                    $result['updated']++;
                }
            } else {
                $result['unchanged']++;
            }

            $eqLogic->clearRequestFailure();
            $eqLogic->setStatus('lastCommunication', date('Y-m-d H:i:s'));

            $byIp[$device['ip']] = $eqLogic;
            if ($device['mac'] !== '') {
                $byMac[$device['mac']] = $eqLogic;
            }
            if ($device['serial'] !== '') {
                $bySerial[$device['serial']] = $eqLogic;
            }
            $result['devices'][] = array(
                'id' => (int) $eqLogic->getId(),
                'name' => $eqLogic->getName(),
                'ip' => $device['ip'],
                'created' => $isNew
            );
        }
        return $result;
    }

    /**
     * Expose l'état de la dernière communication pour la page Santé.
     */
    public function getCommunicationHealth()
    {
        $state = cache::byKey($this->getRequestErrorCacheKey())->getValue(array());
        if (!is_array($state)) {
            $state = array();
        }
        return array(
            'offline' => !empty($state['offline']),
            'error' => isset($state['error']) ? (string) $state['error'] : '',
            'lastFailure' => isset($state['lastFailure']) ? (int) $state['lastFailure'] : 0,
            'failureCount' => isset($state['failureCount']) ? (int) $state['failureCount'] : 0,
            'lastRediscovery' => isset($state['lastRediscovery']) ? (int) $state['lastRediscovery'] : 0
        );
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
            $this->refreshWidget();
        }
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
            log::add(__CLASS__, 'debug', __('Fichier introuvable : ', __FILE__) . dirname(__FILE__) . '/config/' . $type . '.json');
            return false;
        }
        $content = file_get_contents(dirname(__FILE__) . '/../../core/config/' . $type . '.json');
        if (!is_json($content)) {
            log::add(__CLASS__, 'debug', __('JSON invalide : ', __FILE__) . $type . '.json');
            return false;
        }
        $device = json_decode($content, true);
        if (!is_array($device) || !isset($device)) {
            log::add(__CLASS__, 'debug', __('Tableau incorrect : ', __FILE__) . $type . '.json');
            return false;
        }
        return $device;
    }

    // methode requete
    public function makeRequest($cmd, $_timeout = 5)
    {
        $address = trim((string) $this->getConfiguration('addressip'));
        if (filter_var($address, FILTER_VALIDATE_IP) === false && preg_match('/^[A-Za-z0-9.-]+$/', $address) !== 1) {
            $this->reportRequestFailure($cmd, __('adresse invalide : ', __FILE__) . $address);
            return false;
        }

        $baseUrl = 'http://' . $address . '/cgi-bin/sendmsg.lua?cmd=';
        $commandVariants = array($cmd, rawurlencode($cmd));
        $lastError = '';

        foreach (array_unique($commandVariants) as $commandVariant) {
            $url = $baseUrl . $commandVariant;
            log::add(__CLASS__, 'debug', __FUNCTION__ . ' - get URL ' . $url);
            try {
                $requestHttp = new com_http($url);
                $rawResponse = $requestHttp->exec($_timeout, 2);
            } catch (Exception $e) {
                $lastError = $e->getMessage();
                log::add(__CLASS__, 'debug', __FUNCTION__ . ' - ' . $e->getCode() . __(' problème de connexion : ', __FILE__) . $lastError);
                continue;
            }

            if (!is_string($rawResponse) || trim($rawResponse) === '') {
                $lastError = __('réponse vide', __FILE__);
                continue;
            }

            $response = json_decode($rawResponse);
            if (!is_object($response)) {
                $lastError = __('JSON invalide', __FILE__);
                continue;
            }

            if (isset($response->INFO) && is_object($response->INFO) && isset($response->INFO->RSP)) {
                log::add(__CLASS__, 'debug', __FUNCTION__ . __(' - résultat : ', __FILE__) . json_encode($response));
                $this->clearRequestFailure();
                $this->setStatus('lastCommunication', date('Y-m-d H:i:s'));
                return $response;
            }
            if (property_exists($response, 'PARM') || property_exists($response, 'HPAR') || property_exists($response, 'DATA')) {
                log::add(__CLASS__, 'debug', __FUNCTION__ . __(' - résultat données : ', __FILE__) . json_encode($response));
                $this->clearRequestFailure();
                $this->setStatus('lastCommunication', date('Y-m-d H:i:s'));
                return $response;
            }

            $lastError = __('réponse non reconnue', __FILE__);
        }

        $this->reportRequestFailure($cmd, $lastError);
        return false;
    }

    private function getRequestErrorCacheKey()
    {
        return __CLASS__ . '::requestError::' . $this->getId();
    }

    private function reportRequestFailure($cmd, $error)
    {
        $now = time();
        $error = trim((string) $error);
        $signature = sha1($error);
        $state = cache::byKey($this->getRequestErrorCacheKey())->getValue(array());
        if (!is_array($state)) {
            $state = array();
        }

        $lastLog = isset($state['lastLog']) ? intval($state['lastLog']) : 0;
        $lastSignature = isset($state['signature']) ? (string) $state['signature'] : '';
        $failureCount = isset($state['failureCount']) ? intval($state['failureCount']) + 1 : 1;
        if ($lastSignature !== $signature || ($now - $lastLog) >= self::REQUEST_ERROR_LOG_INTERVAL) {
            $message = 'makeRequest' . __(' - aucune réponse exploitable pour ', __FILE__) . $cmd;
            if ($error !== '') {
                $message .= ' (' . $error . ')';
            }
            log::add(__CLASS__, 'error', $message);
            $lastLog = $now;
        }

        cache::set($this->getRequestErrorCacheKey(), array(
            'offline' => 1,
            'lastLog' => $lastLog,
            'signature' => $signature,
            'error' => $error,
            'lastFailure' => $now,
            'failureCount' => $failureCount,
            'lastRediscovery' => isset($state['lastRediscovery']) ? intval($state['lastRediscovery']) : 0
        ), 0);
    }

    private function clearRequestFailure()
    {
        $state = cache::byKey($this->getRequestErrorCacheKey())->getValue(array());
        if (is_array($state) && !empty($state['offline'])) {
            log::add(__CLASS__, 'info', __('Connexion rétablie avec ', __FILE__) . $this->getConfiguration('addressip'));
        }
        cache::set($this->getRequestErrorCacheKey(), array(), 0);
    }

    // interpretation valeur ventilateur
    public function getFanState($num)
    {
        switch ($num) {
            case 0:
                return $this->getConfiguration('invertFanSpeed', false) ? 'OFF' : 'AUTO';
            case 6:
                return 'HIGH';
            case 7:
                return $this->getConfiguration('invertFanSpeed', false) ? 'AUTO' : 'OFF';
            default:
                return $num;
        }
    }

    public static function getFanStateF3L($num)
    {
        switch ($num) {
            case 0:
                return 'OFF';
            case 1:
                return 'ON';
            default:
                return $num;
        }
    }

    public static function getFanStateF4L($num)
    {
        switch ($num) {
            case 0:
                return 'OFF';
            case 1:
                return 'ON';
            default:
                return $num;
        }
    }

    // interpretation valeur status poele
    public static function getStoveState($num)
    {
        $lib = [
            0 => __('Eteint', __FILE__),
            1 => __('Arrêté', __FILE__),
            2 => __('Vérification', __FILE__),
            3 => __('Chargement granulés', __FILE__),
            4 => __('Allumage', __FILE__),
            5 => __('Contrôle combustion', __FILE__),
            6 => __('En chauffe', __FILE__),
            9 => __('Diffusion', __FILE__),
            10 => __('Extinction', __FILE__),
            11 => __('Nettoyage', __FILE__),
            12 => __('Refroidissement', __FILE__),
            241 => __('Erreur Nettoyage', __FILE__),
            243 => __('Erreur Grille', __FILE__),
            244 => __('NTC2 ALARM', __FILE__),
            245 => __('NTC3 ALARM', __FILE__),
            247 => __('Erreur Porte', __FILE__),
            248 => __('Erreur Dépression', __FILE__),
            249 => __('NTC1 ALARM', __FILE__),
            250 => __('TC1 ALARM', __FILE__),
            252 => __('Erreur évacuation Fumées', __FILE__),
            253 => __('Pas de pellets', __FILE__)
        ];

        return $lib[$num] ?? $num;
    }

    // methode jour de la semaine
    public static function getWeekDay($num)
    {
        $lib = [
            1 => __('Lundi', __FILE__),
            2 => __('Mardi', __FILE__),
            3 => __('Mercredi', __FILE__),
            4 => __('Jeudi', __FILE__),
            5 => __('Vendredi', __FILE__),
            6 => __('Samedi', __FILE__),
            7 => __('Dimanche', __FILE__)
        ];

        return $lib[$num] ?? __('Jour #', __FILE__) . $num;
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
                } elseif (isset($_options['slider']) && $_options['slider'] != '') {
                    $cmdString = $cmdString . $_options['slider'];
                }
            } else {
                $cmdString = $cmdString . $_options;
            }
            log::add(__CLASS__, 'debug', '(' . __LINE__ . ') ' . __FUNCTION__ . __(' - commande ', __FILE__) . $cmdString);
            log::add(__CLASS__, 'debug', '(' . __LINE__ . ') ' . __FUNCTION__ . __(' - commande ', __FILE__) . json_encode($_options));
        }
        $DATA = $this->makeRequest($cmdString, 6);

        if (!$DATA) {
            return 'ERROR';
        }
        // verification succes du traitement
        if ($DATA->INFO->RSP != 'OK') {
            log::add(__CLASS__, 'error', '(' . __LINE__ . ') ' . __FUNCTION__ . __(' - erreur ', __FILE__) . $CMD . ' : ' . $DATA->INFO->RSP);
            return false;
        }
        // definition patern de comparaison
        $expl = explode('+', $cmdString);
        $pattern = $expl[0] . '+' . $expl[1];

        // traitement suivant commande
        switch ($pattern) {
            case 'CMD+ON':
                // allumage
            case 'CMD+OFF':
                // extinction
            case 'GET+STAT':
                // status
                //"DATA":{"STATUS":0,"LSTATUS":0}}
                $value = $DATA->DATA->STATUS;
                break;
                // nom poele
           case 'GET+ALLS':
                // mise à jour force du feu
                //"DATA":{"MBTYPE":0,"MAC":"XX:XX:XX:XX:XX:XX","MOD":318,"VER":47,"CORE":129,"FWDATE":"2017-05-03","APLTS":"2000-01-07 11:25:14","APLWDAY":7,"CHRSTATUS":0,"STATUS":0,"LSTATUS":0,"SETP":22,"PUMP":0,"PQT":2872,"F1V":0,"F1RPM":0,"F2L":0,"F2LF":0,"FANLMINMAX":[0,5,0,1,0,1],"F2V":0,"PWR":1,"FDR":0,"DPT":0,"DP":67,"IN":13,"OUT":0,"T1":18.5,"T2":22.39999962,"T3":20,"T4":0,"T5":0,"EFLAGS":0}
                $this->checkAndUpdateCmd('IPower', $DATA->DATA->PWR);
                $this->checkAndUpdateCmd('IConsigne', $DATA->DATA->SETP);
                $this->checkAndUpdateCmd('IFan', $DATA->DATA->F2L);
                $this->checkAndUpdateCmd('IFanF3L', $DATA->DATA->F3L);
                $this->checkAndUpdateCmd('IFanF4L', $DATA->DATA->F4L);
                $this->checkAndUpdateCmd('ITemp', round($DATA->DATA->T1, 1));
                $this->checkAndUpdateCmd('ITemp2', round($DATA->DATA->T2, 1));
                $this->checkAndUpdateCmd('ITemp3', round($DATA->DATA->T3, 1));
                $this->checkAndUpdateCmd('IStatus', $DATA->DATA->STATUS);
                $this->checkAndUpdateCmd('IQuantite', $DATA->DATA->PQT);
                $this->checkAndUpdateCmd('ISnap', json_encode($DATA));
                break;
            case 'GET+LABL':
                // nom du palazzeti
            case 'SET+LABL':
                $value = $DATA->DATA->LABEL;
                break;
            case 'GET+POWR':
                // force du feu
                //"DATA":{"PWR":1,"FDR":0}
            case 'SET+POWR':
                //"DATA":{"PWR":1,"F2L":0,"FANLMINMAX":[0,5,0,1,0,1]}}
                $this->checkAndUpdateCmd('IFan', $DATA->DATA->F2L);
                $value = $DATA->DATA->PWR;
                break;
            case 'GET+SETP':
                // température de consigne
            case 'SET+SETP':
                //"DATA":{"SETP":22}}
                $value = $DATA->DATA->SETP;
                break;
            case 'GET+FAND':
                // force du ventilateur
                //"DATA":{"F1V":0,"F2V":0,"F1RPM":0,"F2L":0,"F2LF":0}}
                $value = $DATA->DATA->F2L;
                break;
            case 'SET+RFAN':
                //"DATA":{"PWR":1,"F2L":1,"F2LF":0}
                $this->checkAndUpdateCmd('IPower', $DATA->DATA->PWR);
                $value = $DATA->DATA->F2L;
                break;
            case 'SET+FN3L':
                // force ventilateur F3L
                $value = $DATA->DATA->F3L;
                break;
            case 'SET+FN4L':
                // force ventilateur F4L
                $value = $DATA->DATA->F4L;
                break;
            case 'GET+TMPS':
                // toutes les températures
                $value = $DATA->DATA->T1; //"DATA":{"T1":16.79999924,"T2":17.89999962,"T3":18,"T4":0,"T5":0}}
                $this->checkAndUpdateCmd('ITemp2', $DATA->DATA->T2);
                $this->checkAndUpdateCmd('ITemp3', $DATA->DATA->T3);
                break;
            case 'GET+CHRD':
                // programmes horaires
                $value = json_encode($DATA->DATA);
                break;
            case 'GET+CNTR':
                // tous les compteurs
                //"DATA":{"IGN":263,"POWERTIME":"7280:48","HEATTIME":"1144:20","SERVICETIME":"1144:20","ONTIME":"0:00","OVERTMPERRORS":0,"IGNERRORS":0,"PQT":2371}}%
                $this->checkAndUpdateCmd('INbAllumage', $DATA->DATA->IGN);
                $this->checkAndUpdateCmd('INbAllumageFailed', $DATA->DATA->IGNERRORS);
                $this->checkAndUpdateCmd('IHeuresAlimElec', self::convertTimeToDec($DATA->DATA->POWERTIME));
                $this->checkAndUpdateCmd('IHeuresChauffe', self::convertTimeToDec($DATA->DATA->HEATTIME));
                $this->checkAndUpdateCmd('IHeuresSurChauffe', self::convertTimeToDec($DATA->DATA->OVERTMPERRORS));
                $this->checkAndUpdateCmd('IHeuresDepuisEntretien', self::convertTimeToDec($DATA->DATA->SERVICETIME));
                $this->checkAndUpdateCmd('IQuantite', $DATA->DATA->PQT);
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
            default:
                $value = json_encode($DATA->DATA);
                break;
        }

        // mise a jour variables info
        if ($updateLogicalId = $CMD->getConfiguration('updateLogicalId')) {
            $INFO = $this->getCmd('info', $updateLogicalId);
            if (is_object($INFO)) {
                $INFO->event($value);
                $INFO->save();
            }
            log::add(__CLASS__, 'debug', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . 'response ' . $value);
            log::add(__CLASS__, 'debug', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . 'updatelogicalId ' .  $updateLogicalId . ' = ' . $value);
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

        $replace['#invertFanSpeed#'] = $this->getConfiguration('invertFanSpeed', false);
        $replace['#lastCommunication#'] = $this->getStatus('lastCommunication');

        $heure = $this->getCmd('info', 'ITime');
        $replace['#heure#'] = is_object($heure) ? $heure->execCmd() : '';
        $replace['#heure_id#'] = is_object($heure) ? $heure->getId() : '';

        $temps = $this->getCmd('info', 'ITemp');
        $replace['#temperature#'] = is_object($temps) ? $temps->execCmd() : '';
        $replace['#temperature_id#'] = is_object($temps) ? $temps->getId() : '';
        $replace['#temperature_valueDate#'] = is_object($temps) ? $temps->getValueDate() : '';
        $replace['#temperature_collectDate#'] = is_object($temps) ? $temps->getCollectDate() : '';
        $replace['#temperature_unite#'] = is_object($temps) ? $temps->getUnite() : '';
        $replace['#temperature_display#'] = (is_object($temps) && $temps->getIsVisible()) ? '#temperature_display#' : 'none';

        $temps2 = $this->getCmd('info', 'ITemp2');
        $replace['#temperature2#'] = is_object($temps2) ? $temps2->execCmd() : '';
        $replace['#temperature2_id#'] = is_object($temps2) ? $temps2->getId() : '';
        $replace['#temperature2_valueDate#'] = is_object($temps2) ? $temps2->getValueDate() : '';
        $replace['#temperature2_collectDate#'] = is_object($temps2) ? $temps2->getCollectDate() : '';
        $replace['#temperature2_unite#'] = is_object($temps2) ? $temps2->getUnite() : '';
        $replace['#temperature2_display#'] = (is_object($temps) && $temps2->getIsVisible()) ? '#temperature2_display#' : 'none';

        $temps3 = $this->getCmd('info', 'ITemp3');
        $replace['#temperature3#'] = is_object($temps3) ? $temps3->execCmd() : '';
        $replace['#temperature3_id#'] = is_object($temps3) ? $temps3->getId() : '';
        $replace['#temperature3_valueDate#'] = is_object($temps3) ? $temps3->getValueDate() : '';
        $replace['#temperature3_collectDate#'] = is_object($temps3) ? $temps3->getCollectDate() : '';
        $replace['#temperature3_unite#'] = is_object($temps3) ? $temps3->getUnite() : '';
        $replace['#temperature3_display#'] = (is_object($temps) && $temps3->getIsVisible()) ? '#temperature3_display#' : 'none';

        $status = $this->getCmd('info', 'IStatus');
        $replace['#status#'] = is_object($status) ? $status->execCmd() : '';
        $replace['#status_id#'] = is_object($status) ? $status->getId() : '';

        $consigne = $this->getCmd('info', 'IConsigne');
        $replace['#consigne#'] = is_object($consigne) ? $consigne->execCmd() : '';
        $replace['#consigne_id#'] = is_object($consigne) ? $consigne->getId() : '';
        $replace['#consigne_valueDate#'] = is_object($consigne) ? $consigne->getValueDate() : '';
        $replace['#consigne_collectDate#'] = is_object($consigne) ? $consigne->getCollectDate() : '';
        $replace['#consigne_unite#'] = is_object($consigne) ? $consigne->getUnite() : '';
        $replace['#consigne_minValue#'] = is_object($consigne) ? $consigne->getConfiguration('minValue') : '';
        $replace['#consigne_maxValue#'] = is_object($consigne) ? $consigne->getConfiguration('maxValue') : '';
        $replace['#consigne_display#'] = (is_object($consigne) && $consigne->getIsVisible()) ? '#consigne_display#' : 'none';
        $Wconsigne = $this->getCmd('action', 'WConsigne');
        $replace['#consigneSet_id#'] = is_object($Wconsigne) ? $Wconsigne->getId() : '';
        if (is_object($Wconsigne)) {
            if (is_array($Wconsigne->getDisplay('parameters'))) {
                foreach ($Wconsigne->getDisplay('parameters') as $key => $value) {
                    $replace['#consigne_' . $key . '#'] = $value;
                }
            }
        }

        $power = $this->getCmd('info', 'IPower');
        $replace['#power#'] = is_object($power) ? $power->execCmd() : '';
        $replace['#power_id#'] = is_object($power) ? $power->getId() : '';
        $replace['#power_valueDate#'] = is_object($power) ? $power->getValueDate() : '';
        $replace['#power_collectDate#'] = is_object($power) ? $power->getCollectDate() : '';
        $replace['#power_minValue#'] = is_object($power) ? $power->getConfiguration('minValue') : '';
        $replace['#power_maxValue#'] = is_object($power) ? $power->getConfiguration('maxValue') : '';
        $replace['#power_display#'] = (is_object($power) && $power->getIsVisible()) ? '#power_display#' : 'none';
        $Wpower = $this->getCmd('action', 'Wpower');
        $replace['#powerSet_id#'] = is_object($Wpower) ? $Wpower->getId() : '';
        if (is_object($Wpower)) {
            if (is_array($Wpower->getDisplay('parameters'))) {
                foreach ($Wpower->getDisplay('parameters') as $key => $value) {
                    $replace['#power_' . $key . '#'] = $value;
                }
            }
        }

        $fan = $this->getCmd('info', 'IFan');
        $replace['#fan#'] = is_object($fan) ? $fan->execCmd() : '';
        $replace['#fan_id#'] = is_object($fan) ? $fan->getId() : '';
        $replace['#fan_valueDate#'] = is_object($fan) ? $fan->getValueDate() : '';
        $replace['#fan_collectDate#'] = is_object($fan) ? $fan->getCollectDate() : '';
        $replace['#fan_minValue#'] = is_object($fan) ? $fan->getConfiguration('minValue') : '';
        $replace['#fan_maxValue#'] = is_object($fan) ? $fan->getConfiguration('maxValue') : '';
        $replace['#fan_display#'] = (is_object($fan) && $fan->getIsVisible()) ? '#fan_display#' : 'none';
        $Wfan = $this->getCmd('action', 'WFan');
        $replace['#fanSet_id#'] = is_object($Wfan) ? $Wfan->getId() : '';
        if (is_object($Wfan)) {
            if (is_array($Wfan->getDisplay('parameters'))) {
                foreach ($Wfan->getDisplay('parameters') as $key => $value) {
                    $replace['#fan_' . $key . '#'] = $value;
                }
            }
        }

        $nbAll = $this->getCmd('info', 'INbAllumage');
        $replace['#nbAll#'] = is_object($nbAll) ? $nbAll->execCmd() : '';
        $replace['#nbAll_id#'] = is_object($nbAll) ? $nbAll->getId() : '';
        $replace['#nbAll_unit#'] = is_object($nbAll) ? $nbAll->getUnite() : '';
        $replace['#nbAll_valueDate#'] = is_object($nbAll) ? $nbAll->getValueDate() : '';
        $replace['#nbAll_collectDate#'] = is_object($nbAll) ? $nbAll->getCollectDate() : '';
        $replace['#nbAll_display#'] = (is_object($nbAll) && $nbAll->getIsVisible()) ? '#nbAll_display#' : 'none';

        $hae = $this->getCmd('info', 'IHeuresAlimElec');
        $replace['#hae#'] = is_object($hae) ? $hae->execCmd() : '';
        $replace['#hae_id#'] = is_object($hae) ? $hae->getId() : '';
        $replace['#hae_unit#'] = is_object($hae) ? $hae->getUnite() : '';
        $replace['#hae_valueDate#'] = is_object($hae) ? $hae->getValueDate() : '';
        $replace['#hae_collectDate#'] = is_object($hae) ? $hae->getCollectDate() : '';
        $replace['#hae_display#'] = (is_object($hae) && $hae->getIsVisible()) ? '#hae_display#' : 'none';

        $hc = $this->getCmd('info', 'IHeuresChauffe');
        $replace['#hc#'] = is_object($hc) ? $hc->execCmd() : '';
        $replace['#hc_id#'] = is_object($hc) ? $hc->getId() : '';
        $replace['#hc_unit#'] = is_object($hc) ? $hc->getUnite() : '';
        $replace['#hc_valueDate#'] = is_object($hc) ? $hc->getValueDate() : '';
        $replace['#hc_collectDate#'] = is_object($hc) ? $hc->getCollectDate() : '';
        $replace['#hc_display#'] = (is_object($hc) && $hc->getIsVisible()) ? '#hc_display#' : 'none';

        $hsc = $this->getCmd('info', 'IHeuresSurChauffe');
        $replace['#hsc#'] = is_object($hsc) ? $hsc->execCmd() : '';
        $replace['#hsc_id#'] = is_object($hsc) ? $hsc->getId() : '';
        $replace['#hsc_unit#'] = is_object($hsc) ? $hsc->getUnite() : '';
        $replace['#hsc_valueDate#'] = is_object($hsc) ? $hsc->getValueDate() : '';
        $replace['#hsc_collectDate#'] = is_object($hsc) ? $hsc->getCollectDate() : '';
        $replace['#hsc_display#'] = (is_object($hsc) && $hsc->getIsVisible()) ? '#hsc_display#' : 'none';

        $hde = $this->getCmd('info', 'IHeuresDepuisEntretien');
        $replace['#hde#'] = is_object($hde) ? $hde->execCmd() : '';
        $replace['#hde_id#'] = is_object($hde) ? $hde->getId() : '';
        $replace['#hde_unit#'] = is_object($hde) ? $hde->getUnite() : '';
        $replace['#hde_valueDate#'] = is_object($hde) ? $hde->getValueDate() : '';
        $replace['#hde_collectDate#'] = is_object($hde) ? $hde->getCollectDate() : '';
        $replace['#hde_display#'] = (is_object($hde) && $hde->getIsVisible()) ? '#hde_display#' : 'none';

        $haf = $this->getCmd('info', 'INbAllumageFailed');
        $replace['#haf#'] = is_object($haf) ? $haf->execCmd() : '';
        $replace['#haf_id#'] = is_object($haf) ? $haf->getId() : '';
        $replace['#haf_unit#'] = is_object($haf) ? $haf->getUnite() : '';
        $replace['#haf_valueDate#'] = is_object($haf) ? $haf->getValueDate() : '';
        $replace['#haf_collectDate#'] = is_object($haf) ? $haf->getCollectDate() : '';
        $replace['#haf_display#'] = (is_object($haf) && $haf->getIsVisible()) ? '#haf_display#' : 'none';

        $pqt = $this->getCmd('info', 'IQuantite');
        $replace['#pqt#'] = is_object($pqt) ? $pqt->execCmd() : '';
        $replace['#pqt_id#'] = is_object($pqt) ? $pqt->getId() : '';
        $replace['#pqt_unit#'] = is_object($pqt) ? $pqt->getUnite() : '';
        $replace['#pqt_valueDate#'] = is_object($pqt) ? $pqt->getValueDate() : '';
        $replace['#pqt_collectDate#'] = is_object($pqt) ? $pqt->getCollectDate() : '';
        $replace['#pqt_display#'] = (is_object($pqt) && $pqt->getIsVisible()) ? '#pqt_display#' : 'none';

        $network = $this->getCmd('info', 'INetwork');
        $replace['#network#'] = is_object($network) ? $network->execCmd() : '';
        $replace['#network_id#'] = is_object($network) ? $network->getId() : '';
        $replace['#network_display#'] = (is_object($network) && $network->getIsVisible()) ? '#network_display#' : 'none';

        $WOn = $this->getCmd('action', 'WOn');
        $replace['#on_id#'] = is_object($WOn) ? $WOn->getId() : '';
        $WOff = $this->getCmd('action', 'WOff');
        $replace['#off_id#'] = is_object($WOff) ? $WOff->getId() : '';

        $fanF3L = $this->getCmd('info', 'IFanF3L');
        $replace['#fanF3L#'] = $this->getFanStateF3L($fanF3L->execCmd());
        $WfanF3L = $this->getCmd('action', 'WFanF3L');
        $replace['#fanF3L_id#'] = is_object($WfanF3L) ? $WfanF3L->getId() : '';

        $fanF4L = $this->getCmd('info', 'IFanF4L');
        $replace['#fanF4L#'] = $this->getFanStateF4L($fanF4L->execCmd());
        $WfanF4L = $this->getCmd('action', 'WFanF4L');
        $replace['#fanF4L_id#'] = is_object($WfanF4L) ? $WfanF4L->getId() : '';

        $snap = $this->getCmd('info', 'ISnap');
        $replace['#snap_id#'] = is_object($snap) ? $snap->getId() : '';
        $replace['#snap_display#'] = (is_object($snap) && $snap->getIsVisible()) ? '#snap_display#' : 'none';

        $refresh = $this->getCmd('action', 'refresh');
        $replace['#refresh_id#'] = is_object($refresh) ? $refresh->getId() : '';

        $html = template_replace($replace, getTemplate('core', $version, __CLASS__, __CLASS__));
        $html = translate::exec($html, 'plugins/' . __CLASS__ . '/core/template/' . $version . '/' . __CLASS__ . '.html');
        cache::set('PalazzettiWidget' . $_version . $this->getId(), $html, 0);
        return $html;
    }

    public static function cronDaily()
    {
        $eqLogics = eqLogic::byType(__CLASS__);

        foreach ($eqLogics as $eqLogic) {
            if ($eqLogic->getIsEnable()) {
                // old WirelessPalaControl version
                try {
                    $request_http = new com_http('http://' . $eqLogic->getConfiguration('addressip') . '/ffffffff');
                    $return = $request_http->exec(6, 2);
                } catch (Exception $e) {
                    if ($e->getCode() == 404) {
                        log::add(__CLASS__, 'debug', __FUNCTION__.' - '. $e->getCode() . __(' erreur de connexion : ', __FILE__) . $e->getMessage());
                        //throw $e;
                    }
                    return false;
                }

                $toSave = false;
                $return = json_decode($return);
                log::add(__CLASS__, 'debug', __FUNCTION__ . __(' - resultat : ', __FILE__) . json_encode($return));
                if ($return->sn) {
                    $eqLogic->setConfiguration('serialNumber', $return->sn);
                    $toSave = true;
                }
                if ($return->m) {
                    $eqLogic->setConfiguration('model', $return->m);
                    $toSave = true;
                }
                if ($return->v) {
                    $eqLogic->setConfiguration('versions', $return->v);
                    $toSave = true;
                }
                if ($toSave) {
                    $eqLogic->setConfiguration('isWirelessPalaControl', true);
                    $eqLogic->save();
                    return true;
                }

                // new WirelessPalaControl version
                try {
                    $request_http = new com_http('http://' . $eqLogic->getConfiguration('addressip') . '/gs0');
                    $return = $request_http->exec(6, 2);
                } catch (Exception $e) {
                    if ($e->getCode() == 404) {
                        log::add(__CLASS__, 'debug', __FUNCTION__.' - '. $e->getCode() . __(' erreur de connexion : ', __FILE__) . $e->getMessage());
                        //throw $e;
                    }
                    return false;
                }

                $toSave = false;
                $return = json_decode($return);
                log::add(__CLASS__, 'debug', __FUNCTION__ . __(' - resultat : ', __FILE__) . json_encode($return));
                if ($return->sn) {
                    $eqLogic->setConfiguration('serialNumber', $return->sn);
                    $toSave = true;
                }
                if ($return->model) {
                    $eqLogic->setConfiguration('model', $return->model);
                    $toSave = true;
                }
                if ($return->version) {
                    $eqLogic->setConfiguration('versions', $return->version);
                    $toSave = true;
                }
                if ($toSave) {
                    $eqLogic->setConfiguration('isWirelessPalaControl', true);
                    $eqLogic->save();
                    return true;
                }
            }
        }
    }
    // recuperation automatique des informations
    public function getInformations()
    {
        log::add(__CLASS__, 'debug', __('Début', __FILE__) . ' : ' . __FUNCTION__);

        // recuperation de l'heure
        $DATA = $this->makeRequest('GET+TIME');
        if (!$DATA) {
            log::add(__CLASS__, 'debug', __FUNCTION__ . __(' - cycle interrompu, équipement injoignable', __FILE__));
            return false;
        }
        $this->checkAndUpdateCmd('ITime', json_encode($DATA->DATA));

        // recuperation de toutes les informations réseau
        $DATA = $this->makeRequest('GET+STDT');
        if ($DATA) {
            $this->checkAndUpdateCmd('IName', $DATA->DATA->LABEL);
            $this->checkAndUpdateCmd('INetwork', json_encode($DATA->DATA));
        }

        // recuperation des programmes horaires
        $DATA = $this->makeRequest('GET+CHRD', 6);
        if ($DATA) {
            $this->checkAndUpdateCmd('IPH', json_encode($DATA->DATA));
        }
        sleep(1);
        // recuperation des infos compteurs
        $DATA = $this->makeRequest('GET+CNTR', 6);
        if ($DATA) {
            $this->checkAndUpdateCmd('INbAllumage', $DATA->DATA->IGN);
            $this->checkAndUpdateCmd('INbAllumageFailed', $DATA->DATA->IGNERRORS);
            $this->checkAndUpdateCmd('IHeuresAlimElec', self::convertTimeToDec($DATA->DATA->POWERTIME));
            $this->checkAndUpdateCmd('IHeuresChauffe', self::convertTimeToDec($DATA->DATA->HEATTIME));
            $this->checkAndUpdateCmd('IHeuresSurChauffe', self::convertTimeToDec($DATA->DATA->OVERTMPERRORS));
            $this->checkAndUpdateCmd('IHeuresDepuisEntretien', self::convertTimeToDec($DATA->DATA->SERVICETIME));
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
                $this->checkAndUpdateCmd('IHeuresAlimElec', self::convertTimeToDec($DATA->DATA->ADDR_206A));
            }
            $DATA = $this->makeRequest('EXT+ADRD+2070+1');
            if ($DATA) {
                $this->checkAndUpdateCmd('IHeuresChauffe', self::convertTimeToDec($DATA->DATA->ADDR_2070));
            }
            $DATA = $this->makeRequest('EXT+ADRD+207A+1');
            if ($DATA) {
                $this->checkAndUpdateCmd('IHeuresSurChauffe', self::convertTimeToDec($DATA->DATA->ADDR_207A));
            }
            $DATA = $this->makeRequest('EXT+ADRD+2076+1');
            if ($DATA) {
                $this->checkAndUpdateCmd('IHeuresDepuisEntretien', self::convertTimeToDec($DATA->DATA->ADDR_2076));
            }
        }

        // recuperation de toutes les informations
        $DATA = $this->makeRequest('GET+ALLS', 6);
        if ($DATA) {
            // mise à jour force du feu
            if (isset($DATA->DATA->PWR)) {
                $this->checkAndUpdateCmd('IPower', $DATA->DATA->PWR);
            }
            if (isset($DATA->DATA->SETP)) {
                $this->checkAndUpdateCmd('IConsigne', $DATA->DATA->SETP);
            }
            if (isset($DATA->DATA->F2L)) {
                $this->checkAndUpdateCmd('IFan', $DATA->DATA->F2L);
            }
            if (isset($DATA->DATA->F3L)) {
                $this->checkAndUpdateCmd('IFanF3L', $DATA->DATA->F3L);
            }
            if (isset($DATA->DATA->F4L)) {
                $this->checkAndUpdateCmd('IFanF4L', $DATA->DATA->F4L);
            }
            if (isset($DATA->DATA->T1)) {
                $this->checkAndUpdateCmd('ITemp', round($DATA->DATA->T1, 2));
            }
            if (isset($DATA->DATA->T2)) {
                $this->checkAndUpdateCmd('ITemp2', round($DATA->DATA->T2, 2));
            }
            if (isset($DATA->DATA->T3)) {
                $this->checkAndUpdateCmd('ITemp3', round($DATA->DATA->T3, 2));
            }
            if (isset($DATA->DATA->STATUS)) {
                $this->checkAndUpdateCmd('IStatus', $DATA->DATA->STATUS);
            }
            $this->checkAndUpdateCmd('ISnap', json_encode($DATA));
        }
        log::add(__CLASS__, 'debug', __('Fin', __FILE__) . ' : ' . __FUNCTION__);
        return true;
    }

    public static function convertTimeToDec($_time) {
        $time = explode(':', $_time);
        return floatval($time[0] .'.'. round($time[1]/0.60));
    }
}

class PalazzettiCmd extends cmd
{
    /*     * *************************Attributs******************************
    public static $_widgetPossibility = array('custom' => false);

    /*     * *********************Methode d'instance************************* */

    public function execute($_options = null)
    {
        $eqLogic = $this->getEqLogic();

        log::add('Palazzetti', 'debug', __FUNCTION__ . ' options ' . json_encode($this->getConfiguration('options')));
        log::add('Palazzetti', 'debug', __FUNCTION__ . ' $options ' . json_encode($_options));
        if ($this->getLogicalId('') == 'refresh') {
            $eqLogic->getInformations();
            $eqLogic->refreshWidget();
        } else {
            $return = $eqLogic->sendCommand($this, $_options);
            log::add('Palazzetti', 'debug', __FUNCTION__ . __(' resultat ', __FILE__) . $return);
        }
    }
}
