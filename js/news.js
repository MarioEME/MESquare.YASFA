class News {
    constructor() {
        this.showHiddenItems = document.getElementById("showHiddenItems");
        this.sources = document.getElementsByClassName("source");
    }

    initialize() {
        window.addEventListener("hashchange",this.onHashChange.bind(this),false);
        document.getElementById("showMore").addEventListener("click",this.onShowMoreClick.bind(this),false);
        document.getElementById("searchIcon").addEventListener("click",this.onSearchIconClick.bind(this),false);
        this.showHiddenItems.addEventListener("click",this.onShowHiddenItemsClick.bind(this),false);

        for(let i = 0; i < this.sources.length; i++) {
            let item = this.sources[i];
            item.addEventListener("change",this.onFeedClick.bind(this));
        };

        this.initializeUser();

        if( location.hash == "")
            location.hash = "#geral";
        else {
            this.updateNews({
                clearNews: true,
                skip: 0,
                category: window.location.hash.replace("#","")
            });
        }


    }

    initializeUser() {
        let url = "/projects/news/api/default.php/user/me";
        this._makeGetRequest(url)
            .then(response => {
                let data = response.data;
                for(let i = 0; i < this.sources.length; i++) {
                    let source = this.sources[i];
                    let sourceId = source.getAttribute("data-feed-id");
                    source.checked = data.hiddenFeeds.indexOf(sourceId) == -1;
                }

            });
    }

    updateNews(options) {
        if( options.clearNews ) {
            var feed = document.getElementById("feed");            
            while (feed.firstChild) {
                feed.removeChild(feed.firstChild);
            }
        }

        this.showLoading();
        this.getNews(options)
            .then(this.hideLoading.bind(this))
            .then(this.showNews.bind(this));
    }

    onFeedClick(ev) {
        this.toggleFeed(ev.target);
    }

    onShowHiddenItemsClick() {
        this.updateHiddenItensVisibility();
    }

    updateHiddenItensVisibility() {
        let showHiddenItems =  this.showHiddenItems.checked;

        let items = document.getElementsByClassName("hidden-item");
        for(let i = 0; i < items.length; i++) {
            let item = items[i];
            item.style.display = showHiddenItems ? "block" : "none";
        };
    }

    toggleFeed(feedCheckbox) {
        let feedId = feedCheckbox.getAttribute("data-feed-id");

        if( !feedCheckbox.checked ) {
            this.addHiddenFeed(feedId)
                .then((data) => {
                    feedCheckbox.setAttribute("data-hiddenfeed-id",data.id);
                });
        }
        else {
            this.removeHiddenFeed(feedId);
        }
    }

    addHiddenFeed(feedId) {
        let url = "/projects/news/api/default.php";
        url = url + "/user/hiddenfeed"
        return this._makePostRequest(url,{id:feedId});
    }

    
    removeHiddenFeed(feedId) {
        let url = "/projects/news/api/default.php";
        url = url + `/user/hiddenfeed/${feedId}`;
        this._makeDeleteRequest(url);
    }

    onSearchIconClick() {
        this.search();
    }

    search() {
        this.updateNews({
            clearNews: true,
            skip: 0,
            category: window.location.hash.replace("#",""),
            searchQuery: document.getElementById("search").value,
        });        
    }

    onShowMoreClick() {
        this.updateNews({
            clearNews: false,
            skip: document.getElementsByClassName("item").length,
            category: window.location.hash.replace("#","")
        });
    }

    showLoading() {
        let div = document.getElementById("loading");
        var showMore = document.getElementById("showMore");
        showMore.style.visibility = "hidden";
        div.className = div.className.replace("loading-hidden","");
    }

    hideLoading(data) {
        let div = document.getElementById("loading");
        var showMore = document.getElementById("showMore");
        showMore.style.visibility = "visible";
        div.className = div.className + " loading-hidden";
        return data; 
    }

    getNews(options) {
        var query = "";
        query += options.category ? "/category/" + options.category +"/item" : "";
        query += options.skip ? "?s=" + options.skip : "";
        query += options.searchQuery ? "/search/items/" + options.searchQuery : "";

        return this._makeGetRequest("/projects/news/api/default.php" + query);
    }

    showNews(newsResponse) {
        var hiddenItems = this.getHiddenItems();
        
        var feed = document.getElementById("feed");

        newsResponse.data.items.forEach((item,i) => {
                var hiddenItem = !hiddenItems[item.id] ? "" : "hidden-item";
                var div = document.createElement("div");
                var date = new Date(0);
                date.setUTCSeconds(parseInt(item.pubDate));
                div.innerHTML =
                    '<div class="item ' + hiddenItem + ' fadeIn" id="'+item.id+'">'
                    +   '<div class="header">'
                    +     '<div class="header-right">'
                    +       '<span class="hide-item">X</span>'
                    +     '</div>'
                    +     '<div class="header-left">'
                    +       '<a href="'+item.link+'" target="_blank" class="source"><img src="assets/external_link_24x24.png"></a>'
                    +       '<a href="'+item.link+'" target="_blank" class="source"><div class="title">'+ item.title +'</div></a>'
                    +     '</div>'
                    +   '</div>'
                    +   '<div class="details">'
                    +     '<div class="date">'+ date.toLocaleString() +'</div>'
                    +     '<div class="space">//</div>'
                    +       '<a href="'+item.link+'" target="_blank" class="source">'+ item.source +'</a>'
                    +   '</div>'
                    +   '<div class="description hidden">'+ item.description +'</div>'
                    + '</div>'
                div.getElementsByClassName("hide-item")[0].addEventListener("click", this.onCloseItemClick.bind(this));
                //div.getElementsByClassName("title")[0].addEventListener("click",this.onTitleClick.bind(this));

                div.children[0].style.animationDelay = (i*(50-Math.min(i,9)*5)) + "ms";
                feed.appendChild(div);
        }); 
        this.updateHiddenItensVisibility();
        if( newsResponse.data.hasMoreResults ) {
            document.getElementById("showMore").style.display = "";
        }
        else {
            document.getElementById("showMore").style.display = "none";
        }
    }

    onTitleClick(ev) {
        let description = ev.target
            .closest(".item") 
            .getElementsByClassName("description")[0];

        this.toggleDescription(description);
    }

    toggleDescription(description) {
        if( description.className.indexOf("hidden") > -1 ) {
            description.className = description.className.replace("hidden","");
        }
        else {
            description.className = description.className + " hidden";
        }
    }

    onCloseItemClick(ev) {
        var item = ev.target.closest(".item");
        this.hideItem(item);
    }

    hideItem(div) {
        let id = div.getAttribute("id");
        var hiddenItems = this.getHiddenItems();
        hiddenItems[id] = 1;
        this.updateHiddenItems(hiddenItems);
        div.className += " hidden-item";
        let showHiddenItems =  this.showHiddenItems.checked;
        div.style.display = showHiddenItems ? "block" : "none";
    }

    getHiddenItems() {
        let storage = window.localStorage;
        var hiddenItems = JSON.parse(storage["hiddenItems"] || "{}");
        return hiddenItems;
    }

    updateHiddenItems(hiddenItems) {
        let storage = window.localStorage;
        storage["hiddenItems"] = JSON.stringify(hiddenItems);
    }

    onHashChange() {
        if( window.location.hash != "#search") {
            this.updateNews({
                clearNews: true,
                skip: 0,
                category: window.location.hash.replace("#",""),
            });
            document.getElementById("search").value = "";
        }
    }

    _makeGetRequest(url) {
        return new Promise((resolve,error) => {
            var req = new XMLHttpRequest();
            req.addEventListener("load", response => resolve(JSON.parse(response.target.response)));
            req.open("GET",url);
            req.setRequestHeader("Accept","application/json");
            req.send();
        });
    }

    _makePostRequest(url,payload) {
        return new Promise((resolve,error) => {
            var req = new XMLHttpRequest();
            req.addEventListener("load", response => resolve(JSON.parse(response.target.response).data));
            req.open("POST",url);
            req.setRequestHeader("Accept","application/json");
            req.send(JSON.stringify(payload));
        });
    }

    _makeDeleteRequest(url) {
        return new Promise((resolve,error) => {
            var req = new XMLHttpRequest();
            req.addEventListener("load", response => resolve(JSON.parse(response.target.response)));
            req.open("DELETE",url);
            req.setRequestHeader("Accept","application/json");
            req.send();
        });
    }

}