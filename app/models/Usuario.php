<?php

use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableInterface;

class Usuario extends Eloquent implements UserInterface, RemindableInterface {

	protected $table = 'usuarios';

	protected $hidden = array('password');

	protected $fillable = array(
		'email',
		'password',
		'nombre',
		'apellido',
		'telefono',
		'empresa',
		'ciudad',
		'pais',
		'fb_id',
		'confirmation',
		'newsletter'
	);

	protected $dates = array('last_login');

	public function listas() {
		return $this->hasMany('Lista', 'id_usuario', 'id');
	}

	public function carpetas() {
		return $this->hasMany('Carpeta', 'id_usuario', 'id');
	}

	public function campanias() {
		return $this->hasMany('Campania', 'id_usuario', 'id');
	}

	public function getAuthIdentifier() {
		return $this->getKey();
	}

	public function getAuthPassword() {
		return $this->password;
	}

	public function getRememberToken() {
		return $this->remember_token;
	}

	public function setRememberToken($value) {
		$this->remember_token = $value;
	}

	public function getRememberTokenName() {
		return 'remember_token';
	}

	public function getReminderEmail() {
		return $this->email;
	}

}