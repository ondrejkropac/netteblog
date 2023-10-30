/**
 * Odstraňování náhledů AJAXem ve správě produktů.
 */
 $(function () {
    $.nette.init();
    $.nette.ext({
        before: function (xhr, settings) {
            if (!settings.nette) {
                return;
            }

            var question = settings.nette.el.data('confirm');
            if (question) {
                return confirm(question);
            }
        },
        error: function (xhr, status, error) {
            alert("Při odstraňování obrázku došlo k chybě " + status + ": " + error);
        }
    });
});