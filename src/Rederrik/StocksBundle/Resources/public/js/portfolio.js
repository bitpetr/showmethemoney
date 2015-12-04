/**
 * Created by davydov on 02.12.2015.
 */
$(function(){
    var addStock = function (e) {
        if (e.keyCode !== 13) {
            return;
        }

        $.ajax({
            type: 'POST',
            url: Routing.generate('stock_add'),
            data: {'symbol': $(this).val()},
            success: function (res) {
                var result = res.result;
                if (result == undefined) {
                    return;
                }
                $('#portfolio-container').find('tbody').append(
                    '<tr id="stock-row-'+result.id+'"><td>' + result.symbol + '</td><td>' + result.companyName +
                    '</td><td>' + result.lastTradePrice + '</td><td>' + result.changeInPercent +
                    '</td><td><button class="ui icon tiny stock-remove button" data-id="'+result.id+'">'+
                    '<i class="delete icon"></i> Remove</button></td></tr>'
                );
                updateGraph();
            }
        });
    };

    var removeStock = function() {
        $.ajax({
            type: 'POST',
            url: Routing.generate('stock_remove'),
            data: {'id': $(this).data('id')},
            success: function (res) {
                if (res.result == undefined || res.result.id == undefined) {
                    return;
                }
                $('#stock-row-'+res.result.id).remove();
                updateGraph();
            }
        });
    };

    var loadGraph = function(data) {
        if(data.result == undefined || data.result.labels.length == 0) {
            return;
        }
        var $graph = $("#graph");
        var width = $graph.parent().attr('width');
        var ctx = $graph.get(0).getContext("2d");
        data.result.datasets[0].fillColor = "rgba(151,187,205,0.2)";
        data.result.datasets[0].strokeColor = "rgba(151,187,205,1)";
        data.result.datasets[0].pointColor = "rgba(151,187,205,1)";
        data.result.datasets[0].pointStrokeColor = "#fff";
        data.result.datasets[0].pointHighlightFill = "#fff";
        data.result.datasets[0].pointHighlightStroke = "rgba(151,187,205,1)";
        graph = new Chart(ctx).Line(data.result, {
            responsive: true,
            tooltipTemplate: "<%if (label){%><%=label%>: <%}%><%= value %> USD"
        });
        $("#graph-container").removeClass('loading');
    };

    var updateGraph = function(){
        $("#graph-container").addClass('loading');
        $.ajax({
            type: 'GET',
            url: Routing.generate('portfolio_graph_data'),
            success: loadGraph
        });
    };

    updateGraph();
    $('#portfolio-container')
        .on('keyup', '#quote-add', addStock)
        .on('click', '.stock-remove', removeStock);
});