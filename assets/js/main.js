jQuery(function ($) {
  var filterType = JSON.parse(localStorage.getItem("filterType")) != null ? JSON.parse(localStorage.getItem("filterType")) : [];
  var filterCategory = JSON.parse(localStorage.getItem("filterCategory")) != null ? JSON.parse(localStorage.getItem("filterCategory")) : [];
  var filterCountry = JSON.parse(localStorage.getItem("filterCountry")) != null ? JSON.parse(localStorage.getItem("filterCountry")) : [];

  var timeout_from = JSON.parse(localStorage.getItem("timeout_from")) != null ? localStorage.getItem("timeout_from") : 1000;
  var timeout_to = JSON.parse(localStorage.getItem("timeout_to")) != null ? localStorage.getItem("timeout_to") : 5000;
  $("input[name=timeout_from]").val(timeout_from);
  $("input[name=timeout_to]").val(timeout_to);

  $("input[name='filter_type[]']").each(function () {
    if (filterType.includes($(this).val())) {
      $(this).attr("checked", true);
    }
  });
  $("input[name='filter_category[]']").each(function () {
    if (filterCategory.includes($(this).val())) {
      $(this).attr("checked", true);
    }
  });
  $("input[name='filter_country[]']").each(function () {
    if (filterCountry.includes($(this).val())) {
      $(this).attr("checked", true);
    }
  });

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

  var inputFilterType = [];
  var inputFilterCategory = [];
  var inputFilterCountry = [];

  buttonCrawlMovies.on("click", () => {
    divMsg.show(300);
    divMsgCrawlSuccess.show(300);
    divMsgCrawlError.show(300);

    $("input[name='filter_type[]']:checked").each(function () {
      inputFilterType.push($(this).val());
    });
    $("input[name='filter_category[]']:checked").each(function () {
      inputFilterCategory.push($(this).val());
    });
    $("input[name='filter_country[]']:checked").each(function () {
      inputFilterCountry.push($(this).val());
    });

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
        filterType: inputFilterType,
        filterCategory: inputFilterCategory,
        filterCountry: inputFilterCountry,
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
          textAreaResultError.val(currentList + "=====>>" + data.msg);
        }

        var wait_timeout = 1000;
        if (data.wait) {
          let timeout_from = $("input[name=timeout_from]").val();
          let timeout_to = $("input[name=timeout_to]").val();
          let maximum = Math.max(timeout_from, timeout_to);
          let minimum = Math.min(timeout_from, timeout_to);
          wait_timeout = Math.floor(Math.random() * (maximum - minimum + 1)) + minimum;
        }
        divMsgText.html(`Wait timeout ${wait_timeout}ms`);
        setTimeout(() => {
          crawl_movies();
        }, wait_timeout);
      },
      error: function (xhr, ajaxOptions, thrownError) {
        let currentList = textAreaResultError.val();
        if (currentList != "") currentList += "\n" + linkCurrent;
        else currentList += linkCurrent;
        textAreaResultError.val(currentList);

        crawl_movies();
      },
    });
  };

  $("input[name='filter_type[]']").change(() => {
    var saveFilterData = [];
    $("input[name='filter_type[]']:checked").each(function () {
      saveFilterData.push($(this).val());
    });
    localStorage.setItem("filterType", JSON.stringify(saveFilterData));
  });

  $("input[name='filter_category[]']").change(() => {
    var saveFilterData = [];
    $("input[name='filter_category[]']:checked").each(function () {
      saveFilterData.push($(this).val());
    });
    localStorage.setItem("filterCategory", JSON.stringify(saveFilterData));
  });

  $("input[name='filter_country[]']").change(() => {
    var saveFilterData = [];
    $("input[name='filter_country[]']:checked").each(function () {
      saveFilterData.push($(this).val());
    });
    localStorage.setItem("filterCountry", JSON.stringify(saveFilterData));
  });

  $("input[name=timeout_from]").change((e) => {
    localStorage.setItem("timeout_from", $("input[name=timeout_from]").val());
  });
  $("input[name=timeout_to]").change((e) => {
    localStorage.setItem("timeout_to", $("input[name=timeout_to]").val());
  });
});
