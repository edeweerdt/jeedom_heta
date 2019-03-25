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
require_once __DIR__  . '/../../../../core/php/core.inc.php';
require_once __DIR__  . '/../../core/php/heta.inc.php';

class heta extends eqLogic {
    /*     * *************************Attributs****************************** */
 
    /*     * ***********************Methode static*************************** */

    /*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom
     */
    public static function cron($_eqLogic_id = null) {
		if ($_eqLogic_id == null) {
			$eqLogics = self::byType('heta', true);
		} else {
			$eqLogics = array(self::byId($_eqLogic_id));
        }
		foreach ($eqLogics as $heta) {
			if ($heta->getIsEnable() == 1) { //vérifie que l'équipement est actif
				$cmd = $heta->getCmd(null, 'refresh');//retourne la commande "refresh" si elle exxiste
				if (!is_object($cmd)) {
				  continue;
				}
				$cmd->execCmd(); // la commande existe on la lance
			}
		}
    }

    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom
     *
	public static function cronHourly($_eqLogic_id = null) {
    }
    */
    
    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom
      public static function cronDaily() {
      }
     */

    /*     * *********************Méthodes d'instance************************* */
    
    public function preInsert() {        
    }

    public function postInsert() {        
    }

    public function preSave() {        
    }

    public function postSave() {
        // INFO Température
        $temperature = $this->getCmd(null, 'temperature');
		if (!is_object($temperature)) {
			$temperature = new hetaCmd();
            $temperature->setTemplate('dashboard','line');
            $temperature->setTemplate('mobile','line');
            $temperature->setIsHistorized(true);
            $temperature->setIsVisible(true);
            $temperature->setUnite('°C');
			$temperature->setName(__('Température', __FILE__));
		}
		$temperature->setEqLogic_id($this->getId());
		$temperature->setLogicalId('temperature');
		$temperature->setDisplay('generic_type', 'THERMOSTAT_TEMPERATURE');
		$temperature->setType('info');
		$temperature->setSubType('numeric');
		$temperature->save();	

        // INFO Consigne de température
        $consigne = $this->getCmd(null, 'consigne');
		if (!is_object($consigne)) {
			$consigne = new hetaCmd();
            $consigne->setIsVisible(0);
            $consigne->setUnite('°C');
			$consigne->setName(__('Consigne', __FILE__));
            $consigne->setConfiguration('historizeMode','none');
		}
        $consigne->setEqLogic_id($this->getId());
		$consigne->setLogicalId('consigne');
		$consigne->setDisplay('generic_type', 'THERMOSTAT_SETPOINT');
		$consigne->setType('info');
		$consigne->setSubType('numeric');
		$consigne->setConfiguration('maxValue', 40);
		$consigne->setConfiguration('minValue', 10);
		$consigne->save();	
		
        // INFO données brutes
        $data = $this->getCmd(null, 'data');
		if (!is_object($data)) {
			$data = new hetaCmd();
            $data->setIsVisible(false);
			$data->setName(__('Data', __FILE__));
		}
		$data->setEqLogic_id($this->getId());
		$data->setLogicalId('data');
		$data->setType('info');
		$data->setSubType('string');
		$data->save();
		
        // INFO état de fonctionnement
        $etat = $this->getCmd(null, 'etat');
		if (!is_object($etat)) {
			$etat = new hetaCmd();
            $etat->setIsVisible(true);
            $etat->setTemplate('dashboard','line');
            $etat->setTemplate('mobile','line');
			$etat->setName(__('Etat', __FILE__));
		}
		$etat->setLogicalId('etat');
		$etat->setEqLogic_id($this->getId());
		$etat->setType('info');
		$etat->setSubType('string');
		$etat->save();

        //INFO numéro de l'état
        $etatId = $this->getCmd(null, 'etatId');
		if (!is_object($etatId)) {
			$etatId = new hetaCmd();
            $etatId->setIsVisible(false);
            $etatId->setTemplate('dashboard','line');
            $etatId->setTemplate('mobile','line');
            $etatId->setName(__('Etat ID', __FILE__));
		}
		$etatId->setLogicalId('etatId');
		$etatId->setEqLogic_id($this->getId());
		$etatId->setType('info');
		$etatId->setSubType('numeric');
		$etatId->save();

		// INFO etat de marche
        $actif = $this->getCmd(null, 'actif');
		if (!is_object($actif)) {
			$actif = new hetaCmd();
			$actif->setName(__('Actif', __FILE__));
			$actif->setIsVisible(0);
			$actif->setIsHistorized(1);
		}
		$actif->setDisplay('generic_type', 'THERMOSTAT_STATE');
		$actif->setEqLogic_id($this->getId());
		$actif->setType('info');
		$actif->setSubType('binary');
		$actif->setLogicalId('actif');
		$actif->save();
        
        // COMMANDE rafraichissement des données
		$refresh = $this->getCmd(null, 'refresh');
		if (!is_object($refresh)) {
			$refresh = new hetaCmd();
			$refresh->setName(__('Rafraichir', __FILE__));
		}
		$refresh->setEqLogic_id($this->getId());
		$refresh->setLogicalId('refresh');
		$refresh->setType('action');
		$refresh->setSubType('other');
		$refresh->save();

        //COMMANDE thermostat
        $thermostat = $this->getCMD(null, 'thermostat');
        if (!is_object($thermostat)) {
            $thermostat = new hetaCmd();
            $thermostat->setTemplate('dashboard','thermostat');
            $thermostat->setTemplate('mobile','thermostat');
            $thermostat->setUnite('°C');
			$thermostat->setName(__('Thermostat', __FILE__));
            $thermostat->setIsVisible(1);
        }
	    $thermostat->setDisplay('generic_type','THERMOSTAT_SET_SETPOINT');
        $thermostat->setEqLogic_id($this->getId());
		$thermostat->setLogicalId('thermostat');
		$thermostat->setType('action');
		$thermostat->setSubType('slider');
        $thermostat->setValue($consigne->getId());
		$thermostat->setConfiguration('maxValue', 40);
		$thermostat->setConfiguration('minValue', 10);
		$thermostat->save();
        
        //COMMANDE arret
		$off = $this->getCmd(null, 'off');
		if (!is_object($off)) {
			$off = new hetaCmd();
			$off->setIsVisible(1);
			$off->setName(__('Off', __FILE__));
		}
		$off->setDisplay('generic_type', 'THERMOSTAT_SET_MODE');
		$off->setEqLogic_id($this->getId());
		$off->setType('action');
		$off->setSubType('other');
		$off->setLogicalId('off');
        $off->setValue($actif->getId());
		$off->save();
        
        //COMMANDE marche
		$on = $this->getCmd(null, 'on');
		if (!is_object($on)) {
			$on = new hetaCmd();
			$on->setIsVisible(1);
			$on->setName(__('On', __FILE__));
		}
		$on->setDisplay('generic_type', 'THERMOSTAT_SET_MODE');
		$on->setEqLogic_id($this->getId());
		$on->setType('action');
		$on->setSubType('other');
		$on->setLogicalId('on');
        $on->setValue($actif->getId());
		$on->save();
    }

    public function preUpdate() {
        if ($this->getConfiguration('mac') == '') {
            throw new Exception(__('L\'adresse Mac ne peut être vide', __FILE__));    
        }
    }

    public function postUpdate() {
 		self::cron($this->getId());// lance la fonction cronHourly avec l’id de l’eqLogic
    }

    public function preRemove() {        
    }

    public function postRemove() {        
    }

    /*
     * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
      public function toHtml($_version = 'dashboard') {

      }
     */

    /*
     * Non obligatoire mais ca permet de déclencher une action après modification de variable de configuration
    public static function postConfig_<Variable>() {
    }
     */

    /*
     * Non obligatoire mais ca permet de déclencher une action avant modification de variable de configuration
    public static function preConfig_<Variable>() {
    }
     */
 
    public function cmdUpdate($pHetaValue) {
    	$this->checkAndUpdateCmd('data', $pHetaValue->data);
        $this->checkAndUpdateCmd('temperature', $pHetaValue->temperature);
        $this->checkAndUpdateCmd('consigne', $pHetaValue->consigne);
        $this->checkAndUpdateCmd('thermostat', $pHetaValue->consigne);
		$this->checkAndUpdateCmd('etatId', $pHetaValue->etatId);
        $this->checkAndUpdateCmd('etat', $pHetaValue->etat);
        if ($pHetaValue->etatId == 0) {
            $actif = 0;
        } else {
            $actif = 1;
        }
        $this->checkAndUpdateCmd('actif', $actif);
    }
    
    public function getHetaStatus() {
        if ($this->getConfiguration('mac') == '') {
            return;
        }
        $mac = $this->getConfiguration("mac");
        $fumis = new fumis($mac);
        $hetaStatus = $fumis->getStatus();
        log::add('heta', 'debug', "Heta [".$mac."] result = ". json_encode($hetaStatus));
        
        $this->cmdUpdate($hetaStatus);
    }
    
    public function setHetaConsigne($pOrder) {
        $fumis = new fumis($this->getConfiguration("mac"));
        $hetaStatus = $fumis->setOrder($pOrder);        
        log::add('heta', 'debug', "Heta [".$this->getConfiguration("mac")."] change order = ". $pOrder);
        return $hetaStatus;
    }
    
    public function setHetaStart() {
        $fumis = new fumis($this->getConfiguration("mac"));
        $fumis->start();
        $status = $this->checkAndUpdateCmd('actif', true);
        log::add('heta', 'debug', "Heta [".$this->getConfiguration("mac")."] start");
        $this->cmdUpdate($status);
    }
    
    public function setHetaStop() {
        $fumis = new fumis($this->getConfiguration("mac"));
        $fumis->stop();
        $status = $this->checkAndUpdateCmd('actif', false);
        log::add('heta', 'debug', "Heta [".$this->getConfiguration("mac")."] stop");
        $this->cmdUpdate($status);
    }
    /*     * **********************Getteur Setteur*************************** */
}

class hetaCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

    public function execute($_options = array()) {
        $eqLogic = $this->getEqLogic();
        $action = $this->getLogicalId();
        log::add('heta','debug','Heta Execute "'.$action.'" with options = '. json_encode($_options));
		switch ($action) {		
			case 'refresh':
                $eqLogic->getHetaStatus();
                $eqLogic->refreshWidget();
                break;
            case 'thermostat':
                if (!isset($_options['slider']) || $_options['slider'] == '' || !is_numeric(intval($_options['slider']))) {
                    return;
                }
                $changed = ($eqLogic->getCmd(null, 'consigne')->execCmd() != $_options['slider']);
                $eqLogic->getCmd(null, 'consigne')->event($_options['slider']);
                if ($changed){
                    $eqLogic->setHetaConsigne($_options['slider']);
                }
                break;
            case 'on':
                $eqLogic->setHetaStart();
                break;
            case 'off':
                $eqLogic->setHetaStop();
                break;
		}
        return;
    }

    /*     * **********************Getteur Setteur*************************** */
}


