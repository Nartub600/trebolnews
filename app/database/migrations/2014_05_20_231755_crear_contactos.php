<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearContactos extends Migration {

	public function up() {
		Schema::create('contactos', function(Blueprint $table) {
			$table->increments('id');
			$table->integer('id_lista')->unsigned();
			$table->string('nombre');
			$table->string('apellido');
			$table->string('email');
			$table->string('puesto');
			$table->string('empresa');
			$table->string('pais');
			$table->timestamps();
			$table->softDeletes();
		});
	}

	public function down() {
		Schema::drop('contactos');
	}

}