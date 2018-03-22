<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class user extends Model{

	const SESSION = "User";
  const SECRET = "MainFo_753951120";
	
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

      if(
           !isset($_SESSION[User::SESSION])                              //Verifica a sessão foi definada
           ||
           !$_SESSION[User::SESSION]
           ||
           !(int)$_SESSION[User::SESSION]["iduser"] > 0                 //verifica se o id de usuario é valido
           ||
           (bool)$_SESSION[User::SESSION]["inadmin"] !== $inadmin       //Verifica se o usuario é admin
      ){


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

  public static function getForgot($email) //metodo para recuperar a senha de usuarios
  {
     $sql = new Sql();

     $results = $sql->select("SELECT * FROM tb_persons a INNER JOIN tb_users b USING(idperson) WHERE a.desemail = :email; ", array (
        ":email"=>$email

     ));
       
     if (count($results) === 0)
     {

      throw new \Exception("Não foi possivel recuperar a senha.");
      
     } else
     {
        $data = $results[0];
        $results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
             ":iduser"=>$data["iduser"],
             ":desip"=>$_SERVER["REMOTE_ADDR"]
        ));
         
        if(count($results2) === 0)
        {

        throw new \Exception("Não foi possivel recuperar a senha");
          
        } else

        {
           
           $dataRecovery = $results2[0];

           $code = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, User::SECRET, $dataRecovery["idrecovery"], MCRYPT_MODE_ECB));


           $link = "http://www.macommerce.com.br/admin/forgot/reset?code=$code";

           $mail = new Mailer($data["desemail"], $data["desperson"], "Redefinir senha do TicketsShow", "forgot", array(
            "name"=>$data["desperson"], 
            "link"=>$ink 
          ));
            
           $mailer->send();

           return $data;

        }

     }

  }

}//ultimo



?>