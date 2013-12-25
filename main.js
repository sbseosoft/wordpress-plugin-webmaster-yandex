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
        },
        
        adminDashboardDrawChart : function() {
            jQuery('#wmYaDashboardChart').empty().append('<h4>Loading charts...</h4>');
            var data = { action : 'wm_ya_dashboardChartData'};
            jQuery.post(ajaxurl, data, function(response) {
                response = jQuery.parseJSON(response);
                if (response.error === 0) {
                    var finalStatData = [['Date', 'Indexed', 'Crawled', 'Incoming', 'Excluded']];
                    jQuery('#wmYaDashboardChart').empty();
                    if (typeof(response.crawled) !== undefined) {
                        Main.adminDashboardDrawChartByType(response.crawled, 'wmYaDashboardChartCrawled', 'Проиндексированные URL');
                    }
                    if (typeof(response.indexed) !== undefined) {
                        Main.adminDashboardDrawChartByType(response.indexed, 'wmYaDashboardChartIndexed', 'Загруженные роботом URL');
                    }
                    if (typeof(response.incoming) !== undefined) {
                        Main.adminDashboardDrawChartByType(response.incoming, 'wmYaDashboardChartIncoming', 'Входящие ссылки');
                    }
                    if (typeof(response.excluded) !== undefined) {
                        Main.adminDashboardDrawChartByType(response.excluded, 'wmYaDashboardChartExcluded', 'Исключенные URL');
                    }
                }
                console.log(response);
                console.log(response.data);
                console.log(response.error);
            });
        },
        
        adminDashboardDrawChartByType : function (type, divId, header) {
            console.log('pew pew');
            jQuery('#wmYaDashboardChart').append('<div id="' + divId + 
                    '" style="height: 250px; width: 80%; padding-top: 30px; position: relative;">' + '</div><div style="clear: both;"></div>');
            var chartData = [['Дата', header]];
            jQuery.each(type, function (i, cData) {
                var currentStat = [cData.date, parseInt(cData.num)];
                chartData.push(currentStat);
            });
            var statData = google.visualization.arrayToDataTable(chartData);
            var chart = new google.visualization.AreaChart(document.getElementById(divId));
            chart.draw(statData);
            jQuery('#' + divId).prepend('<h3>' + header + '</h3>');
        }
    };
});