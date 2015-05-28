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

/**
 *
 * @package local
 * @subpackage reservasalas
 * @copyright 2014 Francisco García Ralph (francisco.garcia.ralph@gmail.com)
 *            Nicolás Bañados Valladares (nbanados@alumnos.uai.cl)
 *            2015 Martín Améstica Montenegro (maamestica@alumnos.uai.cl)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Libreria del plugin
// Se incluye automaticamente al llamar config.php
// function library, automatically included with by config.php

// Definir aqui las funciones que se usaran en varias paginas
defined ( 'MOODLE_INTERNAL' ) || die ();
require_once (dirname ( dirname ( __FILE__ ) ) . '/../../config.php'); // obligatorio
function viewaction($titlepage) {
	$title = get_string ( $titlepage, 'local_reservasalas' );
	
	$viewaction [] = $PAGE->navbar->add ( $title );
	$viewaction [] = $PAGE->set_title ( $title );
	$viewaction [] = $PAGE->set_heading ( $title );
	
	$viewactionoutput [] = $OUTPUT->header ();
	$viewactionoutput [] = $OUTPUT->heading ( $title );
	
	$result = array (
			$viewaction,
			$viewactionoutput 
	);
	return $result;
}
function tabs() {
	$toprow = array ();
	$toprow [] = new tabobject ( get_string ( 'sites', 'local_reservasalas' ), new moodle_url ( '/local/reservasalas/sedes.php' ), get_string ( 'places', 'local_reservasalas' ) );
	$toprow [] = new tabobject ( get_string ( 'buildings', 'local_reservasalas' ), new moodle_url ( '/local/reservasalas/admin/buildings.php' ), get_string ( 'buildings', 'local_reservasalas' ) );
	$toprow [] = new tabobject ( get_string ( 'studyrooms', 'local_reservasalas' ), new moodle_url ( '/local/reservasalas/admin/rooms.php' ), get_string ( 'rooms', 'local_reservasalas' ) );
	$toprow [] = new tabobject ( get_string ( 'resources', 'local_reservasalas' ), new moodle_url ( '/local/reservasalas/admin/resources.php' ), get_string ( 'resources', 'local_reservasalas' ) );
	
	// $tabtree= tabtree ( $toprow, get_string ( 'resources', 'local_reservasalas' ) );
	return $toprow;
}