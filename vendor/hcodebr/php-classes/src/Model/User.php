<?php
namespace Hcode;
namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;
use \Hcode\Model\Cart;

class user extends Model{

	const SESSION = "User";
  const SECRET = "MainFo_753951120";

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

      }else{
               
            if ($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true) {
                 
                 return true;

                }else if ($inadmin === false) {

                      return true;

                } else{

                  return false;
                }  

      }

  }
	
	public static function login($login, $password)                          //Tela de login
	{

		$sql = new Sql();
         
        $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
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

            header("Location: /admin/login"); //redireciona para a tela de login
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
      ":desperson"=>$this->getdesperson(),
      ":deslogin"=>$this->getdeslogin(),
      ":despassword"=>$this->getdespassword(),
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

$this->setData($results[0]);

}

  public function update()
  {

    $sql = new Sql();

    $results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array( 
      ":iduser"=>$this->getiduser(),
      ":desperson"=>$this->getdesperson(),
      ":deslogin"=>$this->getdeslogin(),
      ":despassword"=>$this->getdespassword(),
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

}//ultimo



?>