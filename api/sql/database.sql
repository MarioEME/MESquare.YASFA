CREATE DATABASE IF NOT EXISTS FeedReader;

CREATE TABLE IF NOT EXISTS Feed (
    Id SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    
    Title NVARCHAR(100) NOT NULL,
    Url NVARCHAR(2000) NOT NULL,
    LastUpdate DATETIME NOT NULL,
    CategoryId INT NOT NULL REFERENCES Category(Id)
);

CREATE TABLE IF NOT EXISTS Item (
    Id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    Title NVARCHAR(250) NOT NULL,
    PubDate DATETIME NOT NULL,
    Link NVARCHAR(250),
    Description NVARCHAR(500) NOT NULL DEFAULT '',
    FeedId SMALLINT UNSIGNED NOT NULL REFERENCES Feed(Id),

    UNIQUE KEY(Link)
);

CREATE TABLE IF NOT EXISTS Category (
    Id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    Name NVARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS `User` (
    Id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    Token VARCHAR(128),

    CreatedOn TIMESTAMP DEFAULT CURRENT_TIMESTAMP()
);

CREATE TABLE IF NOT EXISTS UserHiddenItem (
    Id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    UserId INT, 
    ItemId BIGINT
);

CREATE TABLE IF NOT EXISTS UserHiddenFeed (
    Id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    UserId INT, 
    FeedId BIGINT,

    CONSTRAINT UQ_UserHiddenFeed UNIQUE(UserId, FeedId)
);

CREATE TABLE IF NOT Exists UserStarItem (
    Id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    UserId INT,
    ItemId BIGINT
);

INSERT INTO Category (Name) VALUES
    ("Geral")
    ,("Desporto")
    ,("Economia")
    ,("Tecnologia");

INSERT INTO Feed (Title,Url,CategoryId) VALUES
("Jornal de Notícias","http://feeds.jn.pt/JN-Ultimas",1)
,("Expresso","http://feeds.feedburner.com/expresso-geral?format=xml",1)
,("Observador","http://observador.pt/feed/",1)
,("SapoTEK","https://tek.sapo.pt/rss",3)
,("JN Desporto","http://feeds.jn.pt/JN-Desporto",2)
,("Jornal de Negócios","http://www.jornaldenegocios.pt/rss",4)
,("Dinheiro Vivo","http://feeds.feedburner.com/dv-economia",4)
,("JN Economia","http://feeds.jn.pt/JN-Economia",4)
,("Corredores Anónimos","http://corredoresanonimos.pt/feed",2);