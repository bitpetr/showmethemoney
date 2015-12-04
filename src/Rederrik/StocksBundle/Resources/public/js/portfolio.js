/**
 * Created by davydov on 02.12.2015.
 */
$(function(){
    var error = function(message){
        var $error = $('#error');
        $error.find('.body').html(message||'Error has occurred. Please try again later.');
        $error.transition('show');
    };

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
                    error(res.error)
                    return;
                }
                $('#portfolio-container').find('tbody').append(
                    '<tr id="stock-row-'+result.id+'"><td>' + result.symbol + '</td><td>' + result.companyName +
                    '</td><td>' + result.lastTradePrice + '</td><td>' + result.changeInPercent +
                    '</td><td><button class="ui icon tiny stock-remove button" data-id="'+result.id+'">'+
                    '<i class="delete icon"></i> Remove</button></td></tr>'
                );
                updateGraph();
            },
            error: function(err) {
                error(err.responseText)
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
                    error(res.error);
                    return;
                }
                $('#stock-row-'+res.result.id).remove();
                updateGraph();
            }
        });
    };

    var loadGraph = function(data) {
        var $container = $("#graph-container");
        if(data.result == undefined || data.result.labels.length == 0) {
            $container.transition('hide');
            return;
        } else {
            $container.transition('show');
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
        var graph = new Chart(ctx).Line(data.result, {
            responsive: true,
            tooltipTemplate: "<%if (label){%><%=label%>: <%}%><%= value %> USD"
        });
        $container.removeClass('loading');
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
    $('.message .close')
        .on('click', function() {
            $(this)
                .closest('.message')
                .transition('fade')
            ;
        })
    ;
});