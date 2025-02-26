<?php
namespace App\Modelos;

//importa Eloquent para usarlo en el modelo
use Illuminate\Database\Eloquent\Model as Eloquent;

class ModeloMenuBot extends Eloquent
{
   // Define la llave primaria de la tabla usuarios
   protected $primaryKey = 'id';

   // Define el nombre de la tabla 
   protected $table = 'main_bot';

   public $timestamps = true;
   
     // Define los campos que pueden llenarse en la tabla
     protected $fillable = [
      'title',
      'description',
      'parent',
      'social_groups_id',      
      'display_order',
      'tipos_data_idtipos_data',
      'name_file',
      'extension_file',
      'mime_type_file',
      'data_file'
  ];
 
}