<?php
namespace Hcode;
namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;

class Address extends Model{

  const SESSION_ERROR = "AddressError";

 
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


    if (isset($data['localidade']) && $data['localidade']) {
    	
    	$this->setdesaddress($data['logradouro']);
    	$this->setdescomplement($data['complemento']);
    	$this->setdesdistrict($data['bairro']);
    	$this->setdescity($data['localidade']);
    	$this->setdesstate($data['uf']);
    	$this->setdescountry('Brasil');
    	$this->setdeszipcode($nrcep);
    }

    
 }

 public function save()
 {
     $sql = new Sql();

     $results = $sql->select("CALL sp_addresses_save(:idaddress, :idperson, :desaddress, :desnumber, :descomplement, :descity, :desstate, :descountry, :deszipcode, :desdestrict)", [
          
          ':idaddress'=>$this->getidaddress(),
          ':idperson'=>$this->getidperson(),
          ':desaddress'=>utf8_decode($this->getdesaddress()),
          ':desnumber'=>$this->getdesnumber(),
          ':descomplement'=>utf8_decode($this->getdescomplement()),
          ':descity'=>utf8_decode($this->getdescity()),
          ':desstate'=>utf8_decode($this->getdesstate()),
          ':descountry'=>$this->getdescountry(),
          ':deszipcode'=>$this->getdeszipcode(),
          ':desdistrict'=>utf8_decode($this->getdesdistrict())
         ]);

     if (count($results) > 0) {
       
       $this->setData($results[0]);
     }
    }

     public static function setMsgErro($msg)
  {
      $_SESSION[Address::SESSION_ERROR] = $msg;

  }

  public static function getMsgErro() 
  {

   $msg = (isset( $_SESSION[Address::SESSION_ERROR])) ?  $_SESSION[Address::SESSION_ERROR]: "";

   Address::limpaMsgErro();

   return $msg;

  }  
   // limpa sessao de erro
  public static function limpaMsgErro()
  {

    $_SESSION[Address::SESSION_ERROR] = NULL;
  }

}//ultimo



?>