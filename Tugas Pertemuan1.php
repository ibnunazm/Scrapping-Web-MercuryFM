<?php
function _retriever($url, $data = NULL, $header = NULL, $method = 'GET'){
    $cookie_file_path = dirname(__FILE__) . "/cookie/techinasia.txt";
    $datas['http_code'] = 0;
    if ($url == "")
        return $datas;
    $data_string = '';
    if ($data != NULL) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data_string .= $key . '=' . $value . '&';
            }
        } else {
            $data_string = $data;
        }
    }

    $ch = curl_init();
    if ($header != NULL)
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_NOBODY, false);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file_path);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file_path);
    curl_setopt(
        $ch,
        CURLOPT_USERAGENT,
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.90 Safari/537.36"
    );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_REFERER, $_SERVER['REQUEST_URI']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);

    if ($data != NULL) {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        // curl_setopt($ch, CURLOPT_POST, count($data));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    }

    $html = curl_exec($ch);
    //echo curl_getinfo($ch, CURLINFO_HTTP_CODE);
    //echo $html;
    if (!curl_errno($ch)) {
        $datas['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($datas['http_code'] == 200) {
            $datas['result'] = $html;
        }
    }
    curl_close($ch);
    return $datas;
}

$data = array();
$counter = 0;

$html = _retriever('https://mercuryfm.id/');
// print_r($html['result']);

$t_start_string = strpos($html['result'], '<div class="jeg_posts jeg_block_container">');
$t_html = substr($html['result'], $t_start_string);
// print_r($t_html); 

$script_url_start = strpos($t_html, "><script type='application/ld+json'>") + 36;
$script_url_end = strpos($t_html, '</script>', $script_url_start);
$script_url_length = $script_url_end - $script_url_start;
$json = substr($t_html, $script_url_start, $script_url_length);
$data_source = json_decode($json);

foreach ($data_source->itemListElement as $item) {
    $link = $item->item->url;
    $data[$counter]['link'] = $link;
    $detail = _retriever($link)['result'];
    // print_r($detail);

    $script_start = strpos($detail, "><script type='application/ld+json'>") + 36;
    $script_end = strpos($detail, '</script>', $script_start);
    $script_length = $script_end - $script_start;
    $json = substr($detail, $script_start, $script_length);
    $arr_data = json_decode($json);
    // print_r($arr_data);

    $data[$counter]['title'] = $arr_data->headline;
    $data[$counter]['publish_date'] = $arr_data->datePublished;
    $data[$counter]['author'] = $arr_data->author->name;
    $data[$counter]['image'] = $arr_data->image->url;
    $plainText = strip_tags($arr_data->articleBody);
    $plainText = preg_replace('/\s+/', ' ', $plainText);
    $data[$counter]['content'] = $plainText;
    $counter++;
}
// print_r($data);

$i = 0;

echo '<div style="text-align: center; margin:20px;">';
echo '<input type="text" style="width:800px; height:50px;" id="search" placeholder="Search Title">';
echo '</div>';

echo '<table border="1">';
echo    '<tr>
            <th>No.</th>
            <th>Article Link</th>
            <th>Title</th>
            <th>Publish Date</th>
            <th>Author</th>
            <th>Image</th>
            <th>Article Content</th>
        </tr>';
        
foreach ($data as $row) {
    echo '<tr>';
    echo '<td style="vertical-align: top;">' . ($i + 1) . '</td>';
    echo '<td style="vertical-align: top;"><a href="' . $row['link'] . '">' . $row['link'] . '</a></td>';
    echo '<td style="vertical-align: top;">' . $row['title'] . '</td>';
    echo '<td style="vertical-align: top;">' . $row['publish_date'] . '</td>';
    echo '<td style="vertical-align: top;">' . $row['author'] . '</td>';
    echo '<td style="vertical-align: top;"><img width="350" height="250" src="' . $row['image'] . '" alt="Image"></td>';
    echo '<td style="vertical-align: top;">' . $row['content'] . '</td>';
    echo '</tr>';
    $i++;
}
echo '</table>';

echo '<script>
document.getElementById("search").addEventListener("input", function() {
    var input, filter, table, tr, td, i, txtValue;
    input = document.getElementById("search");
    filter = input.value.toUpperCase();
    table = document.querySelector("table");
    tr = table.getElementsByTagName("tr");

    for (i = 0; i < tr.length; i++) {
        td = tr[i].getElementsByTagName("td")[2];
        if (td) {
            txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
});
</script>';

?>