/*  Copyright 2013  Sbseosoft  (email : contact@sbseosoft.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

jQuery(document).ready(function(){
    Main = 
    {
        showPopup : function (url, title) {
            window.open(url, title, "status=1, height=400, width=450, resizable=1");
        },
        
        wmAddText : function () {
            var data = { postId : jQuery('#wmYaCurrentPostId').val(), action : 'wm_ya_add_text'};
            jQuery.post(ajaxurl, data, function(response) {
                if (response.error === 1) {
                    var contents = '<p style="color: red">Error: ' + response.errorText + '</p>';
                    jQuery('#wmYaResultsTextSend').clear().append(contents);
                } else {
                    var contents = '<p style="color: green">Text added</p>';
                    jQuery('#wmYaResultsTextSend').empty().append(contents);
                    var currentdate = new Date(); 
                    var datetime = currentdate.getFullYear() + "-"
                                   + (currentdate.getMonth() + 1)  + "-" 
                                   + currentdate.getDate() + " "  
                                   + currentdate.getHours() + ":"  
                                   + currentdate.getMinutes();
                    contents = 'Text added on ' + datetime;
                    jQuery('#wmYaTextSendDate').empty().append(contents);
                }
                
            });
            return false;
        } 
    };
});