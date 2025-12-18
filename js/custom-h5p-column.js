jQuery(document).ready(function($) {
    
    function checkTable() {
        var table_found = document.querySelector("#h5p-contents .wp-list-table"); // Table with list of h5p contets
    
        if (table_found) {
            //clearInterval(intervalID); // Stop calling the function checkTable

            var table = document.getElementById("h5p-contents").getElementsByClassName("wp-list-table")[0];
            var rows = table.rows;

            if (rows[1].innerHTML.split('</td><td>').length === 8) {

                if (rows[0].innerHTML.split('</th><th').length === 8) {
                    var param_split = '</th><th';
                    var row_splitted = rows[0].innerHTML.split(param_split);
                    var new_column = '>Duplicate Content'; // Content injected
                    row_splitted.splice(6, 0, new_column);
                    rows[0].innerHTML = row_splitted.join(param_split);
                }

                for (var i = 1; i < rows.length; ++i) {
                    if (i === rows.length-1) { // Change footer row of table

                        //rows[i].innerHTML = rows[i].innerHTML.replace('colspan="8"', 'colspan="9"');
                        //rows[i].innerHTML += '<td></td>';

                    } else { // Change body row of table

                        var param_split = '</td><td>';
                        var row_splitted = rows[i].innerHTML.split(param_split);
                        if (php_vars.duplicationTable.hasOwnProperty(row_splitted[5])) {
                            
                            row_splitted[0] += "<br><div>Duplicated from " 
                            + php_vars.duplicationTable[row_splitted[5]].origin_content_name
                            + " by " 
                            + php_vars.duplicationTable[row_splitted[5]].origin_content_author
                            + "</div>";
                        }

                        var new_column = '<a href="' 
                        + window.location.href 
                        + '&duplicate=1&content_id=' 
                        + row_splitted[5] 
                        + '">Duplicate</a>';  // Content injected
                        row_splitted.splice(6, 0, new_column);

                        rows[i].innerHTML = row_splitted.join(param_split);
                    }
                }
            }
        }
    }

    // Interval to call function checkTable that check if table is loaded
    var intervalID = setInterval(checkTable, 400);
});
