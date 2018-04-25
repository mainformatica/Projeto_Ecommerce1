<?php
namespace Hcode;
namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;
use \Hcode\Model\User;
use \Hcode\Model\Products;

class Cart extends Model{

const SESSION = "Cart";
const SESSION_ERROR = "CartError";

  public static function getFromSession()
  {
     
     $cart = new Cart();

     if (isset($_SESSION[Cart::SESSION]) && (int)$_SESSION[Cart::SESSION]['idcart'] > 0)
      {
          $cart->get((int)$_SESSION[Cart::SESSION]['idcart']);
      } else {

        $cart->getFromSessionID();

        if (!(int)$cart->getidcart() > 0) {

             $data = [
                'dessessionid'=>session_id()
             ];

             if (User::checkLogin(false)){
               
              $user = User::getFromSession();

              $data['iduser'] = $user->getiduser();

             }

                $cart->setData($data);

               

                $cart->save();

                $cart->setToSession();             
           }

      }

       return $cart;

  }

  public function setToSession()
  {
    
    $_SESSION[Cart::SESSION] = $this->getValues();


  }

  public function getFromSessionID()
  {
     $sql = new Sql();

     $results = $sql->select("SELECT * FROM tb_carts WHERE dessessionid = :dessessionid" , [
      ':dessessionid'=>session_id()
     ]);

     if (count($results) > 0) {$this->setData($results[0]);
     }

  }

  public function get(int $idcart)
  {
     $sql = new Sql();

     $results = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart" , [
      ':idcart'=>$idcart
     ]);

     if (count($results) > 0) {$this->setData($results[0]);
     }

  }

  public function save() // salvar no carrinho
  {

    $sql = new Sql();

    $results = $sql->select("CALL sp_carts_save(:idcart, :dessessionid, :iduser, :deszipcode, :vlfreight, :nrdays)", [
        ':idcart'=>$this->getidcart(),
        ':dessessionid'=>$this->getdessessionid(),
        ':iduser'=>$this->getiduser(),
        ':deszipcode'=>$this->getdeszipcode(),
        ':vlfreight'=>$this->getvlfreight(),
        ':nrdays'=>$this->getnrdays()
   ]);
    
      $this->setData($results[0]);
  }

  public function addProduct(Products $products){

    $sql = new Sql();

    $sql->query("INSERT INTO tb_cartsproducts (idcart, idproduct) VALUES(:idcart, :idproduct)", [
       ':idcart'=>$this->getidcart(),
       ':idproduct'=>$products->getidproduct()
    ]);

    //metodo para atualizar frete de acordo quantidade de produstos
    $this->getCalculeteTotal();
  }

  public function removeProduct(Products $products, $all = false){

    $sql = new Sql();

    if($all) {

      $sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL", [
         'idcart'=>$this->getidcart(),
         'idproduct'=>$products->getidproduct()
      ]);
    } else {
          $sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL LIMIT 1", [
         'idcart'=>$this->getidcart(),
         'idproduct'=>$products->getidproduct()
      ]);

    }
    //metodo para atualizar frete de acordo quantidade de produstos
    $this->getCalculeteTotal();
  }

  public function getProducts(){

 $sql = new Sql();

 $rows = $sql->select("SELECT b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl, COUNT(*) AS nrqtd, SUM(b.vlprice) AS vltotal 
  FROM tb_cartsproducts a 
  INNER JOIN tb_products b ON a.idproduct = b.idproduct 
  WHERE a.idcart = :idcart AND a.dtremoved IS NULL 
  GROUP BY b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl 
  ORDER BY b.desproduct
   ", [
     ':idcart'=>$this->getidcart()

  ]);

  return Products::checklist($rows);

  }

  public function getProductsTotal(){

        $sql = new Sql();

        $results = $sql->select("SELECT SUM(vlprice) AS vlprice, SUM(vlwidth) AS vlwidth, SUM(vlheight) AS vlheight, SUM(vllength) AS vllength, SUM(vlweight) AS vlweight, COUNT(*) AS nrqtd 
          FROM tb_products a 
          INNER JOIN tb_cartsproducts b ON a.idproduct = b.idproduct 
          WHERE b.idcart = :idcart AND dtremoved IS NULL;
          ",[
              ':idcart'=>$this->getidcart()   

            ]);

        if (count($results) > 0) {
          return $results[0];

        } else {

          return [];

        }
      }
     //metodo de verficação de preco e prazo nos correios
  public function setFreight($nrzicode){
     
     $nrzicode = str_replace('-', '', $nrzicode);

     $totals = $this->getProductsTotal();

     if ($totals['nrqtd'] > 0){ 

      if ($totals['vlheight'] < 2) $totals['vlheight'] = 2;
      if ($totals['vllength'] < 16) $totals['vllength'] = 16;
     
       $qs = http_build_query([
            'nCdEmpresa'=>'',
            'sDsSenha'=>'',
            'nCdServico'=>'40010',
            'sCepOrigem'=>'09932080',
            'sCepDestino'=> $nrzicode,
            'nVlPeso'=>$totals['vlweight'],
            'nCdFormato'=>'1',
            'nVlComprimento'=>$totals['vllength'],
            'nVlAltura'=>$totals['vlheight'],
            'nVlLargura'=>$totals['vlwidth'],
            'nVlDiametro'=>'0',
            'sCdMaoPropria'=>'S',
            'nVlValorDeclarado'=>$totals['vlprice'],
            'sCdAvisoRecebimento'=>'S'
           
        ]);

      $xml = simplexml_load_file("http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?" .$qs);
      
      $result = $xml->Servicos->cServico;

      if ($result->MsgErro != '') {
       
       Cart::setMsgErro($result->MsgErro);


      } else
      {
        Cart::limpaMsgErro();
      }

      $this->setnrdays($result->PrazoEntrega);
      $this->setvlfreight(Cart::formatValueDecimal($result->Valor));
      $this->setdeszipcode($nrzicode);

      $this->save();

      return $result;

     } else {

      $this->setnrdays(0);
     $this->setvlfreight(0);

  }
         

  }
     
      //formata preco para decimal
  public static function formatValueDecimal($value):float
  {
      $value= str_replace('.', '', $value);
      return str_replace(',', '.', $value);
       
  }  
      // mostra mensagem de erro retornando dos correios
  public static function setMsgErro($msg)
  {
      $_SESSION[Cart::SESSION_ERROR] = $msg;

  }

  public static function getMsgErro() 
  {

   $msg = (isset( $_SESSION[Cart::SESSION_ERROR])) ?  $_SESSION[Cart::SESSION_ERROR]: "";

   Cart::limpaMsgErro();

   return $msg;

  }  
   // limpa sessao de erro
  public static function limpaMsgErro()
  {

    $_SESSION[Cart::SESSION_ERROR] = NULL;
  }

  //metodo para atualizar frete de acordo quantidade de produstos
  public function updateFreight()
  {

    if ($this->getdeszipcode() != '') {
      
      $this->setFreight($this->getdeszipcode());
    }
  }

  public function getValues(){

    $this->getCalculeteTotal();

    return parent::getValues();


  }

  public function getCalculeteTotal(){

    $this->updateFreight();

    $totals = $this->getProductsTotal();

    $this->setvlsubtotal($totals['vlprice']);
    $this->setvltotal($totals['vlprice'] + $this->getvlfreight());

  }
  
}//ultimo



?>