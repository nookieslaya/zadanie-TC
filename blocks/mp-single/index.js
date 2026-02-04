(function () {
    var registerBlockType = wp.blocks.registerBlockType;
    var el = wp.element.createElement;
    var Placeholder = wp.components.Placeholder;
    var __ = wp.i18n.__;

    registerBlockType('wp-sejm-api/mp-single', {
        edit: function () {
            return el(
                'div',
                {},
                el(Placeholder, {
                    icon: 'id',
                    label: __('MP Profile', 'wp-sejm-api'),
                    instructions: __('Podglad profilu jest dostepny na froncie strony.', 'wp-sejm-api'),
                })
            );
        },
        save: function () {
            return null;
        },
    });
})();
