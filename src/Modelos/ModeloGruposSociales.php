<?php
namespace App\Modelos;

//importa Eloquent para usarlo en el modelo
use Illuminate\Database\Eloquent\Model as Eloquent;

class ModeloGruposSociales extends Eloquent
{
   // Define la llave primaria de la tabla usuarios
   protected $primaryKey = 'id';

   // Define el nombre de la tabla 
   protected $table = 'social_groups';

   public $timestamps = false;
   
     // Define los campos que pueden llenarse en la tabla
   protected $fillable = [
       'name',
       'state_id'
   ];
 
}