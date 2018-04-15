<?php

include_once("configs.php");

class FeedManager {
    private $conn = null;
    private $cacheTimeout = 00;

    function __construct() {

        $this->conn = new mysqli(
            DB_SERVER,
            DB_USER,
            DB_PASSWORD,
            DB_NAME
        );
        if( $this->conn->connect_error ) {
            die("connection failed: " . $this->conn->connect_error);
        }
        $this->conn->query("set names 'utf8'");
    }

    public function GetFeedsByIds($ids) {
        return $this->SelectFeedsByIds($ids);
    }

    public function GetFeedsByCategory($category,$excludeFeeds) {
        $ids = array();
        switch ($category) {
            case "geral":
                $ids = array(1,2,3);
                break;
            case "desporto":
                $ids = array(5,9);
                break;
            case "economia":
                $ids = array(6,7,8);
                break;
            case "tecnologia":
                $ids[] = 4;
                break;
        }

        $ids = array_diff($ids,$excludeFeeds);
        
        return $this->GetFeedsByIds($ids);
    }

        public function GetFeedItemsByFeed($feed) {
            return $this->GetFeedItemsByFeeds(array($feed),0,50);
        }

        public function GetFeedItemsBySearchQuery($searchQuery,$skip,$take) {
            $tokens = explode(" ", $searchQuery);
            return $this->SelectFeedItemsByTokens($tokens, $skip,$take);
        }

        public function GetFeedItemsByFeeds($feeds,$skip,$take) {
        $feedsId = array();
        foreach( $feeds as $feed ) {
            if( time() - (new DateTime($feed["LastUpdate"]))->getTimestamp() > $this->cacheTimeout ) {
             $items = $this->TryDownloadFeedItemsByFeed($feed);
                if( $items !== FALSE ) {
                    $this->UpdateFeedAndFeedItems($feed,$items);
                }
                $feedsId[] = $feed["Id"];
            }
        }
        
        $items = $this->SelectFeedItemsByFeedsIds($feedsId,$skip,$take);

        return $items;
    }

    private function TryDownloadFeedItemsByFeed($feed) {
        $contentString = file_get_contents($feed["Url"]);
        $items = FALSE;
        if( $contentString !== FALSE) {
            $content = new SimpleXmlElement($contentString,LIBXML_NOCDATA);

            $items = array();
            
            foreach($content->channel->item as $item ) {
                $items[] = array(
                    "feedId" => $feed["Id"],
                    "title" => (string) $item->title,
                    "link" => (string)  $item->link,
                    "description" => (string) $item->description,
                    "pubDate" => (new DateTime($item->pubDate))->getTimestamp()
                );
            }
        }
        
        return $items;
    }

    /*
     * Database Operations
     */

    private function UpdateFeedAndFeedItems($feed, $items) {
        $query = "UPDATE Feed SET LastUpdate = UTC_TIMESTAMP() WHERE Id = {$feed['Id']}";
        $this->conn->query($query);
        $query = "INSERT INTO Item (Title,PubDate,Link,Description,FeedId) VALUES ";
        $firstItem = true;
        foreach( $items as $item ) {
            if( $firstItem )
                $firstItem = false;
            else
                $query .= ",";
            $title = $this->conn->escape_string($item["title"]);
            $link = $this->conn->escape_string($item["link"]);
            $pubDate = $item["pubDate"];
            $description = $this->conn->escape_string($item["description"]);
            $feedId = $this->conn->escape_string($feed["Id"]);

            $query .= "('{$title}',FROM_UNIXTIME({$pubDate}),'{$link}','{$description}',{$feedId})";
        }

        $query .=" ON DUPLICATE KEY UPDATE Id = Id, PubDate = VALUES(PubDate)";
        
        $this->conn->query($query);
        //die(mysqli_error($this->conn));
    }

    private function SelectFeedItemsByTokens($tokens, $skip, $take) {
        $condition = "WHERE 1 = 1";
        
        foreach( $tokens as $token ) {
            $condition .= " AND I.Title LIKE '%" . $this->conn->escape_string($token) . "%'";
        }

        return $this->SelectFeedItemsByCondition($condition, $skip, $take);
    }

    private function SelectFeedItemsByFeed($feed) {
        $condition = "WHERE I.FeedId = {$feed['Id']}";

        return $this->SelectFeedItemsByCondition($condition);
    }

    private function SelectFeedItemsByFeedsIds($feedsIds,$skip,$take) {
        $items = array();
        if( sizeof($feedsIds) > 0 ) {
        $condition = "WHERE F.Id in ";
        $condition .= "(" . implode(", ", $feedsIds) . ") ";
        
            $items = $this->SelectFeedItemsByCondition($condition,$skip,$take);
        }
    
        return $items;
    }

    private function SelectFeedItemsByCondition($condition,$skip,$take) {
        $query ="
SELECT
    I.Id id,I.Title title,I.Link link,I.PubDate pubDate,I.Description description
    ,F.Title source
FROM
    Item I
INNER JOIN
    Feed F ON F.Id = I.FeedId
{$condition}
ORDER BY
    I.PubDate DESC
LIMIT {$skip},{$take}";
        $result = $this->conn->query($query);
        $items = array();

        //die(mysqli_error($this->conn));

        if( $result->num_rows > 0 ) {
              while($row = $result->fetch_assoc() ) {
                $items[] = array(
                    "id" => $row["id"],
                    "title" => $row["title"],
                    "link" => $row["link"],
                    "description" => $row["description"],
                    "source" => $row["source"],
                    "pubDate" => (new DateTime($row["pubDate"]))->getTimestamp()
                );
            }
        }

        return $items;
    }

    private function SelectFeedsByIds($ids) {
        $feeds = array();
        if( sizeof($ids) > 0 ) {
        $idsEscaped = "";
        
        foreach($ids as $id) {
            if( $idsEscaped != "")
                $idsEscaped .=",";
            $idsEscaped .= intval($id);
        }
        $query = "SELECT Id, Title, Url, LastUpdate, CategoryId FROM Feed WHERE Id IN ({$idsEscaped})";

        $result = $this->conn->query($query);
        //die(mysqli_error($this->conn));

        if( $result->num_rows > 0 ) {
            while($row = $result->fetch_assoc()) {
                $feeds[] = $row;
            }
        }
    }
        return $feeds;
    }

    // private function utf8ize($d) {
    //     if (is_array($d)) {
    //         foreach ($d as $k => $v) {
    //             $d[$k] = $this->utf8ize($v);
    //         }
    //     } else if (is_string ($d)) {
    //         return utf8_encode($d);
    //     }
    //     return $d;
    // }
}

?>