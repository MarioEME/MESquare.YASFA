<?php

include_once("randomstringenerator.php");
include_once("usermanager.php");

class Service {

    private $logger = null;
    private $request = null;
    private $result = null;
    private $user = null;

    private $COOKIE = "ME2FDR_SESSION";

    function __construct($logger)
    {
        $this->logger = $logger;
    }

    public function execute()
    {
        $this->checkUser();
        $this->processRequest();
        $this->executeRequest();
        $this->processResponse();
    }

    private function checkUser() {

        if( !isset($_COOKIE[$this->COOKIE]) ) {
            $userManager = new UserManager($this->logger);
            $newToken = $userManager->createUser();

            setcookie($this->COOKIE,$newToken,mktime(23,59,59,12,31,9999),"/");
            $this->user = $newToken;
        }
        else {
            $this->user = $_COOKIE[$this->COOKIE];
        }

        $this->user = (new UserManager($this->logger))->getUserByToken($this->user);
        $this->logger->Debug("user: ".print_r($this->user,true));
    }

    // /search/items/a b dsds
    // /category/<xpto>/items
    // /user
    private function processRequest()
    {
        $uri = str_replace("/projects/news/api/default.php/","",$_SERVER['REQUEST_URI']);
        $this->logger->Debug("Requesting URI: $uri");

        $verb = $_SERVER['REQUEST_METHOD'];
        $path = explode("/",$uri);
        $this->logger->Debug("Path: ".print_r($path,true));

        switch($path[0])
        {
            case "search":
                $this->request = array(
                    "type" => "search",
                    "data" => array(
                        "searchQuery" => $path[2],//// isset($_GET["q"]) ?  $_GET["q"] : "";
                        "skip" => isset($_GET["s"]) ? $_GET["s"] + 0 : 0,
                        "take" =>  isset($_GET["t"]) ? $_GET["t"] + 0 : 50,
                    )
                );
                break;

            case "category";
                if( $path[2] == "item")
                {
                    $this->request = array(
                        "type" => "category_items",
                        "data" => array(
                            "category" => $path[1],
                            "skip" => isset($_GET["s"]) ? $_GET["s"] + 0 : 0,
                            "take" =>  isset($_GET["t"]) ? $_GET["t"] + 0 : 50,
                        )
                    );
                }
                break;

            case "user":
                if( sizeof($path) == 2 && $verb == "GET" ) {
                    $this->request = array(
                        "type" => "user",
                        "data" => array(
                            "user" => $path[1],
                        )
                    );
                }
                else {
                    if( $verb == "POST" ) {
                        if( $path[1] == "hiddenfeed") {
                            $body = file_get_contents('php://input');
                            $body = json_decode($body,true);

                            $this->request = array(
                                "type" => "post_user_hiddenfeed",
                                "data" => array(
                                    "user" => $path[1],
                                    "feedId" => $body["id"]
                                )
                            );  
                        }
                    }
                    if( $verb == "DELETE" ) {
                        if( $path[1] == "hiddenfeed") {
                            $this->request = array(
                                "type" => "delete_user_hiddenfeed",
                                "data" => array(
                                    "user" => $path[1],
                                    "feedId" => $path[2]
                                )
                            );  
                        }
                    }
                }
                break;
            default:
                $this->request = array(
                    "type" => "unknown",
                    "data" => array()
                );
                break;
        }
        
        $this->logger->Debug("Request:" . print_r($this->request,true));
        //$searchToken = isset($_GET["t"]) ? $_GET["t"] : "";
    }

    private function executeRequest()
    {
        switch($this->request["type"])
        {
            case "search":
                $feedManager = new FeedManager();
                $items = $feedManager->GetFeedItemsBySearchQuery(
                    $this->request["data"]["searchQuery"],
                    $this->request["data"]["skip"],
                    $this->request["data"]["take"]+1
                );
                
                $this->result = array(
                    "hasMoreResults" => sizeof($items) > $take,
                    "items" => $items
                );

                break;
            case "category_items":
                $feedManager = new FeedManager();
                $feeds = $feedManager->GetFeedsByCategory($this->request["data"]["category"],$this->user["hiddenFeeds"]);
                
                $items = $feedManager->GetFeedItemsByFeeds(
                    $feeds,
                    $this->request["data"]["skip"],
                    $this->request["data"]["take"]+1
                );
                
                $this->result = array(
                    "hasMoreResults" => sizeof($items) > $this->request["data"]["take"],
                    "items" => $items
                );

                break;
            case "user":
                $userManager = new UserManager($this->logger);
                $this->result = $this->user; // $userManager->getUserByToken($this->user["token"]);
                break;

            case "post_user_hiddenfeed":
                $userManager = new UserManager($this->logger);
                $hiddenFeedId = $userManager->addHiddenFeed($this->user,$this->request["data"]["feedId"]);
                $this->result = array(
                    "id" => $hiddenFeedId
                );
                break;
            case "delete_user_hiddenfeed":
                $userManager = new UserManager($this->logger);
                $userManager->removeHiddenFeed($this->user,$this->request["data"]["feedId"]);
                break;
        }
    }

    private function processResponse()
    {
        header('Content-type: application/json;charset=UTF-8');
        
        echo json_encode(array(
            "status" => "success",
            "data" => $this->result
        ));
    
        //$logger->Info("_GET[c]=".$category.";feeds.count()=".sizeof($feeds).";items.count()=".sizeof($items));  
    }
}
?>
