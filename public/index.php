<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Sunra\PhpSimple\HtmlDomParser;

if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/utils.php';
$config['displayErrorDetails'] = true;

$app = new \Slim\App(["settings" => $config]);
$container = $app->getContainer();

// Register component on container
$container['view'] = function ($container) {
    $view = new \Slim\Views\Twig(__DIR__ . '/../templates/', [
    ]);

    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new Slim\Views\TwigExtension($container['router'], $basePath));

    return $view;
};

$app->get('/', function (Request $request, Response $response) {

    $response = $this->view->render($response, "form.phtml", array());
    return $response;
});

$app->post('/', function (Request $request, Response $response) {

    $data = $request->getParsedBody();
    $file_type=$_FILES['file']['type'];
    $file_name=$_FILES['file']['name'];
    $search_key = strtoupper(filter_var($data['buscar-palabra'], FILTER_SANITIZE_STRING));
    $search_result = null;

    $option = filter_var($data['en'], FILTER_SANITIZE_STRING);

    switch ($option){

        case 'file-text':

            if ($file_type == 'text/plain'){

                $content = file_get_contents($_FILES['file']['tmp_name'], FILE_USE_INCLUDE_PATH);

                $words_result = convertWords($content);

                $words_result = sortWords($words_result);


                if (array_key_exists($search_key, $words_result)) {
                    $search_result = $words_result[$search_key];
                }





                $show = True;

                $response = $this->view->render($response, "response.phtml", array('words_result' => $words_result,
                    'show' => $show, 'search_key' => $search_key, 'search_result' => $search_result ));
                return $response;

            } else {

                $response = $this->view->render($response, "alert.phtml", array( 'error_file' => $file_name ));
                return $response;
            }

            break;

        case 'html':

            $url = strtoupper(filter_var($data['url'], FILTER_VALIDATE_URL));


            if(!urlExists($url)){

                $content =  strip_tags(HtmlDomParser::file_get_html($url)->plaintext);


                $words_result = convertWords($content);

                $words_result = sortWords($words_result);


                if (array_key_exists($search_key, $words_result)) {
                    $search_result = $words_result[$search_key];
                }


                $show = True;

                $response = $this->view->render($response, "response.phtml", array('words_result' => $words_result,
                    'show' => $show, 'search_key' => $search_key, 'search_result' => $search_result ));
                return $response;

            } else {
                return $this->view->render($response, "alert.phtml", array( 'error_url' => True, 'url' => $url ));
            }

            break;

        /*case 'currency':
            $amount = 2;
            //currency codes : http://en.wikipedia.org/wiki/ISO_4217
            $c_from = "USD";
            $c_to = "INR";
            $c_amount =  currencyConverter($c_from,$c_to,$amount);

            $response = $this->view->render($response, "response.phtml", array(
                'show' => False, 'amount' => $amount, 'c_from' =>$c_from, 'c_to'=>$c_to, 'c_amount' => $c_amount  ));


            return $response;
        */


    }

});


$app->get('/currency', function (Request $request, Response $response) {


    $response = $this->view->render($response, "currency_form.phtml", array());
    return $response;
});


$app->post('/currency', function (Request $request, Response $response) {

    $data = $request->getParsedBody();
    $amount = strtoupper(filter_var($data['amount'], FILTER_SANITIZE_STRING));;
    //currency codes : http://en.wikipedia.org/wiki/ISO_4217
    $c_from = strtoupper(filter_var($data['c_from'], FILTER_SANITIZE_STRING));;
    $c_to = strtoupper(filter_var($data['c_to'], FILTER_SANITIZE_STRING));;
    $c_amount =  currencyConverter($c_from,$c_to,$amount);

    $response = $this->view->render($response, "response.phtml", array(
        'show' => False, 'amount' => $amount, 'c_from' =>$c_from, 'c_to'=>$c_to, 'c_amount' => $c_amount  ));

    return $response;
});


function convertWords ($content){
    $accents = '/&([A-Za-z]{1,2})(grave|acute|circ|cedil|uml|lig);/';

    $string_encoded = htmlentities($content,ENT_NOQUOTES,'UTF-8');

    $content = strtoupper(preg_replace($accents,'$1',$string_encoded));

    $words_result = array_count_values(str_word_count($content, 1)) ;


    return $words_result;
}


function sortWords ($words_result){
    $k = array_keys($words_result);
    $v = array_values($words_result);
    array_multisort($v, SORT_DESC, $k, SORT_ASC);
    $words_result = array_combine($k, $v);

    $sliced_array = array_slice($words_result, 0, 10);

    return $sliced_array;
}

function urlExists($url=NULL)
{
    if($url == NULL) return false;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if($httpcode>=200 && $httpcode<300){
        return true;
    } else {
        return false;
    }
}

$app->run();
