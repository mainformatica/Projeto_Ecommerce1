<?php 
session_start();

require_once("vendor/autoload.php");

use \Slim\Slim;
use \Hcode\Page;
use \Hcode\PageAdmin;
use \Hcode\Model\User;


$app = new Slim();

$app->config('debug', true);

$app->get('/', function() {
    
    $page = new Page();

    $page->setTpl("index");
});

$app->get('/admin', function() {
    
User::verifyLogin();

    $page = new PageAdmin();

    $page->setTpl("index");

});

$app->get('/admin/login', function() {

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
]);

	$page->setTpl("login");

});

$app->post('/admin/login', function(){

User::login($_POST["login"], $_POST["password"]);

	header("Location: /admin");
	exit;
	
});
//finaliza sessão / sai da pagina
$app->get('/admin/logout', function(){

	User::logout();

	header("Location: /admin/login");
	exit;
});
//rota para abrir pagina usuarios
$app->get("/admin/users", function(){

    User::verifyLogin();

    $users = User::listAll();

	$page = new PageAdmin();

	$page->setTpl("users", array(
        "users"=>$users
	));

});
//rota para criar usuario
$app->get("/admin/users/create", function(){

	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("users-create");

});
//apaga usuario
$app->get("/admin/users/:iduser/delete", function($iduser){

    User::verifyLogin();

    $user = new User();

    $user->get((int)$iduser);

    $user->delete();

    header("Location: /admin/users");
    exit;

    });
//rota para alterar usuario
$app->get("/admin/users/:iduser", function($iduser){

	User::verifyLogin();

	$user = new User();

	$user->get((int)$iduser);

	$page = new PageAdmin();

	$page->setTpl("users-update", array(
"user"=>$user->getValues()
	));

});
//altera usuario
$app->post("/admin/users/create", function(){

    User::verifyLogin();

    $user = new User();

    $_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;

    $user->setData($_POST);

    $user->save();
    
    header("Location: /admin/users");
    exit;

});
//cria usuario
$app->post("/admin/users/:iduser", function($iduser){

    User::verifyLogin();

    $user = new User();

    $_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;

    $user->get((int)$iduser);

    $user->setData($_POST);

    $user->update();

    header("Location: /admin/users");
    exit;

    });

$app->get("/admin/forgot", function(){
    
    $page = new PageAdmin([
		"header"=>false,
		"footer"=>false
]);

	$page->setTpl("forgot");

});

$app->post("/admin/forgot", function(){

	$user = User::getForgot($_POST["email"]);

	header("Location: /admin/forgot/sent");
});

$app->get("/admin/forgot/sent", function(){

      $page = new PageAdmin([
		"header"=>false,
		"footer"=>false
]);

	$page->setTpl("forgot-sent");

});

$app->post("/admin/forgot", function(){

	$user = User::getForgot($_POST["email"]);

	header("Location: /admin/forgot/sent");

});



$app->run();

 ?>