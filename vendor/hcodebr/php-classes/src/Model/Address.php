<?php
namespace Hcode;
namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;


class Address extends Model{
 
 public static function getCep($nrcep) //busca o endereço pelo cp no via cep em jsaon
 {

      $nrcep = str_replace("-", "", $nrcep);
      
      $ch = curl_init(); //inicia o processo co curl para pegar o endereço
 
      curl_setopt($ch, CURLOPT_URL, "https://viacep.com.br/ws/$nrcep/json/");

      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //espera o retorno do viacep
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //parametro de autenticação (não estamos utilizando)

      $data = json_decode(curl_exec($ch), true);

      curl_close($ch);

      return $data;

 }

 public function loadFromCep($nrcep)
 {

    $data = Address::getCep($nrcep);


    if (isset($data['logradouro']) && $data['logradouro']) {
    	
    	$this->setdesaddress($data['logradouro']);
    	$this->setdescomplement($data['complemento']);
    	$this->setdesdistrict($data['bairro']);
    	$this->setdescity($data['localidade']);
    	$this->setdesstate($data['uf']);
    	$this->setdescountry($data['Brasil']);
    	$this->setnrzipcode($nrcep);
    }

    
 }

}//ultimo



?>