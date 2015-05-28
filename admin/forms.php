<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
// a

/**
 *
 * @package local
 * @subpackage reservasalas
 * @copyright 2014 Francisco García Ralph (francisco.garcia.ralph@gmail.com)
 *            Nicolás Bañados Valladares (nbanados@alumnos.uai.cl)
 *            2015 Martín Améstica Montenegro (maamestica@alumnos.uai.cl)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once (dirname ( dirname ( __FILE__ ) ) . '/../../config.php');
require_once ($CFG->libdir . '/formslib.php');
require_once ($CFG->dirroot . '/local/reservasalas/admin/tables.php');
class edit_rooms extends moodleform {
	function definition() {
		global $CFG, $DB;
		$mform = & $this->_form;
		$instance = $this->_customdata;
		$prevaction = $instance ['prevaction'];
		$edificioid = $instance ['edificioid'];
		$idsala = optional_param ( 'idsala', NULL, PARAM_RAW );
		$nombresala = $DB->get_record ( 'reservasalas_salas', array (
				'id' => $idsala 
		) );
		if (empty ( $nombresala )) {
			$nombresala = new stdClass ();
			$nombresala->nombre = "101";
			$nombresala->nombre_pc = "PC 1";
			$nombresala->capacidad = '0';
		}
		
		$resourcesArray = array ();
		$seeResources = $DB->get_records ( 'reservasalas_recursos' );
		
		foreach ( $seeResources as $seeResource ) {
			$nresources = $seeResource->id;
			$checkName = $nresources;
			
			$resourcesArray [] = & $mform->createElement ( 'advcheckbox', $checkName, $seeResource->nombre, $seeResource->nombre . ' ' );
			if ($DB->get_records ( 'reservasalas_salarecursos', array (
					'salas_id' => $idsala,
					'recursos_id' => $seeResource->id 
			) ) != null) {
				
				$mform->setDefault ( $checkName, '1' );
			}
		}
		
		$mform->addElement ( 'text', 'cambiarnombresala', get_string ( 'roomsname', 'local_reservasalas' ) . ': ', array (
				'value' => $nombresala->nombre 
		) );
		$mform->setType ( 'cambiarnombresala', PARAM_TEXT );
		$mform->addRule ( 'cambiarnombresala', get_string ( 'indicateroomname', 'local_reservasalas' ), 'required' );
		$mform->addElement ( 'text', 'cambiarnombrepc', get_string ( 'pcname', 'local_reservasalas' ) . ': ', array (
				'value' => $nombresala->nombre_pc 
		) );
		$mform->setType ( 'cambiarnombrepc', PARAM_TEXT );
		$mform->addRule ( 'cambiarnombrepc', get_string ( 'indicatepcname', 'local_reservasalas' ), 'required' );
		$mform->addElement ( 'hidden', 'action', 'editar' );
		
		$types = array (
				'1' => get_string ( 'class', 'local_reservasalas' ),
				'2' => get_string ( 'study', 'local_reservasalas' ),
				'3' => get_string ( 'reunion', 'local_reservasalas' ) 
		);
		
		$mform->addElement ( 'select', 'roomType', get_string ( 'selectTypeRoom', 'local_reservasalas' ) . ': ', $types );
		$mform->setDefault ( 'roomType', $nombresala->tipo );
		$mform->setType ( 'roomType', PARAM_INT );
		$mform->addElement ( 'text', 'cap', get_string ( 'roomcapacity', 'local_reservasalas' ) . ': ', array (
				'value' => $nombresala->capacidad 
		) );
		$mform->setType ( 'cap', PARAM_INT );
		$mform->AddGroup ( $resourcesArray, '', get_string ( 'resources', 'local_reservasalas' ) . ': ' );
		$mform->setType ( 'action', PARAM_TEXT );
		$mform->addElement ( 'hidden', 'edificio', $edificioid );
		$mform->setType ( 'edificio', PARAM_INT );
		$mform->addElement ( 'hidden', 'prevaction', $prevaction );
		$mform->setType ( 'prevaction', PARAM_TEXT );
		$mform->addElement ( 'hidden', 'idsala', $idsala );
		$mform->setType ( 'idsala', PARAM_TEXT );
		$this->add_action_buttons ();
	}
	function validation($data, $files) {
		global $DB;
		$errors = array ();
		if ($DB->get_records_sql ( 'select * from {reservasalas_salas} where nombre = ? AND edificios_id = ? AND tipo = ? AND id != ?', array (
				$data ['cambiarnombresala'],
				$data ['edificio'],
				$data ['roomType'],
				$data ['idsala'] 
		) )) {
			$errors ['cambiarnombresala'] = '*' . get_string ( 'roomNameExists', 'local_reservasalas' );
		}
		return $errors;
	}
}
class create_rooms extends moodleform {
	function definition() {
		global $CFG, $DB;
		
		$mform = & $this->_form;
		
		$edificios = $DB->get_records ( 'reservasalas_edificios' );
		foreach ( $edificios as $edificio ) {
			$sede = $DB->get_record ( 'reservasalas_sedes', array (
					'id' => $edificio->sedes_id 
			) );
			$sedeedificio [$edificio->id] = $sede->nombre . " - " . $edificio->nombre;
		}
		
		$types = array (
				'1' => get_string ( 'class', 'local_reservasalas' ),
				'2' => get_string ( 'study', 'local_reservasalas' ),
				'3' => get_string ( 'reunion', 'local_reservasalas' ) 
		);
		
		$mform->addElement ( 'select', 'SedeEdificio', get_string ( 'selectabuilding', 'local_reservasalas' ) . ': ', $sedeedificio );
		$mform->setType ( 'SedeEdificio', PARAM_INT );
		$mform->addElement ( 'select', 'roomType', get_string ( 'selectTypeRoom', 'local_reservasalas' ) . ': ', $types );
		$mform->setType ( 'roomType', PARAM_INT );
		$mform->addElement ( 'text', 'sala', get_string ( 'numbersofrooms', 'local_reservasalas' ) . ': ' );
		$mform->setType ( 'sala', PARAM_TEXT );
		$mform->addRule ( 'sala', get_string ( 'indicatenumbersofrooms', 'local_reservasalas' ), 'required' );
		
		$mform->addRule ( 'sala', get_string ( 'indicatenumbersofrooms', 'local_reservasalas' ), 'nonzero' );
		$mform->addElement ( 'hidden', 'action', 'crear' );
		$mform->setType ( 'action', PARAM_TEXT );
		$this->add_action_buttons ( true, get_string ( 'next', 'local_reservasalas' ) );
	}
	function validation($data, $files) {
		$errors = array ();
		$salas = $data ['sala'];
		if (empty ( $data ['sala'] ) || is_null ( $data ['sala'] )) {
			$errors ['sala'] = get_string ( 'enteravalidnumericvalue', 'local_reservasalas' );
		}
		if (! is_number ( $data ['sala'] )) {
			$errors ['sala'] = get_string ( 'enteravalidnumericvalue', 'local_reservasalas' );
		}
		if ($data ['sala'] < 0) {
			$errors ['sala'] = get_string ( 'enteravalidnumericvalue', 'local_reservasalas' );
		}
		return $errors;
	}
}
class add_rooms extends moodleform {
	// Add elements to form
	function definition() {
		Global $DB;
		$mform = & $this->_form;
		$instance = $this->_customdata;
		$numerodesalas = $instance ['sala'];
		$roomtype = $instance ['type'];
		$edificioid = $instance ['SedeEdificio'];
		
		if ($edificio = $DB->get_record ( "reservasalas_edificios", array (
				'id' => $edificioid 
		) )) {
			
			$sede = $DB->get_record ( 'reservasalas_sedes', array (
					'id' => $edificio->sedes_id 
			) );
			$mform->addElement ( 'hidden', 'sede', $sede->id );
			$mform->setType ( 'sede', PARAM_INT );
		}
		
		$seeResources = $DB->get_records ( 'reservasalas_recursos' );
		$mform->addElement ( 'hidden', 'edificio', $edificioid );
		$mform->setType ( 'edificio', PARAM_INT );
		$mform->addElement ( 'hidden', 'nombreedificio', $edificio->nombre ); // nombre del edifcio
		$mform->setType ( 'nombreedificio', PARAM_TEXT );
		$mform->addElement ( 'hidden', 'nombresede', $sede->nombre ); // nombre de la sede
		$mform->setType ( 'nombresede', PARAM_TEXT );
		// $mform->addElement('hidden','numero',$instance['sala']);
		// $mform->setType('numero', PARAM_INT);
		
		for($i = 0; $i < $numerodesalas; $i ++) {
			$resourcesArray = array ();
			foreach ( $seeResources as $seeResource ) {
				$nresources = $seeResource->id;
				$checkName = $i . $nresources;
				$resourcesArray [] = & $mform->createElement ( 'advcheckbox', $checkName, $seeResource->nombre, $seeResource->nombre . ' ', array (
						'group' => 1 
				), array (
						0,
						1 
				) );
			}
			$nsala = strval ( "sala$i" );
			$npc = "pc$i";
			$ntype = "cap$i";
			$nres = "res$i";
			$value = 101 + $i;
			$mform->addElement ( 'text', $nsala, get_string ( 'roomsname', 'local_reservasalas' ), array (
					'value' => $value 
			) ); // ***
			$mform->addRule ( $nsala, get_string ( 'roomsname', 'local_reservasalas' ), 'required' ); // *******
			$mform->setType ( $nsala, PARAM_INT );
			$mform->addElement ( 'text', $ntype, get_string ( 'roomcapacity', 'local_reservasalas' ) . ': ' );
			$mform->setType ( $ntype, PARAM_INT );
			$mform->setDefault ( $ntype, 0 );
			$mform->AddGroup ( $resourcesArray, '', get_string ( 'resources', 'local_reservasalas' ) . ': ' );
			$mform->addElement ( 'text', $npc, get_string ( 'pcname', 'local_reservasalas' ) . ': ', array (
					'value' => 'Pc de Sala ' . ($i + 1) 
			) );
			$mform->setType ( $npc, PARAM_INT );
			$mform->addElement ( 'static' ); // para crear un espacio entre los pc :)
		}
		$mform->addElement ( 'hidden', 'typeRoom', $roomtype );
		$mform->setType ( 'typeRoom', PARAM_INT );
		$mform->addElement ( 'hidden', 'number', $numerodesalas );
		$mform->setType ( 'number', PARAM_INT );
		$mform->addElement ( 'hidden', 'action', 'agregar' );
		$mform->setType ( 'action', PARAM_TEXT );
		$this->add_action_buttons ( true, get_string ( 'roomscreates', 'local_reservasalas' ) );
	}
	function validation($data, $files) {
		/*
		 * global $DB;
		 * $errors=array();
		 *
		 * for($i=0;$i<$data['number'];$i++){
		 * $nsala =strval("sala$i");
		 * //$aux=101+$i;
		 * if($DB->get_record('reservasalas_salas', array('edificios_id'=>$data['edificio'],'nombre'=>$data[$nsala],'tipo'=>$data['typeRoom']))){
		 * $errors[$nsala]= "Este nombre ya existe en el Edficio";
		 * }
		 * }
		 * return $errors;
		 * /*
		 * $j=0;
		 * $todaslassalas=$DB->get_records('reservasalas_salas');
		 * foreach ($todaslassalas as $sala){
		 * $nsala="sala".$j;
		 * if($sala->nombre == $data[$nsala]){
		 * $errors[$nsala]= "Este nombre ya existe en el Edficio";
		 * }
		 * $j++;
		 * }
		 *
		 *
		 * return $errors;
		 *
		 * /*global $DB;
		 *
		 * $errors = array();
		 * for($i=0; $i<$data['number']; $i++){
		 * $nsala = 101+$i;
		 * if($DB->get_record('reservasalas_salas', Array('edificios_id'=>$data['edificio'],'nombre'=>$data[$nsala], 'tipo'=>$data['typeRoom']))){
		 * $errors[$nsala] = "*Sala no creada debido a que el nombre ya fue utilizado en esta sede";
		 * }
		 * }
		 * return $errors;
		 */
	}
}
class edit_buildings extends moodleform {
	// Add elements to form
	function definition() {
		global $CFG, $DB;
		
		$mform = & $this->_form; // Don't forget the underscore!
		$instance = $this->_customdata;
		$buildingid = $instance ['idbuilding'];
		$prevaction = $instance ['prevaction'];
		$name = $instance ['buildingname'];
		$modules = $instance ['modules'];
		$places = $instance ['place'];
		$idres = optional_param ( 'edificio', NULL, PARAM_RAW );
		$moduleforline = implode ( '', $modules );
		$buildingplace = $DB->get_record ( 'reservasalas_edificios', array (
				'id' => $buildingid 
		) );
		$placename = $DB->get_record ( 'reservasalas_sedes', array (
				'id' => $buildingplace->sedes_id 
		) );
		
		$mform->addElement ( 'select', 'sede', get_string ( 'campus', 'local_reservasalas' ) . ': ', $places ); // agrego el select para las sedes
		$mform->setDefault ( 'sede', $placename->id );
		$mform->setType ( 'sede', PARAM_INT );
		$mform->addElement ( 'text', 'edificio', get_string ( 'newbuildingname', 'local_reservasalas' ) . ': ', array (
				'value' => $name 
		) ); // Agregar nuevos edificos
		$mform->setType ( 'edificio', PARAM_TEXT );
		$mform->addElement ( 'hidden', 'testbuilding', $name );
		$mform->setType ( 'testbuilding', PARAM_TEXT );
		$mform->addRule ( 'edificio', get_string ( 'indicatenametobuilding', 'local_reservasalas' ) . ': ', 'required' );
		$mform->addElement ( 'textarea', 'modules', get_string ( 'modules', 'local_reservasalas' ) . ': ' );
		$mform->setDefault ( 'modules', $moduleforline );
		$mform->setType ( 'modules', PARAM_TEXT );
		$mform->addRule ( 'modules', get_string ( 'indicatemodules', 'local_reservasalas' ), 'required' );
		$mform->addElement ( 'static', 'rule', get_string ( 'modulerule', 'local_reservasalas' ) . ': ' );
		$mform->addElement ( 'static', 'condition', get_string ( 'modulecondition', 'local_reservasalas' ) );
		$mform->addElement ( 'hidden', 'action', 'editar' );
		$mform->setType ( 'action', PARAM_TEXT );
		$mform->addElement ( 'hidden', 'buildingid', $buildingid );
		$mform->setType ( 'buildingid', PARAM_INT );
		$mform->addElement ( 'hidden', 'prevaction', $prevaction );
		$mform->setType ( 'prevaction', PARAM_INT );
		$this->add_action_buttons ( true, get_string ( 'changebuilding', 'local_reservasalas' ) );
	}
	function validation($data, $files) {
		global $DB;
		
		$errors = array ();
		$buildings = $DB->get_records ( 'reservasalas_edificios', array (
				'sedes_id' => $data ['sede'] 
		) );
		foreach ( $buildings as $building ) {
			if ($data ['edificio'] != $data ['testbuilding'] && $building->nombre == $data ['edificio']) {
				$errors ['edificio'] = '*' . get_string ( 'buildingExists', 'local_reservasalas' );
			}
		}
		
		$line = '';
		$linearray = array ();
		$linestring = '';
		$explode = $data ['modules'];
		$modulesArray = array ();
		$modulesArray = explode ( '#', $explode );
		$steps = array ();
		foreach ( $modulesArray as $moduleArray ) {
			$steps [] = $moduleArray;
		}
		foreach ( $steps as $step ) {
			if ($step) {
				$string = explode ( ',', $step );
				$time = explode ( '-', $string [1] );
				$moduleName = $string [0];
				$start_module = $time [0];
				$end_module = $time [1];
				$line ++;
				
				if (empty ( $moduleName ) || empty ( $start_module ) || empty ( $end_module )) {
					
					$linearray [] = $line ++;
					$linestring = implode ( ', ', $linearray );
					$errors ['modules'] = get_string ( 'checkthestructure', 'local_reservasalas' ) . $linestring . get_string ( 'usethereference', 'local_reservasalas' );
				}
			}
		}
		
		$linearray = array ();
		$line = '';
		return $errors;
	}
}
class create_building extends moodleform {
	function definition() {
		global $CFG, $DB;
		
		$mform = & $this->_form;
		$sedes = $DB->get_records ( 'reservasalas_sedes' ); // obtengo las sedes
		$sedesArray = array ();
		foreach ( $sedes as $key => $value ) { // recorro las sedes para crear el array
			$sedesArray [$value->id] = $value->nombre;
		}
		$mform->addElement ( 'select', 'sede', get_string ( 'campus', 'local_reservasalas' ) . ': ', $sedesArray ); // agrego el select para las sedes
		$mform->addElement ( 'text', 'edificio', get_string ( 'newbuilding', 'local_reservasalas' ) . ': ' ); // Agregar nuevos edificos
		$mform->setType ( 'edificio', PARAM_TEXT );
		$mform->addRule ( 'edificio', get_string ( 'indicatenametobuilding', 'local_reservasalas' ), 'required' );
		$mform->addElement ( 'textarea', 'modules', get_string ( 'modules', 'local_reservasalas' ) . ': ' );
		$mform->setType ( 'modules', PARAM_TEXT );
		$mform->addRule ( 'modules', get_string ( 'indicatemodules', 'local_reservasalas' ), 'required' );
		$mform->addElement ( 'static', 'rule', get_string ( 'modulerule', 'local_reservasalas' ) );
		$mform->addElement ( 'static', 'condition', get_string ( 'modulecondition', 'local_reservasalas' ) );
		$mform->addElement ( 'hidden', 'action', 'crear' );
		$mform->setType ( 'action', PARAM_TEXT );
		$this->add_action_buttons ( true, get_string ( 'createnewbuilding', 'local_reservasalas' ) );
	}
	function validation($data, $files) {
		global $DB;
		$errors = array ();
		if ($DB->get_records ( 'reservasalas_edificios', array (
				'nombre' => $data ['edificio'],
				'sedes_id' => $data ['sede'] 
		) )) {
			$errors ['edificio'] = '*' . get_string ( 'buildingExists', 'local_reservasalas' );
		}
		
		$line = '';
		$linearray = array ();
		$linestring = '';
		$explode = $data ['modules'];
		$modulesArray = array ();
		$modulesArray = explode ( '#', $explode );
		$steps = array ();
		foreach ( $modulesArray as $moduleArray ) {
			$steps [] = $moduleArray;
		}
		foreach ( $steps as $step ) {
			if ($step) {
				$string = explode ( ',', $step );
				$time = explode ( '-', $string [1] );
				$moduleName = $string [0];
				$start_module = $time [0];
				$end_module = $time [1];
				$line ++;
				
				if (empty ( $moduleName ) || empty ( $start_module ) || empty ( $end_module )) {
					
					$linearray [] = $line ++;
					$linestring = implode ( ', ', $linearray );
					$errors ['modules'] = get_string ( 'checkthestructure', 'local_reservasalas' ) . $linestring . get_string ( 'usethereference', 'local_reservasalas' );
				}
			}
		}
		$linearray = array ();
		$line = '';
		return $errors;
	}
}
class edit_campus extends moodleform {
	// Add elements to form
	function definition() {
		global $CFG;
		
		$mform = & $this->_form; // Don't forget the underscore!
		$instance = $this->_customdata;
		$placeid = $instance ['idplace'];
		$prevaction = $instance ['prevaction'];
		$name = $instance ['placename'];
		$idres = optional_param ( 'idsede', NULL, PARAM_RAW );
		
		$mform->addElement ( 'text', 'place', get_string ( 'campusname', 'local_reservasalas' ) . ': ', array (
				'value' => $name 
		) );
		$mform->setType ( 'place', PARAM_TEXT );
		$mform->addRule ( 'place', get_string ( 'indicatecampus', 'local_reservasalas' ), 'required' );
		$mform->addElement ( 'hidden', 'action', 'editar' );
		$mform->setType ( 'action', PARAM_TEXT );
		$mform->addElement ( 'hidden', 'idplace', $placeid );
		$mform->setType ( 'idplace', PARAM_INT );
		$mform->addElement ( 'hidden', 'prevaction', $prevaction );
		$mform->setType ( 'prevaction', PARAM_TEXT );
		
		$this->add_action_buttons ( true, get_string ( 'changecampusname', 'local_reservasalas' ) );
	}
	function validation($data, $files) {
		global $DB;
		$errors = array ();
		$recuperarsede = $DB->get_records ( 'reservasalas_sedes', array (
				'nombre' => $data ['place'] 
		) );
		if (! empty ( $recuperarsede )) {
			$errors ['place'] = get_string ( 'thenameexists', 'local_reservasalas' );
		}
		return $errors;
	}
}
class create_campus extends moodleform {
	// Add elements to form
	function definition() {
		global $CFG, $DB;
		
		$mform = & $this->_form; // Don't forget the underscore!
		
		$mform->addElement ( 'text', 'sede', get_string ( 'newcampus', 'local_reservasalas' ) . ': ' );
		$mform->setType ( 'sede', PARAM_TEXT );
		$mform->addRule ( 'sede', get_string ( 'thenameexists', 'local_reservasalas' ), 'required' );
		$mform->addElement ( 'hidden', 'action', 'crear' );
		$mform->setType ( 'action', PARAM_TEXT );
		
		$this->add_action_buttons ( true, get_string ( 'campuscreate', 'local_reservasalas' ) );
	}
	function validation($data, $files) {
		global $DB;
		$errors = array ();
		$recuperarsede = $DB->get_record ( 'reservasalas_sedes', array (
				'nombre' => $data ['sede'] 
		) );
		if (! empty ( $recuperarsede )) {
			$errors ['sede'] = get_string ( 'thenameexists', 'local_reservasalas' );
		}
		return $errors;
	}
}
class edit_resources extends moodleform {
	// Add elements to form
	function definition() {
		global $CFG;
		
		$mform = & $this->_form; // Don't forget the underscore!
		$instance = $this->_customdata;
		$idresource = $instance ['idresource'];
		$prevaction = $instance ['prevaction'];
		$name = $instance ['resourcename'];
		$idres = optional_param ( 'idresource', NULL, PARAM_RAW );
		
		$mform->addElement ( 'text', 'resource', get_string ( 'resourcename', 'local_reservasalas' ) . ': ', array (
				'value' => $name 
		) );
		$mform->setType ( 'resource', PARAM_TEXT );
		$mform->addRule ( 'resource', get_string ( 'indicateresource', 'local_reservasalas' ) . ': ', 'required' );
		$mform->addElement ( 'hidden', 'action', 'editar' );
		$mform->setType ( 'action', PARAM_TEXT );
		$mform->addElement ( 'hidden', 'idresource', $idresource );
		$mform->setType ( 'idresource', PARAM_INT );
		$mform->addElement ( 'hidden', 'prevaction', $prevaction );
		$mform->setType ( 'prevaction', PARAM_TEXT );
		$mform->addElement ( 'hidden', 'idres', $idres );
		$mform->setType ( 'idres', PARAM_INT );
		$this->add_action_buttons ( true, get_string ( 'changeresource', 'local_reservasalas' ) );
	}
	function validation($data, $files) {
		global $DB;
		$errors = array ();
		$buscarrecursoexistente = $DB->get_records ( 'reservasalas_recursos', array (
				'nombre' => $data ['resource'] 
		) );
		if (! empty ( $buscarrecursoexistente )) {
			$errors ['resource'] = get_string ( 'thenameexists', 'local_reservasalas' );
		}
		return $errors;
	}
}
class create_resources extends moodleform {
	// Add elements to form
	function definition() {
		global $CFG;
		
		$mform = & $this->_form; // Don't forget the underscore!
		$mform->addElement ( 'text', 'resource', get_string ( 'newresource', 'local_reservasalas' ) . ': ' );
		$mform->setType ( 'resource', PARAM_TEXT );
		$mform->addRule ( 'resource', get_string ( 'indicateresource', 'local_reservasalas' ), 'required' );
		$mform->addElement ( 'hidden', 'action', 'crear' );
		$mform->setType ( 'action', PARAM_TEXT );
		$this->add_action_buttons ( true, get_string ( 'createresource', 'local_reservasalas' ) );
	}
	function validation($data, $files) {
		global $DB;
		$errors = array ();
		$nombredelrecurso = $DB->get_records ( 'reservasalas_recursos', array (
				'nombre' => $data ['resource'] 
		) );
		if (! empty ( $nombredelrecurso )) {
			$errors ['resource'] = get_string ( 'theresourceexist', 'local_reservasalas' );
		}
		return $errors;
	}
}
