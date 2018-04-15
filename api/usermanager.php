<?php

class UserManager {
    private $conn = null;
    private $logger = null;

    function __construct($logger) {
        $this->logger = $logger;

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

    public function addHiddenFeed($user, $feedId) {
        return $this->insertUserHiddenFeed($user["id"],$feedId);
    }

    public function removeHiddenFeed($user,$feedId) {
        $this->deleteUserHiddenFeedByUserIdAndFeedId($user["id"], $feedId);
    }

    public function createUser() {
        $newToken = (new RandomStringGenerator())->generate("64");
        $this->insertUser($newToken);

        return $newToken;
    }

    public function getUser($userToken) {
        return $this->getUserByToken($userToken);
    }

    public function getUserByToken($token,$loadRelatedData = true) {
        $user = $this->selectUserByToken($token);
        if( $loadRelatedData )
            $user["hiddenFeeds"] = $this->selectUserHiddenFeedsByUserId($user["id"]);

        return $user;
    }

    private function insertUser($token) {
        $token = $this->conn->escape_string($token);
        
        $sql = "
INSERT User (Token)
VALUES ('$token')
";

        $this->conn->query($sql);
        $this->logger->SqlError($this->conn);

        return $token;
    }

    private function SelectUserByToken($token) {
        $token = $this->conn->escape_string($token);
        $sql = "
SELECT
    U.Id,
    U.Token
FROM
    User U
WHERE
    U.Token = '$token'
";

        $result = $this->conn->query($sql);
        $this->logger->SqlError($this->conn);

        $user = null;
        if( $result->num_rows > 0 ) {
            $row = $result->fetch_assoc();
            $user = array(
                "id" => $row["Id"],
                "token" => $row["Token"]
            );
        }

        return $user;
    }

    private function selectUserHiddenFeedsByUserId($userId)
    {
        $userId = intval($userId);
        $sql = "
SELECT
    FeedId
FROM
    UserHiddenFeed
WHERE
    UserId = $userId
";

        $result = $this->conn->query($sql);
        $this->logger->SqlError($this->conn);

        $items = array();
        if( $result->num_rows > 0 ) {
            while($row = $result->fetch_assoc() ) {
                $items[] = $row["FeedId"];
            }
        }

        return $items;
    }

    private function insertUserHiddenFeed($userId,$feedId)
    {
        $userId = intval($userId);
        $feedId = intval($feedId); 

        $sql = "
INSERT INTO UserHiddenFeed (UserId,FeedId)
VALUES ($userId,$feedId)    
ON DUPLICATE KEY UPDATE Id = Id;
";

        $this->conn->query($sql);
        $this->logger->SqlError($this->conn);

        return $this->conn->insert_id;
    }

    private function deleteUserHiddenFeedById($id)
    {
        $id = intval($id);

        $sql ="
DELETE FROM UserHiddenFeed
WHERE Id = $id;
";

        $this->conn->query($sql);
        $this->logger->SqlError($this->conn);
    }

    private function deleteUserHiddenFeedByUserIdAndFeedId($userId,$feedId)
    {
        $userId = intval($userId);
        $feedId = intval($feedId);
        $sql ="
DELETE FROM UserHiddenFeed
WHERE UserId = $userId AND FeedId = $feedId
";

        $this->conn->query($sql);
        $this->logger->SqlError($this->conn);
    }

}

?>