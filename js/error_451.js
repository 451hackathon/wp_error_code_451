function setError451Ignore() {
    var date = new Date();
    date.setTime(date.getTime()+(60*60*1000));
    var expires = ";"+date.toGMTString();
    document.cookie = "ignore=1"+expires+"; path=/";
    location.reload();
}

