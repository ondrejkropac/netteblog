/**
 * Funkce napojí div formulářové skupiny na daný checkbox tak, aby se zobrazoval podle toho, jak je checkbox zaškrtnutý.
 * @param checkBoxSelector selektor checkboxu
 * @param divSelector selector divu formulářové skupiny
 * @param inverse jestli se má zobrazovat inverze k zaškrtnutí checkboxu
 */
 function toggleFormControls(checkBoxSelector, divSelector, inverse) {
    $(document).on('click', checkBoxSelector, function () {
        $(divSelector).slideToggle(500);
    });

    var checked = $(checkBoxSelector).prop('checked');
    if ((!inverse && !checked && $(checkBoxSelector).length) || (inverse && checked)) $(divSelector).hide();
}

$(function () {
    // Registrace známých checkboxů a jejich formulářových skupin.
    toggleFormControls('#create_account', '#create-account-controls');
    toggleFormControls('#omit_delivery_address', '#delivery-address-controls', true);
});