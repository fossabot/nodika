function boot_study() {
    $('.help-button').click(function (e) {
        e.preventDefault();
        var $button = $(this);
        var $primaryContent = $(".primary-content");
        var $secondaryContent = $(".secondary-content");
        var turnOn = $secondaryContent.hasClass("hidden");
        $secondaryContent.toggleClass("hidden");
        $button.toggleClass("pressed");
        if (turnOn) {
            $primaryContent.addClass("col-md-6");
            $primaryContent.removeClass("col-md-12");
        } else {
            $primaryContent.addClass("col-md-12");
            $primaryContent.removeClass("col-md-6");
        }
        return false;
    });
}