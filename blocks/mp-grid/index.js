(function () {
    var registerBlockType = wp.blocks.registerBlockType;
    var Fragment = wp.element.Fragment;
    var el = wp.element.createElement;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var useBlockProps = wp.blockEditor.useBlockProps;
    var PanelBody = wp.components.PanelBody;
    var RangeControl = wp.components.RangeControl;
    var ToggleControl = wp.components.ToggleControl;
    var Placeholder = wp.components.Placeholder;
    var __ = wp.i18n.__;

    registerBlockType('wp-sejm-api/mp-grid', {
        edit: function (props) {
            var attributes = props.attributes || {};
            var postsPerPage = typeof attributes.postsPerPage === 'number' ? attributes.postsPerPage : 12;
            var enablePagination = typeof attributes.enablePagination === 'boolean' ? attributes.enablePagination : true;
            var enableFilters = typeof attributes.enableFilters === 'boolean' ? attributes.enableFilters : true;
            var blockProps = useBlockProps();

            return el(
                Fragment,
                {},
                el(
                    InspectorControls,
                    {},
                    el(
                        PanelBody,
                        { title: __('MPs Grid', 'wp-sejm-api'), initialOpen: true },
                        el(RangeControl, {
                            label: __('Posts per page', 'wp-sejm-api'),
                            value: postsPerPage,
                            min: 1,
                            max: 60,
                            onChange: function (value) {
                                props.setAttributes({ postsPerPage: value });
                            },
                        }),
                        el(ToggleControl, {
                            label: __('Enable pagination', 'wp-sejm-api'),
                            checked: enablePagination,
                            onChange: function (value) {
                                props.setAttributes({ enablePagination: value });
                            },
                        }),
                        el(ToggleControl, {
                            label: __('Enable filters', 'wp-sejm-api'),
                            checked: enableFilters,
                            onChange: function (value) {
                                props.setAttributes({ enableFilters: value });
                            },
                        })
                    )
                ),
                el(
                    'div',
                    blockProps,
                    el(Placeholder, {
                        icon: 'id',
                        label: __('MPs Grid', 'wp-sejm-api'),
                        instructions: __('Podglad siatki jest dostepny na froncie strony.', 'wp-sejm-api'),
                    })
                )
            );
        },
        save: function () {
            return null;
        },
    });
})();
