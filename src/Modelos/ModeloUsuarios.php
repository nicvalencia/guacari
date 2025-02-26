<?php
namespace App\Modelos;

//importa Eloquent para usarlo en el modelo
use Illuminate\Database\Eloquent\Model as Eloquent;

class ModeloUsuarios extends Eloquent
{
   // Define la llave primaria de la tabla usuarios
   protected $primaryKey = 'id';

   // Define el nombre de la tabla 
   protected $table = 'users';

   public $timestamps = true;
   
     // Define los campos que pueden llenarse en la tabla
   protected $fillable = [
       'first_name',
       'last_name',
       'email',
       'password',
       'permissions',
       'last_login'
   ];
 
}