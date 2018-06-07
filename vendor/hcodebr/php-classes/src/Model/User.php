<?php
namespace Hcode;
namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;

class user extends Model{

	const SESSION = "User";
  const SECRET = "MainFo_753951120";
  const ERROR = "UserError";
  const ERROR_REGISTER = "UserErrorRegister";
  const SUCESS = "UserSucess";


  public static function getFromSession()
  {
         $user = new User();

     if (isset($_SESSION[User::SESSION] ) &&  (int)$_SESSION[User::SESSION]['iduser'] > 0) {
               
         $user->setData($_SESSION[User::SESSION]);

     }

     return $user;

  }

  public static function checkLogin($inadmin = true) // verifica se usuario esta logado e como esta logoado
  {
      if (
           !isset($_SESSION[User::SESSION])                              //Verifica a sessão foi definada
           ||
           !$_SESSION[User::SESSION]                                     //Verifica a sessão esta vazia
           ||
           !(int)$_SESSION[User::SESSION]["iduser"] > 0                 // verifica se é admin

      ){//Usuario  Não esta logado
             
             return false;

      } else {
               
            if ($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true) {
                 
                 return true;

                }else if ($inadmin === false) {

                      return true;

                } else {

                  return false;
                }  

      }

  }
	
	public static function login($login, $password)                          //Tela de login
	{

		$sql = new Sql();
         
        $results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b ON a.idperson = b.idperson WHERE a.deslogin = :LOGIN", array(
        ":LOGIN"=>$login
        ));

        if (count($results) === 0)
        {
        	throw new \Exception("Usuário ou senha inválido.");
        } 
        
        $data = $results[0];

       if (password_verify($password, $data["despassword"]) === true)
       {

         $user = new User();

         $data['desperson'] = utf8_encode($data['desperson']);

         $user->setData($data);

         $_SESSION[User::SESSION] = $user->getValues();
        return $user;
       

       }else {

         throw new \Exception("Usuário ou senha invalido.");

         }

	}

	public static function verifyLogin($inadmin = true)
	{

      if (!User::checkLogin($inadmin)) {

           if ($inadmin) {
              header("Location: /admin/login"); //redireciona para a tela de login
            } else {

            header("Location: /login");
      }
        exit;
      } 
      
	}

	public static function logout()    //Destroi a sessão e vai para tela de login
	{

		$_SESSION[User::SESSION] = NULL;
	}

  
  public static function listAll()
  {

    $sql = new Sql;

    return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");

  }

  public function save()
  {
 
    $sql = new Sql();

    $results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array( 
      ":desperson"=>utf8_decode($this->getdesperson()),
      ":deslogin"=>$this->getdeslogin(),
      ":despassword"=>User::getPasswordHash($this->getdespassword()),
      ":desemail"=>$this->getdesemail(),
      ":nrphone"=>$this->getnrphone(),
      ":inadmin"=>$this->getinadmin()
 ));

    $this->setData($results[0]);

  }

  public function get($iduser)
  {
    $sql = new Sql();

$results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser", array(
     "iduser"=>$iduser

));
 
 $data = $results[0];

 $data['desperson'] = utf8_encode($data['desperson']);

$this->setData($results[0]);

}

  public function update()
  {

    $sql = new Sql();

    $results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array( 
      ":iduser"=>$this->getiduser(),
      ":desperson"=>utf8_decode($this->getdesperson()),
      ":deslogin"=>$this->getdeslogin(),
      ":despassword"=>($this->getdespassword()),
      ":desemail"=>$this->getdesemail(),
      ":nrphone"=>$this->getnrphone(),
      ":inadmin"=>$this->getinadmin()
 ));

    $this->setData($results[0]);

  }

  public function delete()
  {

    $sql = new Sql();

    $sql->select("Call sp_users_delete(:iduser)", array (
     "iduser"=>$this->getiduser()

    ));
  }
      //Esqueci  a senha 
  public static function getForgot($email, $inadmin = true)
{
     $sql = new Sql();
     $results = $sql->select("
         SELECT *
         FROM tb_persons a
         INNER JOIN tb_users b USING(idperson)
         WHERE a.desemail = :email;
     ", array(
         ":email"=>$email
     ));
     if (count($results) === 0)
     {
         throw new \Exception("Não foi possível recuperar a senha.");
     }
     else
     {
         $data = $results[0];
         $results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
             ":iduser"=>$data['iduser'],
             ":desip"=>$_SERVER['REMOTE_ADDR']
         ));
         if (count($results2) === 0)
         {
             throw new \Exception("Não foi possível recuperar a senha.");
         }
         else
         {
             $dataRecovery = $results2[0];
             $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
             $code = openssl_encrypt($dataRecovery['idrecovery'], 'aes-256-cbc', User::SECRET, 0, $iv);
             $result = base64_encode($iv.$code);
             if ($inadmin === true) {
                 $link = "http://www.macommerce.com.br/admin/forgot/reset?code=$result";
             } else {
                 $link = "http://www.macommerce.com.br/forgot/reset?code=$result";
             } 
             $mailer = new Mailer($data['desemail'], $data['desperson'], "Redefinir senha  TicketsShow", "forgot", array(
                 "name"=>$data['desperson'],
                 "link"=>$link
             )); 
             $mailer->send();
             return $link;
         }
     }
 }

  public static function validForgotDecrypt($result)
 {
     $result = base64_decode($result);
     $code = mb_substr($result, openssl_cipher_iv_length('aes-256-cbc'), null, '8bit');
     $iv = mb_substr($result, 0, openssl_cipher_iv_length('aes-256-cbc'), '8bit');;
     $idrecovery = openssl_decrypt($code, 'aes-256-cbc', User::SECRET, 0, $iv);
     $sql = new Sql();
     $results = $sql->select("
         SELECT *
         FROM tb_userspasswordsrecoveries a
         INNER JOIN tb_users b USING(iduser)
         INNER JOIN tb_persons c USING(idperson)
         WHERE
         a.idrecovery = :idrecovery
         AND
         a.dtrecovery IS NULL
         AND
         DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();
     ", array(
         ":idrecovery"=>$idrecovery
     ));
     if (count($results) === 0)
     {
         throw new \Exception("Não foi possível recuperar a senha.");
     }
     else
     {
         return $results[0];
     }
 }

  public static function setForgotUsed($idrecovery)
  {

      $sql = new Sql();

      $sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", array(

      "idrecovery"=>$idrecovery

      ));

  }

  public function setPassword($password)
  {

      $sql = new Sql();

      $sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
       ":password"=>$password,
       ":iduser"=>$this->getiduser()

      ));

  }

  public static function setError($msg){

    $_SESSION[User::ERROR] = $msg;
  }

  public static function getError(){

    $msg=(isset($_SESSION[User::ERROR]) && $_SESSION[User::ERROR]) ?$_SESSION[User::ERROR] : '';

    User::clearError();

    return $msg;

  }

  public static function clearError(){

    $_SESSION[User::ERROR] = NULL;
  }

  public static function setSucess($msg){

    $_SESSION[User::SUCESS] = $msg;
  }

  public static function getSucess(){

    $msg=(isset($_SESSION[User::SUCESS]) && $_SESSION[User::SUCESS]) ?$_SESSION[User::SUCESS] : '';

    User::clearSucess();

    return $msg;

  }

  public static function clearSucess(){

    $_SESSION[User::SUCESS] = NULL;
  }

  public static function setErrorRegister($msg){

   $_SESSION[User::ERRROR_REGISTER] = $msg;

  }

  public static function getPasswordHash($password)
  {   

    return password_hash($password, PASSWORD_DEFAULT, [
     'coast'=>12
    ]);

  }

  public static function setRegisterError($msg)
  {
     $_SESSION[User::ERROR_REGISTER] =$msg;
   
  }

  public static function getRegisterError()
  {
     $msg = (isset($_SESSION[User::ERROR_REGISTER]) && $_SESSION[User::ERROR_REGISTER]) ? $_SESSION[User::ERROR_REGISTER] : '';

     User::clearRegisterError();

     return $msg;

  }

  public static function clearRegisterError()
  {

    $_SESSION[User::ERROR_REGISTER] = NULL;

  }

  public static function checkLoginExiste($login)
  {
     $sql = new Sql();

     $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :deslogin", [

         ':deslogin'=>$login
     ]);

     return (count($results) > 0);

  }

  public function getOrders()
  {
      $sql = new Sql();

      $results = $sql->select("
        SELECT * FROM tb_orders a 
        INNER JOIN tb_ordersstatus b USING(idstatus) 
        INNER JOIN tb_carts c USING(idcart)
        INNER JOIN tb_users d ON d.iduser = a.iduser
        INNER JOIN tb_addresses e USING(idaddress)
        INNER JOIN tb_persons f ON f.idperson = d.idperson
        WHERE a.iduser = :iduser ", [

               ':iduser'=>$this->getiduser()
        ]);

      return $results;

  }
  //Paginação dos Usuarios / quantidade de usuarios por pagina. 
  public static function getPage($page = 1, $itemsPerPage = 10)
  {
     $start = ($page - 1 ) * $itemsPerPage;

     $sql = new Sql();

       $results = $sql->select("
       SELECT SQL_CALC_FOUND_ROWS *
       FROM tb_users a 
       INNER JOIN tb_persons b USING(idperson) 
       ORDER BY b.desperson
       LIMIT $start, $itemsPerPage;
       ");
       
       $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal; ");

     return [
              'data'=>($results),
              'total'=>(int)$resultTotal[0]["nrtotal"],
              'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
     ];
    
  }

  public static function getPageSearch($search, $page = 1, $itemsPerPage = 10)
  {
     $start = ($page - 1) * $itemsPerPage;

     $sql = new Sql();

       $results = $sql->select("
       SELECT SQL_CALC_FOUND_ROWS *
       FROM tb_users a 
       INNER JOIN tb_persons b USING(idperson)
       WHERE b.desperson LIKE :search OR b.desemail = :search OR a.deslogin LIKE :search 
       ORDER BY b.desperson
       LIMIT $start, $itemsPerPage;
       ", [
           ':search'=> '%'.$search.'%'

       ]);
       
       $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal; ");

     return [
              'data'=>($results),
              'total'=>(int)$resultTotal[0]["nrtotal"],
              'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
     ];
    
  }

}//ultimo



?>