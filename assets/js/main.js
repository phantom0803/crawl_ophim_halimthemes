jQuery(function ($) {
  const buttonGetListMovies = $("div#get_list_movies");
  const inputPageFrom = $("input[name=page_from]");
  const inputPageTo = $("input[name=page_to]");
  const divMsg = $("div#msg");
  const divMsgText = $("p#msg_text");
  const textArealistMovies = $("textarea#result_list_movies");
  const buttonCrawlMovies = $("div#crawl_movies");
  const buttonRollMovies = $("div#roll_movies");
  const divMsgCrawlSuccess = $("div#result_success");
  const divMsgCrawlError = $("div#result_error");
  const textAreaResultSuccess = $("textarea#list_crawl_success");
  const textAreaResultError = $("textarea#list_crawl_error");

  buttonRollMovies.on("click", () => {
    var listLink = textArealistMovies.val();
    listLink = listLink.split("\n");
    listLink.sort(() => Math.random() - 0.5);
    listLink = listLink.join("\n");
    textArealistMovies.val(listLink);
  });

  buttonGetListMovies.on("click", () => {
    divMsg.show(300);
    textArealistMovies.show(300);
    crawl_page_callback(inputPageFrom.val());
  });
  const crawl_page_callback = (currentPage) => {
    var urlPageCrawl = `https://ophim1.com/danh-sach/phim-moi-cap-nhat?page=${currentPage}`;

    if (currentPage < inputPageTo.val()) {
      divMsgText.html("Done!");
      buttonCrawlMovies.show(300);
      return false;
    }
    divMsgText.html(`Crawl Page: ${urlPageCrawl}`);
    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "crawl_ophim_page",
        url: urlPageCrawl,
      },
      beforeSend: function () {
        buttonGetListMovies.hide(300);
      },
      success: function (res) {
        let currentList = textArealistMovies.val();
        if (currentList != "") currentList += "\n" + res;
        else currentList += res;

        textArealistMovies.val(currentList);
        currentPage--;
        crawl_page_callback(currentPage);
      },
    });
  };

  buttonCrawlMovies.on("click", () => {
    divMsg.show(300);
    divMsgCrawlSuccess.show(300);
    divMsgCrawlError.show(300);
    crawl_movies(false);
  });
  const crawl_movies = () => {
    var listLink = textArealistMovies.val();
    listLink = listLink.split("\n");
    let linkCurrent = listLink.shift();
    if (linkCurrent == "") {
      divMsgText.html(`Crawl Done!`);
      return false;
    }
    listLink = listLink.join("\n");
    textArealistMovies.val(listLink);
    divMsgText.html(`Crawl Movies: <b>${linkCurrent}</b>`);

    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "crawl_ophim_movies",
        url: linkCurrent,
      },
      beforeSend: function () {
        buttonCrawlMovies.hide(300);
        buttonRollMovies.hide(300);
      },
      success: function (res) {
        let data = JSON.parse(res);
        if (data.status) {
          let currentList = textAreaResultSuccess.val();
          if (currentList != "") currentList += "\n" + linkCurrent;
          else currentList += linkCurrent;
          textAreaResultSuccess.val(currentList);
        } else {
          let currentList = textAreaResultError.val();
          if (currentList != "") currentList += "\n" + linkCurrent;
          else currentList += linkCurrent;
          textAreaResultError.val(currentList);
        }
        crawl_movies();
      },
      error: function (xhr, ajaxOptions, thrownError) {
        let currentList = textAreaResultError.val();
        if (currentList != "") currentList += "\n" + linkCurrent;
        else currentList += linkCurrent;
        textAreaResultError.val(currentList);
      }
    });
  };
});
