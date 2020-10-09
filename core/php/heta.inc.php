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

require_once __DIR__  . '/../../../../core/php/core.inc.php';
/*
 * Non obligatoire mais peut être utilisé si vous voulez charger en même temps que votre
 * plugin des librairies externes (ne pas oublier d'adapter plugin_info/info.xml).
 * 
 * 
 */

class hetaResult {
    const ETAT = array('0' => 'Off',
                       '10' => 'Préchauffage',
                       '20' => 'Allumage',
                       '30' => 'Combustion',
                       '40' => 'En attente mode ECO',
                       '50' => 'Refroidissement');

    public $message;   
    public $etat;
    public $data;
    
    // température constatée
    public $temperature;
    
    // valeur de la consigne thermostat
    public $consigne;

    // niveau du réservoir à pellet
    public $pellet;

    // valeur de puissance
    public $puissance;

    // valeur de ventilation
    public $ventilation;
        
    // statistiques de fonctionnement
    public $statistic;
    
    // température des gaz
    public $temperatureInterne;

    function hetaResult($pData) {
        $this->data = $pData;
        $this->message = $pData->message;
        $this->code = $pData->code;
        
        $this->etat = self::ETAT[$pData->controller->status];
        $this->etatId = $pData->controller->status;
        $_temp = $this->getTemperature(1);
        $this->temperature = $_temp->actual;
        $this->consigne = $_temp->set;
        $this->pellet = $this->getFuel(1)->quantity * 100;
        $this->puissance = $pData->controller->power->actualPower;
        $this->ventilation = $this->getFan(1)->speed;
        $this->temperatureInterne = $this->getFoyer();
        $this->statistic = $this->getStatistic();
        $this->data = null;
    }
    
    protected function getFoyer() {
        $sonde7 = $this->getTemperature(7);
        if ($sonde7 != NULL) {
            return $sonde7->actual;
        }
        return $this->getVariableValue(11);
    }

    protected function getStatistic() {
        $result = array(
            tMaintenance =>    $this->data->controller->timeToService + 0,
            nbAllumages =>     $this->data->controller->statistic->igniterStarts,
            tFonctionnement => round($this->data->controller->statistic->uptime / 3600),
            tChauffage =>      round($this->data->controller->statistic->heatingTime / 3600),
            tService =>        round($this->data->controller->statistic->serviceTime / 3600),
            nbSurchauffe =>    $this->data->controller->statistic->overheatings,
            nbErrAllumage =>   $this->data->controller->statistic->misfires
        );
        return $result;
    }

    protected function getTemperature($id) {
        return $this->getElement($this->data->controller->temperatures, $id);
    }

    protected function getFuel($id) {
        return $this->getElement($this->data->controller->fuels, $id);
    }

    protected function getFan($id) {
        return $this->getElement($this->data->controller->fans, $id);
    }

    protected function getVariableValue($id) {
        return $this->getElement($this->data->controller->diagnostic->variables, $id)->value;
    }

    protected function getElement($elts, $id) {
        foreach ($elts as $elt) {
            if ($elt->id == $id) {
                return $elt;
            }
        }
        return NULL;
    }
}

class fumis {
    /*     * *************************Attributs****************************** */
    const FUMISURL = "http://api.fumis.si/v1/status/";
    const APPNAME  = "heta";
    const APIVERSION = "1";

    private $_mac;
    private $_pin;

    /*     * ***********************Methode static*************************** */

    
    /*     * ***********************Constructeur***************************** */
    function fumis($pMac = null, $pPin = null) {
        $this->setMac($pMac);
        $this->setPin($pPin);
    }

    /*     * *********************Méthodes d'instance************************* */
    public function getStatus () {
        $context = $this->_createContext('POST');
        $result = $this->_launch(self::FUMISURL, $context);
        $myHeta = $this->_createResult($result);
        return $myHeta;
    }

    public function setOrder($pOrder) {
        $command = $this->_prepareCommand('order', $pOrder*1);
        return $this->_sendCommand($command);
    }
    
    public function start() {
        $command = $this->_prepareCommand('on');
        return $this->_sendCommand($command);
    }
    
    public function stop() {
        $command = $this->_prepareCommand('off');
        return $this->_sendCommand($command);
    }
    
    protected function _createResult($strData) {
        $inData = json_decode($strData);
        
        $hetaResult = new hetaResult($inData);
        return $hetaResult;
    }
    
    protected function _getMessage($strData) {
        $inData = json_decode($strData);
        
        $message = $inData->message;
        return $message;
    }
    
    protected function _prepareCommand($pCmd, $pValue = null) {
        switch ($pCmd) {
            case 'order':
                $command = array(
                    temperatures => array(array(set => $pValue, id  => 1)),
                    type => 0);
                break;
            case 'on':
                $command = array(
                    command => 2,
                    type => 0);
                break;
            case 'off':
                $command = array(
                    command => 1,
                    type => 0);
                break;
        }
        $request = array(
            unit => array(
                id => $this->_mac,
                type => 0,
                pin => $this->_pin),
            apiVersion => self::APIVERSION,
            controller => $command
        );
        return $request;
    }
    
    protected function _sendCommand($pCommand) {
        $content = array(
            unit => array(
                id => $this->_mac,
                type => 0,
                pin => $this->_pin),
            apiVersion => self::APIVERSION,
            controller => $pCommand
        );
        $context = $this->_createContext('POST', $content);
        $result = $this->_launch(self::FUMISURL, $context);
        $myHeta = $this->_createResult($result);
        return $myHeta;                
    }
    
	protected function _createContext($pMethod, $pContent = null){
        $opts = array(
            'http'=>array(
                'method'=>$pMethod,
                'header'=> array(
                    'Content-type: application/json',
                    'password: '.$this->_pin,
                    'username: '.$this->_mac,
                    'appname: '.self::APPNAME,
                    'Accept-Language: fr-fr',
                )
            )
        );
		if ($pContent !== null){
			$opts['http']['content'] = json_encode($pContent); 
		}
        log::add('heta', 'debug', "Fumis request = ".json_encode($opts));
		return stream_context_create($opts);
	}

    protected function _launch ($pUrl, $pContext){
        if (($stream = file_get_contents(self::FUMISURL, false, $pContext)) !== false) {
            return $stream;
        }
        //$msg = $this->_getMessage($stream);
        log::add('heta', 'error', "Fumis Error");            
        //throw new Exception(__('Erreur d\'appel FUMIS API', __FILE__));
	}

    /*     * **********************Getteur Setteur*************************** */
    public function setMac($pMac) {
        $this->_mac = $pMac;
    }
    public function setPin($pPin) {
        $this->_pin = $pPin;
    }
}
?>