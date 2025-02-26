<?php
namespace App\Modelos;

//importa Eloquent para usarlo en el modelo
use Illuminate\Database\Eloquent\Model as Eloquent;

class ModeloMenu extends Eloquent
{
   // Define la llave primaria de la tabla usuarios
   protected $primaryKey = 'idmenus';

   // Define el nombre de la tabla 
   protected $table = 'menu_aplicacion';

   public $timestamps = false;
   
     // Define los campos que pueden llenarse en la tabla
     protected $fillable = [
      'title',
      'pid',
      'parent',
      'icon_class',
      'url',
      'view'
  ];
 
}