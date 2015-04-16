'use strict';

define(['jquery', 'underscore', 'routing'], function ($, _, Routing) {
    return {
        urls: {
            'get_group': 'pim_enrich_group_rest_get'
        },
        promises: {},
        getGroup: function (code) {
            if (!this.promises[code]) {
                this.promises[code] = $.getJSON(Routing.generate(this.urls.get_group, { 'identifier': code }));
            }

            return this.promises[code].promise();
        }
    };
});
