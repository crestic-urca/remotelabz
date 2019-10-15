/**
 * This file implements JavaScript for flavors/
 */

import API from './api';

const api = new API('flavor')
  
$(function () {
    var flavorTable = $('#flavorTable').DataTable({
        ajax: {
            url: Routing.generate('api_flavors'),
            dataSrc: ''
        },
        buttons: [{
            extend: 'edit',
            action: function() {
                api.edit($('table tr.selected').data('id'));
            }
        }, {
            extend: 'delete',
            action: function() {
                api.delete($('table tr.selected').data('id'));
            }
        }],
        columns: [{
                data: 'name'
            }, {
                data: 'memory'
            }, {
                data: 'disk'
        }]
    });
})
  