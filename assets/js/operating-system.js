/**
 * This file implements JavaScript for operating-systems/
 */

import API from './app';

const api = new API('operating_system')
  
$(function () {
    var operatingSystemTable = $('#operatingSystemTable').DataTable({
        ajax: {
            url: Routing.generate('get_operating_systems'),
            dataSrc: ''
        },
        buttons: [{
            extend: 'edit'
        }, {
            extend: 'delete',
            action: function() {
                api.delete($('table tr.selected').data('id'));
            }
        }],
        columns: [{
                data: 'name'
            }, {
                data: 'path',
            }, {
                data: 'hypervisor.name',
                defaultContent: 'None'
            }, {
                data: 'flavor.name',
                defaultContent: 'None'
        }]
    });
})
  