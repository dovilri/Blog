<?php

require './router.php';
require './slugifier.php';
require_once './database.php';
require_once './functions.php';
session_start();

$method = $_SERVER["REQUEST_METHOD"];
$parsed = parse_url($_SERVER['REQUEST_URI']);
$path = $parsed['path'];

// Define the routes
$routes = [

    // [method, routes, handlerFunction],
    ['GET', '/', 'HomeHandler'],
    
    // New article
    ['GET', '/add', 'CreateHandler'],
    ['POST', '/addServer', 'CreateHandlerServer'],

    // Edit article
    ['GET', '/edit/{ArticleName}', 'UpdateHandler'],
    ['POST', '/editServer/{ArticleName}', 'UpdateHandlerServer'],

    // Delete article
    ['GET', '/delete/{ArticleName}', 'DeleteHandler'],
    ['POST', '/deleteServer/{ArticleName}', 'DeleteHandlerServer'],

    // Login
    ['GET', '/login', 'LoginHandler'],
    ['POST', '/loginServer', 'LoginHandlerServer'],

    // Register
    ['GET', '/register', 'RegisterHandler'],
    ['POST', '/registerServer', 'RegisterHandlerServer'],

    // Logout
    ['GET', '/logout', 'LogoutHandler'],

    // View article
    ['GET', '/{ArticleName}', 'ViewHandler'],

];

//  Initialize the routes
$dispatch = registerRoutes($routes);
$matchedRoute = $dispatch($method, $path);
$handlerFunction = $matchedRoute['handler'];
$handlerFunction($matchedRoute['vars']);

// Declaring the handler functions
function HomeHandler()
{
    global $db;
    $articles = $db->run('SELECT * FROM articles')->fetchAll();
    $New_Articles_Array = [];

    foreach($articles as $article){
        $GetUsername = 'SELECT * FROM users WHERE id = ?';
        $userid = $article['creator_id'];
        $article['creator']  = $db -> run($GetUsername , [$userid]) -> fetch()['username'];
        $article['created_at'] = date('Y/m/d H:i', $article['created_at']); 
        array_push($New_Articles_Array, $article);
    }

    $home = render("Home.phtml", ['articles' => $New_Articles_Array]);
    echo render("wrapper.phtml", [ 'inner' => $home, 'site' => 'home' ]);
}

function ViewHandler($vars)
{
    global $db;
    $ArticleName = $vars['ArticleName'];
    $GetArticle = 'SELECT * FROM articles WHERE slug = ?';
    $article = $db->run($GetArticle, [$ArticleName])->fetch();
    if($article == null){
        header('Location: /');
        exit;
    }

    $GetUsername = 'SELECT * FROM users WHERE id = ?';
    $userid = $article['creator_id'];
    $article['creator']  = $db -> run($GetUsername , [$userid]) -> fetch()['username'];
    $article['created_at'] = date('m/d/Y H:i', $article['created_at']); 
    $article['modified_at'] = date('m/d/Y H:i', $article['modified_at']); 

    $articlepage = render("ReadArticle.phtml", ['article' => $article]);
    echo render("wrapper.phtml", [ 'inner' => $articlepage, 'site' => 'readarticle' ]);
}

function CreateHandler()
{
    if( !isLoggedin() )
    {
        header('Location: /');
        exit;
    }

    $Create = render('Create.phtml');
    echo render("wrapper.phtml", [ 'inner' => $Create , 'site' => 'create' ]);
    
}

function CreateHandlerServer()
{
    if( !isLoggedin() )
    {
        header('Location: /');
        exit;
    }
    global $db;

    $imageid = GetImageId();
    if(gettype($imageid) == 'array'){
        $validation = $imageid;
        $_SESSION['validation'] = $validation;
        header('Location: /add');
        exit;
    }

    $title = $_POST['title'] ? cleaner($_POST['title']) : '';
    $introduction = $_POST['introduction'] ? cleaner($_POST['introduction']): '';
    $slug = slugify($title) . "-" . uniqid();
    $content = $_POST['content'] ?? '';
    $picture = $imageid;
    $creator_id = $_SESSION['userid'];
    $created_at = time();
    $modified_at = $created_at;

    if(empty($title) || empty($introduction) || empty($content)){
        array_push($validation, 'Something is missing.');
        $_SESSION['validation'] = $validation;
        header('Location: /add');
        exit;
    }

    $insertsql = 'INSERT INTO articles (title, introduction, slug, content, picture, creator_id, created_at, modified_at) VALUES(? , ? , ? , ? , ? , ? , ? , ?)';
    
    try {
        $db->run($insertsql, [$title, $introduction, $slug, $content, $picture, $creator_id, $created_at, $modified_at]);
    }
    catch(Exception $e) {
        $validation = [];
        array_push($validation, 'Error');
        $_SESSION['validation'] = $validation;
        header('Location: /add');
        exit;
    }

    $_SESSION['success'] = 'Created successfully';
    header('Location: /add');
    exit;

    
}


function UpdateHandler($vars)
{
    if( !isLoggedin() )
    {
        header('Location: /');
        exit;
    }

    global $db;

    $ArticleName = $vars['ArticleName'];
    $GetArticle = 'SELECT * FROM articles WHERE slug = ?';
    $article = $db->run($GetArticle, [$ArticleName])->fetch();
    if($article == null){
        header('Location: /');
        exit;
    }

    $article['created_at'] = date('m/d/Y H:i', $article['created_at']); 
    $article['modified_at'] = date('m/d/Y H:i', $article['modified_at']); 

    $Update = render('Update.phtml', ['article' => $article]);
    echo render("wrapper.phtml", [ 'inner' => $Update , 'site' => 'update' ]);
}

function UpdateHandlerServer($vars)
{
    if( !isLoggedin() )
    {
        header('Location: /');
        exit;
    }
    global $db;
    $ArticleName = $vars['ArticleName'];


    if($_FILES['uploadimage']['error'] == 0){

        $imageid = GetImageId();
        if(gettype($imageid) == 'array'){
            $validation = $imageid;
            $_SESSION['validation'] = $validation;
            header('Location: /edit/' . $ArticleName);
            exit;
        }
    
    }
    if($_FILES['uploadimage']['error'] == 4){

        $GetImageFromDB = 'SELECT * FROM articles WHERE slug = ?';
        $imageid = $db->run($GetImageFromDB, [$ArticleName])->fetch()['picture'];

    }
   

    $title = $_POST['title'] ? cleaner($_POST['title']) : '';
    $introduction = $_POST['introduction'] ? cleaner($_POST['introduction']): '';
    $content = $_POST['content'] ? $_POST['content'] : '';
    $picture = $imageid;
    $creator_id = $_SESSION['userid'];
    $modified_at = time();

    if(empty($title) || empty($introduction) || empty($content)){
        array_push($validation, 'Something is missing.');
        $_SESSION['validation'] = $validation;
        header('Location: /edit/' . $ArticleName);
        exit;
    }

    $updatesql = 'UPDATE articles SET title = ?, introduction = ?, content = ?, picture = ?, creator_id = ?, modified_at = ? WHERE slug = ?' ;


    try {
        $db->run($updatesql, [$title, $introduction, $content, $picture, $creator_id, $modified_at, $ArticleName]);
    }
    catch(Exception $e) {
        $validation = [];
        array_push($validation, 'Error');
        $_SESSION['validation'] = $validation;
        header('Location: /edit/' . $ArticleName);
        exit;
    }

    $_SESSION['success'] = 'Modified successfully';
    header('Location: /edit/' . $ArticleName);
    exit;


}


function DeleteHandler($vars)
{
    if( !isLoggedin() )
    {
        header('Location: /');
        exit;
    }
    global $db;
    $ArticleName = $vars['ArticleName'];
    $GetArticle = 'SELECT * FROM articles WHERE slug = ?';
    $article = $db->run($GetArticle, [$ArticleName])->fetch();

    if($article == null){
        header('Location: /');
        exit;
    }

    $Delete = render('Delete.phtml', ['article' => $article]);
    echo render("wrapper.phtml", [ 'inner' => $Delete , 'site' => 'delete' ]);
}

function DeleteHandlerServer($vars)
{
    if( !isLoggedin() )
    {
        header('Location: /');
        exit;
    }
    global $db;
    $ArticleName = $vars['ArticleName'];
    $DeleteArticle = 'DELETE FROM articles WHERE slug = ?';
    $db->run($DeleteArticle, [$ArticleName]);


    $_SESSION['success'] = 'Deleted successfully';
    header('Location: /');
    exit;
}


function LoginHandler()
{
    if( isLoggedin() )
    {
        header('Location: /');
        exit;
    }
    $Login = render("Login.phtml");
    echo $Login;
    
}


function LoginHandlerServer()
{
    if( isLoggedin() )
    {
        header('Location: /');
        exit;
    }

    global $db;
    $username = $_POST['username'];
    $sql = "SELECT * FROM users WHERE username = ? ";
    $User = $db->run($sql, [$username])->fetchAll();
    if ( count($User) == 1 ){

        if(password_verify($_POST['password'] , $User[0]['password'])){

            $_SESSION['userid'] = $User[0]['id'];
            $_SESSION['username'] = $User[0]['username'];
            header('Location: /');

        }else{
            $_SESSION['info'] = 'Password is not correct.';
            header('Location: /login');
            exit;
        }
    
    }else{
        $_SESSION['info'] = 'There is no account with this username.';
        header('Location: /login');
        exit;
    }
}

function RegisterHandler()
{
    if( isLoggedin() )
    {
        header('Location: /');
        exit;
    }
    $Register = render("Register.phtml");
    echo $Register;
}

function RegisterHandlerServer()
{
    if( isLoggedin() )
    {
        header('Location: /');
        exit;
    }

    global $db;

    $username = $_POST['username'] ?? false;
    $password = $_POST['password'] ?? false;
    $password_confirm = $_POST['password_confirm'] ?? false;

    if(!$username || !$password){
        $_SESSION['info'] = 'You did not give the username or password.';
        header('Location: /register');
        exit; 
    }
    if(strlen($username) < 3){
        $_SESSION['info'] = 'Username is too short.';
        header('Location: /register');
        exit;
    }
    if(strlen($password) < 3){
        $_SESSION['info'] = 'Password is too short.';
        header('Location: /register');
        exit;
    }
    if($password != $password_confirm){
        $_SESSION['info'] = 'The 2 password did not match.';
        header('Location: /register');
        exit;
    }
    

    $sql = "SELECT * FROM users WHERE username = ? ";
    $record = $db->run($sql, [$username])->fetchAll();

    if(count($record) == 0){

        $HashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users(username, password) VALUES(?, ?)";
        try {
            $db->run($sql, [$username, $HashedPassword]);
        }
        catch(Exception $e) {
            exit;
        }

        $_SESSION['success'] = 'Registered successfully';
        header('Location: /login');
        exit;

    }else{
        $_SESSION['info'] = 'Username already exists';
        header('Location: /register');
        exit;
    }
}

function LogoutHandler()
{

    session_destroy();

    header('Location: /');

}
function notFoundHandler()
{
    echo 'There is no content at this subpage.';
}

function render($path, $params = [])
{
    ob_start();
    require __DIR__ . '/views/' . $path;
    return ob_get_clean();
}
